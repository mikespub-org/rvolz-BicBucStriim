<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Functional test for the inline author links editor in js/script.js.
 *
 * Renders the author details page through the real app, extracts the links
 * form and delete-button structure with a real HTML parser and then exercises
 * js/script.js in Node (jsdom) against that markup. This catches
 * template/script drift, e.g. a renamed input id that detaches the save
 * handler, or an API param mapping (link-description/link-url to label/url)
 * that silently stops saving links.
 *
 * Excluded from the default test run (see <groups> in phpunit.xml.dist) because
 * it shells out to Node through yarn (jsdom is resolved via Yarn PnP). Run it
 * explicitly with:
 *
 *   vendor/bin/phpunit --group node-js
 *
 * or just this harness with --filter AuthorLinksScriptTest.
 */
#[CoversNothing]
#[Group('node-js')]
class AuthorLinksScriptTest extends PHPUnit\Framework\TestCase
{
    private const AUTHOR_ID = 5;

    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = dirname(__DIR__, 2);
    }

    /**
     * Render the author details page for an admin user and return its HTML.
     */
    private function renderAuthorDetailsPage(): string
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/authors/' . self::AUTHOR_ID . '/0/');
        $response = $app->handle($request);
        $this->assertSame(200, $response->getStatusCode(), 'Author details page did not render');
        $this->assertStringContainsString(
            '<title>BicBucStriim :: Author Details</title>',
            (string) $response->getBody()
        );
        return (string) $response->getBody();
    }

    /**
     * Extract the links editor structure the script depends on, using DOMDocument
     * so the assertions run against genuinely parsed HTML rather than string matching.
     *
     * @return array{action: string, proot: string, author: string}
     */
    private function extractLinksEditorStructure(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        $this->assertNotEquals(false, $loaded, 'Rendered author details markup could not be parsed');

        $xpath = new DOMXPath($dom);

        $forms = $xpath->query('//form[@id="author-link-new"]');
        $this->assertSame(1, $forms->length, 'Expected exactly one form#author-link-new');
        /** @var \DOMElement $form */
        $form = $forms->item(0);
        $this->assertSame(
            1,
            $xpath->query('.//input[@id="link-description"]', $form)->length,
            'form#author-link-new needs its input#link-description'
        );
        $this->assertSame(
            1,
            $xpath->query('.//input[@id="link-url"]', $form)->length,
            'form#author-link-new needs its input#link-url'
        );

        $this->assertSame(
            1,
            $xpath->query('//*[@id="author-links-list"]')->length,
            'Expected exactly one #author-links-list'
        );
        $this->assertSame(
            1,
            $xpath->query('//*[@id="author-links-edit"]')->length,
            'Expected exactly one #author-links-edit'
        );

        $delImage = $xpath->query('//*[@id="delete-image"]');
        $this->assertSame(1, $delImage->length, 'Expected exactly one #delete-image');
        /** @var \DOMElement $delete */
        $delete = $delImage->item(0);

        // every rendered delete button must carry the link id the script
        // reads via data-link (the fallback data-proot/data-author lives
        // on #delete-image only)
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " author-link-delete ")]') as $node) {
            /** @var DOMElement $node */
            $this->assertNotSame(
                '',
                $node->getAttribute('data-link'),
                'Every .author-link-delete needs a data-link attribute'
            );
        }

        return [
            'action' => $form->getAttribute('action'),
            'proot' => $delete->getAttribute('data-proot'),
            'author' => $delete->getAttribute('data-author'),
        ];
    }

    /**
     * Harnesses run via "yarn node" so Yarn PnP can resolve the jsdom dependency.
     */
    private function isNodeAvailable(): bool
    {
        $proc = proc_open('yarn node -e "require(\'jsdom\')"', [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) {
            return false;
        }
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($proc) === 0;
    }

    /**
     * Run the JS harness against the extracted links editor structure.
     *
     * @param array $fixture form/delete-link attributes extracted from the page
     */
    private function runHarness(array $fixture, string $label): string
    {
        $harness = $this->projectRoot . '/tests/js/author_links_harness.js';
        $this->assertFileExists($harness);

        $fixtureFile = tempnam(sys_get_temp_dir(), 'bbs-links-') . '.json';
        file_put_contents($fixtureFile, json_encode($fixture + ['label' => $label]));

        $cmd = sprintf(
            'yarn node %s %s %s 2>&1',
            escapeshellarg($harness),
            escapeshellarg($fixtureFile),
            escapeshellarg($this->projectRoot)
        );
        exec($cmd, $output, $exitCode);
        @unlink($fixtureFile);

        $this->assertSame(0, $exitCode, "$label link script assertions failed:\n" . implode("\n", $output));
        return implode("\n", $output);
    }

    /**
     * The rendered page must declare the exact markup js/script.js binds to,
     * and its form action must target the metadata links endpoint.
     */
    #[RunInSeparateProcess]
    public function testTemplateDeclaresLinksEditorMarkup(): void
    {
        $structure = $this->extractLinksEditorStructure($this->renderAuthorDetailsPage());

        $this->assertStringEndsWith(
            '/metadata/authors/' . self::AUTHOR_ID . '/links/',
            $structure['action'],
            'form#author-link-new must post to the metadata links endpoint'
        );
        $this->assertSame(
            (string) self::AUTHOR_ID,
            $structure['author'],
            '#delete-image needs the author id for the delete fallback'
        );
    }

    /**
     * js/script.js must add and delete links through the endpoints declared by
     * the rendered page, updating both lists inline.
     */
    #[RunInSeparateProcess]
    public function testLinksEditorScriptBehaviour(): void
    {
        if (!$this->isNodeAvailable()) {
            $this->markTestSkipped('yarn node with jsdom is not available');
        }

        $structure = $this->extractLinksEditorStructure($this->renderAuthorDetailsPage());
        $this->assertStringContainsString(
            'all author links script assertions passed',
            $this->runHarness(
                [
                    'formAction' => $structure['action'],
                    'deleteProot' => $structure['proot'],
                    'deleteAuthor' => $structure['author'],
                    'typedLabel' => 'BBS Test Link',
                    'typedUrl' => 'http://localhost/bbs/',
                    // synthetic pre-existing row in the harness DOM: keeps the
                    // fixture db pristine (cf. MetadataActionsTest::testAuthorLinks
                    // which would trip if we persisted a row here)
                    'existingLink' => [
                        'id' => '99',
                        'label' => 'Harness Existing',
                        'url' => 'http://localhost/existing/',
                    ],
                ],
                'author links page'
            )
        );
    }
}
