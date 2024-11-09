<?php

if (!defined('REDBEAN_MODEL_PREFIX')) {
    define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\Models\\');
}

use BicBucStriim\AppData\AppAuthor;
use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\AppData\DataConstants;
use BicBucStriim\Models\Calibrething;
use BicBucStriim\Models\Artefact;
use BicBucStriim\Models\Link;
use BicBucStriim\Models\Note;
use BicBucStriim\Models\R;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AppAuthor::class)]
#[CoversClass(BicBucStriim::class)]
#[CoversClass(Calibrething::class)]
#[CoversClass(Artefact::class)]
#[CoversClass(Link::class)]
#[CoversClass(Note::class)]
class AuthorsTest extends PHPUnit\Framework\TestCase
{
    public static $schema;
    public static $testschema;
    public static $db2;

    public static $data;
    public static $datadb;

    /** @var ?BicBucStriim */
    public $bbs;

    public function setUp(): void
    {
        self::$schema = dirname(__DIR__, 2) . '/data/schema.sql';
        self::$testschema = dirname(__DIR__, 2) . '/tests/data/schema.sql';
        self::$db2 = dirname(__DIR__, 2) . '/tests/fixtures/data2.db';

        self::$data = dirname(__DIR__, 2) . '/tests/data';
        self::$datadb = dirname(__DIR__, 2) . '/tests/data/data.db';
        if (file_exists(self::$data)) {
            system("rm -rf " . self::$data);
        }
        mkdir(self::$data);
        chmod(self::$data, 0o777);
        copy(self::$db2, self::$datadb);
        copy(self::$schema, self::$testschema);
        $this->bbs = new BicBucStriim(self::$datadb, false);
    }

    public function tearDown(): void
    {
        // Must use nuke() to clear caches etc.
        R::nuke();
        R::close();
        $this->bbs = null;
        system("rm -rf " . self::$data);
    }

    public function testCalibreAuthor(): void
    {
        $this->assertNull($this->bbs->getCalibreAuthor(1));
        $result = $this->bbs->addCalibreAuthor(1, 'Author 1');
        $this->assertNotNull($result);
        $this->assertEquals('Author 1', $result->cname);
        $this->assertEquals(0, $result->refctr);
        $result2 = $this->bbs->getCalibreAuthor(1);
        $this->assertEquals('Author 1', $result2->cname);
        $this->assertEquals(0, $result2->refctr);
    }

    public function testEditAuthorThumbnail(): void
    {
        $this->assertNotNull($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $this->assertTrue(file_exists(self::$data . '/authors/author_1_thm.png'));
        $result2 = $this->bbs->getCalibreAuthor(1);
        $this->assertEquals('Author Name', $result2->cname);
        $this->assertEquals(1, $result2->refctr);
        $artefacts = $result2->ownArtefactList;
        $this->assertEquals(1, count($artefacts));
        $result = $artefacts[1];
        $this->assertNotNull($result);
        $this->assertEquals(DataConstants::AUTHOR_TYPE, $result->atype);
        $this->assertEquals('./tests/data/authors/author_1_thm.png', $result->url);
    }

    public function testGetAuthorThumbnail(): void
    {
        $this->assertNotNull($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $this->assertNotNull($this->bbs->author(2, 'Author Name')->editThumbnail(true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $result = $this->bbs->getAuthorThumbnail(1);
        $this->assertNotNull($result);
        $this->assertEquals(DataConstants::AUTHOR_TYPE, $result->atype);
        $this->assertEquals('./tests/data/authors/author_1_thm.png', $result->url);
        $result = $this->bbs->author(2)->getThumbnail();
        $this->assertNotNull($result);
    }

    public function testDeleteAuthorThumbnail(): void
    {
        $this->assertNotNull($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $this->assertNotNull($this->bbs->getAuthorThumbnail(1));
        $this->assertTrue($this->bbs->deleteAuthorThumbnail(1));
        $this->assertFalse(file_exists(self::$data . '/authors/author_1_thm.png'));
        $this->assertNull($this->bbs->getAuthorThumbnail(1));
        $this->assertNotNull($this->bbs->author(2, 'Author Name')->editThumbnail(true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $this->assertNotNull($this->bbs->author(2)->getThumbnail());
        $this->assertTrue($this->bbs->author(2)->deleteThumbnail());
        $this->assertNull($this->bbs->author(2)->getThumbnail());
        $this->assertFalse(file_exists(self::$data . '/authors/author_2_thm.png'));
        $this->assertEquals(0, R::count('artefact'));
        $this->assertEquals(0, R::count('calibrething'));
    }

    public function testAuthorLinks(): void
    {
        $this->assertEquals(0, count($this->bbs->authorLinks(1)));
        $this->bbs->author(2, 'Author 1')->addLink('google', 'http://google.com/1');
        $this->bbs->addAuthorLink(1, 'Author 2', 'amazon', 'http://amazon.com/2');
        $links = $this->bbs->authorLinks(1);
        $this->assertEquals(2, R::count('link'));
        $this->assertEquals(1, count($links));
        $this->assertEquals(DataConstants::AUTHOR_TYPE, $links[0]->ltype);
        $this->assertEquals('amazon', $links[0]->label);
        $this->assertEquals('http://amazon.com/2', $links[0]->url);
        $this->assertEquals(2, $links[0]->id);
        $links = $this->bbs->author(2)->getLinks();
        $this->assertEquals(1, count($links));
        $this->assertEquals(DataConstants::AUTHOR_TYPE, $links[0]->ltype);
        $this->assertEquals('google', $links[0]->label);
        $this->assertEquals('http://google.com/1', $links[0]->url);
        $this->assertEquals(1, $links[0]->id);
        $this->assertTrue($this->bbs->deleteAuthorLink(1, 2));
        $this->assertEquals(0, count($this->bbs->authorLinks(1)));
        $this->assertEquals(1, R::count('link'));
        $this->assertTrue($this->bbs->author(2)->deleteLink(1));
        $this->assertEquals(0, count($this->bbs->authorLinks(2)));
        $this->assertEquals(0, R::count('link'));
    }

    public function testAuthorNote(): void
    {
        $this->assertNull($this->bbs->authorNote(1));
        $this->bbs->editAuthorNote(2, 'Author 1', 'text/plain', 'Goodbye, goodbye!');
        $this->bbs->editAuthorNote(1, 'Author 2', 'text/plain', 'Hello again!');
        $this->assertEquals(2, R::count('note'));
        $note = $this->bbs->authorNote(1);
        $this->assertNotNull($note);
        $this->assertEquals(DataConstants::AUTHOR_TYPE, $note->ntype);
        $this->assertEquals('text/plain', $note->mime);
        $this->assertEquals('Hello again!', $note->ntext);
        $this->assertEquals(2, $note->id);
        $note = $this->bbs->editAuthorNote(1, 'Author 2', 'text/markdown', '*Hello again!*');
        $this->assertEquals('text/markdown', $note->mime);
        $this->assertEquals('*Hello again!*', $note->ntext);
        $this->assertTrue($this->bbs->deleteAuthorNote(1));
        $this->assertEquals(1, R::count('note'));
    }
}
