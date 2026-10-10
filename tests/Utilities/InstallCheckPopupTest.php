<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2016 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 * Functional test for the installation check info popups.
 *
 * Renders templates/installcheck.twig, extracts the popup structure with a real
 * HTML parser and then exercises js/installcheck.js in Node against that markup.
 * This catches template/script drift, e.g. a split button pointing at a popup id
 * that no longer exists, or a script regression that stops the dialogs opening.
 */

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Excluded from the default test run (see <groups> in phpunit.xml.dist) because
 * it shells out to Node. Run it explicitly with:
 *
 *   vendor/bin/phpunit --group installcheck-js
 */
#[CoversNothing]
#[Group('installcheck-js')]
class InstallCheckPopupTest extends PHPUnit\Framework\TestCase
{
    private const EXPECTED_POPUPS = 11;

    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = dirname(__DIR__, 2);
    }

    /**
     * Render installcheck.twig with the same variables installcheck.php supplies.
     */
    private function renderInstallCheck(): string
    {
        $twig = new Environment(new FilesystemLoader($this->projectRoot . '/templates'), []);
        return $twig->load('installcheck.twig')->render([
            'page' => ['rot' => '', 'version' => '4.1.2'],
            'is_a' => true,
            'srv' => 'Apache/2.4',
            'mre' => true,
            'calibre_dir' => null,
            'cd' => null,
            'htaccess' => true,
            'hsql' => true,
            'hgd2' => true,
            'hgd2v' => '2.3',
            'dwrit' => true,
            'intl' => true,
            'sodium' => true,
            'mwrit' => true,
            'opd' => '',
            'php' => true,
            'phpv' => '8.4.26',
        ]);
    }

    /**
     * Extract the popup markup the script depends on, using DOMDocument so the
     * assertions run against genuinely parsed HTML rather than string matching.
     *
     * @return array{popups: list<array{id: string, closeButtons: int}>, triggers: list<array{href: string}>}
     */
    private function extractPopupStructure(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        $this->assertNotEquals(false, $loaded, 'Rendered installcheck markup could not be parsed');

        $xpath = new DOMXPath($dom);

        $popups = [];
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " bbs-popup ")]') as $node) {
            $popups[] = [
                'id' => $node->getAttribute('id'),
                'closeButtons' => $xpath->query('.//a[@data-rel="back"]', $node)->length,
            ];
        }

        $triggers = [];
        foreach ($xpath->query('//a[@data-rel="popup"]') as $node) {
            $triggers[] = ['href' => $node->getAttribute('href')];
        }

        return ['popups' => $popups, 'triggers' => $triggers];
    }

    private function isNodeAvailable(): bool
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open('node --version', $descriptors, $pipes);
        if (!is_resource($proc)) {
            return false;
        }
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($proc) === 0;
    }

    /**
     * Run the JS harness against an extracted structure.
     *
     * @param array $structure popups/triggers extracted from the template
     */
    private function runHarness(array $structure, string $label): string
    {
        $harness = $this->projectRoot . '/tests/js/installcheck_popup_harness.js';
        $this->assertFileExists($harness);

        $fixture = tempnam(sys_get_temp_dir(), 'bbs-popup-') . '.json';
        file_put_contents($fixture, json_encode($structure + ['label' => $label]));

        $cmd = sprintf(
            'node %s %s %s 2>&1',
            escapeshellarg($harness),
            escapeshellarg($fixture),
            escapeshellarg($this->projectRoot)
        );
        exec($cmd, $output, $exitCode);
        @unlink($fixture);

        $this->assertSame(0, $exitCode, "$label popup assertions failed:\n" . implode("\n", $output));
        return implode("\n", $output);
    }

    /**
     * The template must declare every popup the script opens, with a close button.
     */
    public function testTemplateDeclaresPopupMarkup(): void
    {
        $structure = $this->extractPopupStructure($this->renderInstallCheck());

        $this->assertCount(self::EXPECTED_POPUPS, $structure['popups']);
        $this->assertCount(self::EXPECTED_POPUPS, $structure['triggers']);

        $ids = [];
        foreach ($structure['popups'] as $popup) {
            $this->assertNotSame('', $popup['id'], 'Every .bbs-popup needs an id');
            $this->assertSame(1, $popup['closeButtons'], 'Popup ' . $popup['id'] . ' needs exactly one close button');
            $ids[] = $popup['id'];
        }

        // Each split button must resolve to a declared popup id.
        foreach ($structure['triggers'] as $trigger) {
            $this->assertStringStartsWith('#', $trigger['href']);
            $this->assertContains(ltrim($trigger['href'], '#'), $ids, 'Split button points at a missing popup');
        }

        // And every popup should be reachable from a split button.
        $targets = array_map(fn ($t) => ltrim($t['href'], '#'), $structure['triggers']);
        foreach ($ids as $id) {
            $this->assertContains($id, $targets, 'Popup ' . $id . ' is never opened by a split button');
        }
    }

    /**
     * js/installcheck.js must open and close the popups described by the template.
     */
    public function testPopupOpensAndCloses(): void
    {
        if (!$this->isNodeAvailable()) {
            $this->markTestSkipped('Node.js is not available');
        }

        $structure = $this->extractPopupStructure($this->renderInstallCheck());
        $this->assertNotEmpty($structure['triggers'], 'No popup triggers found in template');

        // js/script.js normally creates the shared backdrop element.
        $this->assertStringContainsString('with js/script.js backdrop', $this->runHarness(
            $structure + ['hasBackdrop' => true],
            'with js/script.js backdrop'
        ));

        // The script must also cope when it has to create the backdrop itself.
        $this->assertStringContainsString('standalone', $this->runHarness(
            $structure + ['hasBackdrop' => false],
            'standalone'
        ));
    }
}
