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
 * Functional test for the inline author notes editor in js/script.js.
 *
 * Renders the author notes page through the real app, extracts the edit form
 * and delete link with a real HTML parser and then exercises js/script.js in
 * Node against that markup. This catches template/script drift, e.g. a renamed
 * form id that silently detaches the save handler again (see commit c2af339).
 *
 * Excluded from the default test run (see <groups> in phpunit.xml.dist) because
 * it shells out to Node. Run it explicitly with:
 *
 *   vendor/bin/phpunit --group node-js
 *
 * or just this harness with --filter AuthorNotesScriptTest.
 */
#[CoversNothing]
#[Group('node-js')]
class AuthorNotesScriptTest extends PHPUnit\Framework\TestCase
{
    private const AUTHOR_ID = 5;
    private const TYPED_NOTE = '## Hello world!';

    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = dirname(__DIR__, 2);
    }

    /**
     * Render the author notes page for an admin user and return its HTML.
     */
    private function renderAuthorNotesPage(): string
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/authors/' . self::AUTHOR_ID . '/notes/');
        $response = $app->handle($request);
        $this->assertSame(200, $response->getStatusCode(), 'Author notes page did not render');
        $this->assertStringContainsString(
            '<title>BicBucStriim :: Notes</title>',
            (string) $response->getBody()
        );
        return (string) $response->getBody();
    }

    /**
     * Extract the note editor structure the script depends on, using DOMDocument
     * so the assertions run against genuinely parsed HTML rather than string matching.
     *
     * @return array{action: string, proot: string, author: string}
     */
    private function extractNoteEditorStructure(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        $this->assertNotEquals(false, $loaded, 'Rendered author notes markup could not be parsed');

        $xpath = new DOMXPath($dom);

        $forms = $xpath->query('//form[@id="author-note-edit"]');
        $this->assertSame(1, $forms->length, 'Expected exactly one form#author-note-edit');
        /** @var \DOMElement $form */
        $form = $forms->item(0);
        $this->assertSame(
            1,
            $xpath->query('.//textarea[@id="note-edit"]', $form)->length,
            'form#author-note-edit needs its textarea#note-edit'
        );

        $deletes = $xpath->query('//a[@id="delete-notes"]');
        $this->assertSame(1, $deletes->length, 'Expected exactly one a#delete-notes');
        /** @var \DOMElement $delete */
        $delete = $deletes->item(0);

        $sections = $xpath->query('//section[@id="author-notes"]');
        $this->assertSame(1, $sections->length, 'Expected exactly one section#author-notes');

        return [
            'action' => $form->getAttribute('action'),
            'proot' => $delete->getAttribute('data-proot'),
            'author' => $delete->getAttribute('data-author'),
        ];
    }

    private function isNodeAvailable(): bool
    {
        $proc = proc_open('node --version', [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) {
            return false;
        }
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($proc) === 0;
    }

    /**
     * Run the JS harness against the extracted note editor structure.
     *
     * @param array $fixture form/delete link attributes extracted from the page
     */
    private function runHarness(array $fixture, string $label): string
    {
        $harness = $this->projectRoot . '/tests/js/author_notes_harness.js';
        $this->assertFileExists($harness);

        $fixtureFile = tempnam(sys_get_temp_dir(), 'bbs-notes-') . '.json';
        file_put_contents($fixtureFile, json_encode($fixture + ['label' => $label]));

        $cmd = sprintf(
            'node %s %s %s 2>&1',
            escapeshellarg($harness),
            escapeshellarg($fixtureFile),
            escapeshellarg($this->projectRoot)
        );
        exec($cmd, $output, $exitCode);
        @unlink($fixtureFile);

        $this->assertSame(0, $exitCode, "$label note script assertions failed:\n" . implode("\n", $output));
        return implode("\n", $output);
    }

    /**
     * The rendered page must declare the exact markup js/script.js binds to,
     * and its form action must target the metadata notes endpoint.
     */
    #[RunInSeparateProcess]
    public function testTemplateDeclaresNoteEditorMarkup(): void
    {
        $structure = $this->extractNoteEditorStructure($this->renderAuthorNotesPage());

        $this->assertStringEndsWith(
            '/metadata/authors/' . self::AUTHOR_ID . '/notes/',
            $structure['action'],
            'form#author-note-edit must post to the metadata notes endpoint'
        );
        $this->assertSame(
            (string) self::AUTHOR_ID,
            $structure['author'],
            'a#delete-notes needs the author id'
        );
    }

    /**
     * js/script.js must save and delete notes through the endpoints declared
     * by the rendered page, updating the preview inline.
     */
    #[RunInSeparateProcess]
    public function testNoteEditorScriptBehaviour(): void
    {
        if (!$this->isNodeAvailable()) {
            $this->markTestSkipped('Node.js is not available');
        }

        $structure = $this->extractNoteEditorStructure($this->renderAuthorNotesPage());
        $this->assertStringContainsString(
            'all author notes script assertions passed',
            $this->runHarness(
                [
                    'formAction' => $structure['action'],
                    'deleteProot' => $structure['proot'],
                    'deleteAuthor' => $structure['author'],
                    'typedNote' => self::TYPED_NOTE,
                ],
                'author notes page'
            )
        );
    }
}
