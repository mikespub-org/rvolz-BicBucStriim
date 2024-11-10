<?php

use BicBucStriim\Actions\MetadataActions;
use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Utilities\TestHelper;
use Nyholm\Psr7\UploadedFile;
use Slim\Factory\AppFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetadataActions::class)]
class MetadataActionsTest extends TestCase
{
    public const AUTHOR_ID = 10;
    public const SERIES_ID = 6;

    public static function getExpectedRoutes()
    {
        return [
            // 'name' => [method(s), path, ...middleware(s), callable] with '$self' string
            'meta-author-thumb' => ['GET', '/authors/{id}/thumbnail/', ['$self', 'getAuthorThumbnail']],
            'meta-author-thumb-post' => ['POST', '/authors/{id}/thumbnail/', ['$self', 'editAuthorThumbnail']],
            'meta-author-thumb-delete' => ['DELETE', '/authors/{id}/thumbnail/', ['$self', 'delAuthorThumbnail']],
            'meta-author-note' => ['GET', '/authors/{id}/notes/', ['$self', 'getAuthorNote']],
            'meta-author-note-post' => ['POST', '/authors/{id}/notes/', ['$self', 'editAuthorNote']],
            'meta-author-note-delete' => ['DELETE', '/authors/{id}/notes/', ['$self', 'delAuthorNote']],
            'meta-author-links' => ['GET', '/authors/{id}/links/', ['$self', 'getAuthorLinks']],
            'meta-author-link-post' => ['POST', '/authors/{id}/links/', ['$self', 'newAuthorLink']],
            'meta-author-link-delete' => ['DELETE', '/authors/{id}/links/{link}/', ['$self', 'delAuthorLink']],
            'meta-series-note' => ['GET', '/series/{id}/notes/', ['$self', 'getSeriesNote']],
            'meta-series-note-post' => ['POST', '/series/{id}/notes/', ['$self', 'editSeriesNotes']],
            'meta-series-note-delete' => ['DELETE', '/series/{id}/notes/', ['$self', 'delSeriesNotes']],
            'meta-series-links' => ['GET', '/series/{id}/links/', ['$self', 'getSeriesLinks']],
            'meta-series-link-post' => ['POST', '/series/{id}/links/', ['$self', 'newSeriesLink']],
            'meta-series-link-delete' => ['DELETE', '/series/{id}/links/{link}/', ['$self', 'delSeriesLink']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAddRoutes(): void
    {
        $expected = $this->getExpectedRoutes();
        $app = AppFactory::create();
        MetadataActions::addRoutes($app);
        $routeCollector = $app->getRouteCollector();
        $routes = $routeCollector->getRoutes();
        $this->assertEquals(count($expected), count($routes));
        $patterns = [];
        foreach ($routes as $route) {
            $patterns[] = $route->getPattern();
        }
        foreach ($expected as $routeInfo) {
            $path = MetadataActions::PREFIX . $routeInfo[1];
            $this->assertEquals(true, in_array($path, $patterns));
        }
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetAuthorThumbnail(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/metadata/authors/' . self::AUTHOR_ID . '/thumbnail/');

        $expected = '"data": null';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a thumbnail for this author
        /** @var BicBucStriim $bbs */
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        $artefact = $bbs->author(self::AUTHOR_ID)->getThumbnail();
        $this->assertNull($artefact);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testEditAuthorThumbnailNoFile(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('POST', '/metadata/authors/' . self::AUTHOR_ID . '/thumbnail/');

        $expected = 302;
        $response = $app->handle($request);
        $this->assertEquals($expected, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testEditAuthorThumbnailWrongFile(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('POST', '/metadata/authors/' . self::AUTHOR_ID . '/thumbnail/');
        $files = [
            'file' => new UploadedFile('test', 4, \UPLOAD_ERR_OK, 'wrong.txt', 'text/plain'),
        ];
        $request = $request->withUploadedFiles($files);

        $expected = 302;
        $response = $app->handle($request);
        $this->assertEquals($expected, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetAuthorThumbnail')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testEditAuthorThumbnail(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('POST', '/metadata/authors/' . self::AUTHOR_ID . '/thumbnail/');
        $source = dirname(__DIR__, 2) . '/img/writer.png';
        $tmpfile = tempnam(sys_get_temp_dir(), 'BBS');
        copy($source, $tmpfile);
        $size = filesize($source);
        $name = basename($source);
        $type = mime_content_type($source);
        $files = [
            'file' => new UploadedFile($tmpfile, $size, \UPLOAD_ERR_OK, $name, $type),
        ];
        $request = $request->withUploadedFiles($files);

        $expected = 302;
        $response = $app->handle($request);
        $this->assertEquals($expected, $response->getStatusCode());

        // check that we actually have a thumbnail for this author now
        /** @var BicBucStriim $bbs */
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        $artefact = $bbs->author(self::AUTHOR_ID)->getThumbnail();
        $this->assertNotNull($artefact);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testEditAuthorThumbnail')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testDeleteAuthorThumbnail(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('DELETE', '/metadata/authors/' . self::AUTHOR_ID . '/thumbnail/');

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a thumbnail for this author anymore
        /** @var BicBucStriim $bbs */
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        $artefact = $bbs->author(self::AUTHOR_ID)->getThumbnail();
        $this->assertNull($artefact);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAuthorNote(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/metadata/authors/' . self::AUTHOR_ID . '/notes/');

        $expected = '"data": null';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a note for this author
        /** @var BicBucStriim $bbs */
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        $note = $bbs->author(self::AUTHOR_ID)->getNote();
        $this->assertNull($note);

        $params = [
            'ntext' => '## Hello world!',
            'mime' => 'text/markdown',
        ];
        $request = TestHelper::getAuthRequest('POST', '/metadata/authors/' . self::AUTHOR_ID . '/notes/', $params);

        $expected = '"html": "<h2>Hello world!</h2>\n"';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we actually have a note for this author now
        $note = $bbs->author(self::AUTHOR_ID)->getNote();
        $this->assertNotNull($note);

        $request = TestHelper::getAuthRequest('DELETE', '/metadata/authors/' . self::AUTHOR_ID . '/notes/');

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a note for this author anymore
        $note = $bbs->author(self::AUTHOR_ID)->getNote();
        $this->assertNull($note);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAuthorLinks(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/metadata/authors/' . self::AUTHOR_ID . '/links/');

        $expected = '"data": []';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a link for this author
        /** @var BicBucStriim $bbs */
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        $links = $bbs->author(self::AUTHOR_ID)->getLinks();
        $this->assertCount(0, $links);

        $params = [
            'url' => 'http://localhost/bbs/',
            'label' => 'BBS Test',
        ];
        $request = TestHelper::getAuthRequest('POST', '/metadata/authors/' . self::AUTHOR_ID . '/links/', $params);

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $result = (string) $response->getBody();
        $this->assertStringContainsString($expected, $result);

        // check that we actually have a link for this author now
        $links = $bbs->author(self::AUTHOR_ID)->getLinks();
        $this->assertCount(1, $links);

        $data = json_decode($result, true);
        $link = (string) $data['link']['id'];
        $request = TestHelper::getAuthRequest('DELETE', '/metadata/authors/' . self::AUTHOR_ID . '/links/' . $link . '/');

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a link for this author anymore
        $links = $bbs->author(self::AUTHOR_ID)->getLinks();
        $this->assertCount(0, $links);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testSeriesNote(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/metadata/series/' . self::SERIES_ID . '/notes/');

        $expected = '"data": null';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a note for this series
        /** @var BicBucStriim $bbs */
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        $note = $bbs->series(self::SERIES_ID)->getNote();
        $this->assertNull($note);

        $params = [
            'ntext' => '## Hello world!',
            'mime' => 'text/markdown',
        ];
        $request = TestHelper::getAuthRequest('POST', '/metadata/series/' . self::SERIES_ID . '/notes/', $params);

        $expected = '"html": "<h2>Hello world!</h2>\n"';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we actually have a note for this series now
        $note = $bbs->series(self::SERIES_ID)->getNote();
        $this->assertNotNull($note);

        $request = TestHelper::getAuthRequest('DELETE', '/metadata/series/' . self::SERIES_ID . '/notes/');

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a note for this series anymore
        $note = $bbs->series(self::SERIES_ID)->getNote();
        $this->assertNull($note);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testSeriesLinks(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/metadata/series/' . self::SERIES_ID . '/links/');

        $expected = '"data": []';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a link for this series
        /** @var BicBucStriim $bbs */
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        $links = $bbs->series(self::SERIES_ID)->getLinks();
        $this->assertCount(0, $links);

        $params = [
            'url' => 'http://localhost/bbs/',
            'label' => 'BBS Test',
        ];
        $request = TestHelper::getAuthRequest('POST', '/metadata/series/' . self::SERIES_ID . '/links/', $params);

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $result = (string) $response->getBody();
        $this->assertStringContainsString($expected, $result);

        // check that we actually have a link for this series now
        $links = $bbs->series(self::SERIES_ID)->getLinks();
        $this->assertCount(1, $links);

        $data = json_decode($result, true);
        $link = (string) $data['link']['id'];
        $request = TestHelper::getAuthRequest('DELETE', '/metadata/series/' . self::SERIES_ID . '/links/' . $link . '/');

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // check that we don't have a link for this series anymore
        $links = $bbs->series(self::SERIES_ID)->getLinks();
        $this->assertCount(0, $links);
    }
}
