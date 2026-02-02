<?php

use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Calibre\CalibreFilter;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Calibre::class)]
#[CoversClass(CalibreFilter::class)]
class CalibreTest extends PHPUnit\Framework\TestCase
{
    public static $cdb1;
    public static $cdb2;
    public static $cdb3;

    /** @var ?Calibre */
    public $calibre;

    public function setUp(): void
    {
        self::$cdb1 = dirname(__DIR__, 2) . '/tests/fixtures/metadata_empty.db';
        self::$cdb2 = dirname(__DIR__, 2) . '/tests/fixtures/lib2/metadata.db';
        self::$cdb3 = dirname(__DIR__, 2) . '/tests/fixtures/metadata_error.db';
        $this->calibre = new Calibre(self::$cdb2);
    }

    public function tearDown(): void
    {
        $this->calibre = null;
    }

    public function testOpenCalibreEmptyDb(): void
    {
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertTrue($this->calibre->libraryOk());
    }

    public function testOpenCalibreNotExistingDb(): void
    {
        $this->calibre = new Calibre(self::$cdb3);
        $this->assertFalse($this->calibre->libraryOk());
        $this->assertEquals(0, $this->calibre->last_error);
    }

    public function testGetTagId(): void
    {
        $this->assertEquals(21, $this->calibre->getTagId('Architecture'));
        $this->assertNull($this->calibre->getTagId('Nothing'));
    }

    public function testGetLanguageId(): void
    {
        $this->assertEquals(3, $this->calibre->getLanguageId('eng'));
        $this->assertNull($this->calibre->getLanguageId('Nothing'));
    }

    public function testLibraryStatsEmptyFilter(): void
    {
        $result = $this->calibre->libraryStats(new CalibreFilter());
        $this->assertEquals(7, $result["titles"]);
        $this->assertEquals(6, $result["authors"]);
        $this->assertEquals(6, $result["tags"]);
        $this->assertEquals(4, $result["series"]);
        $this->assertGreaterThanOrEqual(Calibre::USER_VERSION, $result["version"]);
    }

    public function testLibraryStatsTagFilter(): void
    {
        $result = $this->calibre->libraryStats(new CalibreFilter($lang = null, $tag = 21));
        $this->assertEquals(6, $result["titles"]);
        $this->assertEquals(6, $result["authors"]);
        $this->assertEquals(6, $result["tags"]);
        $this->assertEquals(4, $result["series"]);
        $this->assertGreaterThanOrEqual(Calibre::USER_VERSION, $result["version"]);
    }

    public function testLibraryStatsLanguageFilter(): void
    {
        $result = $this->calibre->libraryStats(new CalibreFilter($lang = 3, $tag = null));
        $this->assertEquals(1, $result["titles"]);
        $this->assertEquals(6, $result["authors"]);
        $this->assertEquals(6, $result["tags"]);
        $this->assertEquals(4, $result["series"]);
        $this->assertGreaterThanOrEqual(Calibre::USER_VERSION, $result["version"]);
    }

    public function testLibraryStatsLanguageAndTagFilter(): void
    {
        $result = $this->calibre->libraryStats(new CalibreFilter($lang = 1, $tag = 3));
        $this->assertEquals(1, $result["titles"]);
        $this->assertEquals(6, $result["authors"]);
        $this->assertEquals(6, $result["tags"]);
        $this->assertEquals(4, $result["series"]);
        $this->assertGreaterThanOrEqual(Calibre::USER_VERSION, $result["version"]);
    }

    public function testLast30(): void
    {
        $result = $this->calibre->last30Books('en', 30, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(7, count($result));
        $result2 = $this->calibre->last30Books('en', 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result2));
        $result3 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 3));
        $this->assertEquals(1, count($result3));
        $result4 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEquals(6, count($result4));
        $result5 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEquals(0, count($result5));
        $result3 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 2));
        $this->assertEquals(2, count($result3));
        $result4 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEquals(1, count($result4));
    }

    public function testAuthorsSlice(): void
    {
        $result0 = $this->calibre->authorsSlice(0, 2);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->authorsSlice(1, 2);
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(3, $result1['pages']);
        $result2 = $this->calibre->authorsSlice(2, 2);
        $this->assertEquals(2, count($result2['entries']));
        $this->assertEquals(2, $result2['page']);
        $this->assertEquals(3, $result2['pages']);
        $no_result = $this->calibre->authorsSlice(100, 2);
        $this->assertEquals(0, count($no_result['entries']));
        $this->assertEquals(100, $no_result['page']);
        $this->assertEquals(3, $no_result['pages']);
    }

    public function testAuthorsSliceSearch(): void
    {
        $result0 = $this->calibre->authorsSlice(0, 2, 'I');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->authorsSlice(1, 2, 'I');
        $this->assertEquals(2, count($result1['entries']));
        $result3 = $this->calibre->authorsSlice(2, 2, 'I');
        $this->assertEquals(1, count($result3['entries']));
    }

    public function testAuthorDetailsSlice(): void
    {
        $result0 = $this->calibre->authorDetailsSlice('en', 6, 0, 1, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->authorDetailsSlice('en', 6, 1, 1, new CalibreFilter());
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(2, $result1['pages']);
    }

    public function testAuthorDetailsSliceWithFilter(): void
    {
        $result0 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(1, $result0['pages']);
        $result1 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter(1));
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(0, $result1['page']);
        $this->assertEquals(1, $result1['pages']);
        $result2 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter(2));
        $this->assertEquals(0, count($result2['entries']));
        $this->assertEquals(0, $result2['page']);
        $this->assertEquals(0, $result2['pages']);
    }

    public function testAuthorsInitials(): void
    {
        $result = $this->calibre->authorsInitials();
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(5, count($result));
        $this->assertEquals('E', $result[0]->initial);
        $this->assertEquals(1, $result[0]->ctr);
        $this->assertEquals('R', $result[4]->initial);
        $this->assertEquals(2, $result[4]->ctr);
    }

    public function testAuthorsNamesForInitial(): void
    {
        $result = $this->calibre->authorsNamesForInitial('R');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result));
        $this->assertEquals(1, $result[0]->anzahl);
        $this->assertEquals('Rilke, Rainer Maria', $result[0]->sort);
    }

    public function testTagsSlice(): void
    {
        $result0 = $this->calibre->tagsSlice(0, 2);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->tagsSlice(1, 2);
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(3, $result1['pages']);
        $result2 = $this->calibre->tagsSlice(2, 2);
        $this->assertEquals(2, count($result2['entries']));
        $this->assertEquals(2, $result2['page']);
        $this->assertEquals(3, $result2['pages']);
        $no_result = $this->calibre->tagsSlice(100, 2);
        $this->assertEquals(0, count($no_result['entries']));
        $this->assertEquals(100, $no_result['page']);
        $this->assertEquals(3, $no_result['pages']);
    }

    public function testTagsSliceSearch(): void
    {
        $result0 = $this->calibre->tagsSlice(0, 2, 'I');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->tagsSlice(1, 2, 'I');
        $this->assertEquals(2, count($result1['entries']));
        $result3 = $this->calibre->tagsSlice(2, 2, 'I');
        $this->assertEquals(1, count($result3['entries']));
    }

    public function testTagDetailsSlice(): void
    {
        $result0 = $this->calibre->tagDetailsSlice('en', 3, 0, 1, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->tagDetailsSlice('en', 3, 1, 1, new CalibreFilter());
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(2, $result1['pages']);
    }

    public function testTagsInitials(): void
    {
        $result = $this->calibre->tagsInitials();
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(5, count($result));
        $this->assertEquals('A', $result[0]->initial);
        $this->assertEquals(1, $result[0]->ctr);
        $this->assertEquals('V', $result[4]->initial);
        $this->assertEquals(1, $result[4]->ctr);
    }

    public function testTagsNamesForInitial(): void
    {
        $result = $this->calibre->tagsNamesForInitial('B');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result));
        $this->assertEquals(1, $result[0]->anzahl);
        $this->assertEquals('Belletristik & Literatur', $result[0]->name);
    }

    public function testTimestampOrderedTitlesSlice(): void
    {
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(4, $result0['pages']);
        $this->assertEquals(7, $result0['entries'][0]->id);
        $this->assertEquals(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->timestampOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(4, $result1['pages']);
        $this->assertEquals(5, $result1['entries'][0]->id);
        $this->assertEquals(4, $result1['entries'][1]->id);
        $result3 = $this->calibre->timestampOrderedTitlesSlice('en', 3, 2, new CalibreFilter());
        $this->assertEquals(1, count($result3['entries']));
        $this->assertEquals(3, $result3['page']);
        $this->assertEquals(4, $result3['pages']);
        $this->assertEquals(1, $result3['entries'][0]->id);
        $no_result = $this->calibre->timestampOrderedTitlesSlice('en', 100, 2, new CalibreFilter());
        $this->assertEquals(0, count($no_result['entries']));
        $this->assertEquals(100, $no_result['page']);
        $this->assertEquals(4, $no_result['pages']);

        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 3));
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(1, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 1, 2, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(1, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEquals(0, count($result0['entries']));
        $this->assertEquals(0, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(1, $result0['pages']);
    }

    public function testPubdateOrderedTitlesSlice(): void
    {
        $result0 = $this->calibre->pubdateOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(4, $result0['pages']);
        $this->assertEquals(7, $result0['entries'][0]->id);
        $this->assertEquals(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->pubdateOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(4, $result1['pages']);
        $this->assertEquals(5, $result1['entries'][0]->id);
    }

    public function testLastmodifiedOrderedTitlesSlice(): void
    {
        $result0 = $this->calibre->lastmodifiedOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(4, $result0['pages']);
        $this->assertEquals(7, $result0['entries'][0]->id);
        $this->assertEquals(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->lastmodifiedOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(4, $result1['pages']);
        $this->assertEquals(5, $result1['entries'][0]->id);
        $this->assertEquals(1, $result1['entries'][1]->id);
    }

    public function testTitlesSlice(): void
    {
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(4, $result0['pages']);
        $result1 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(4, $result1['pages']);
        $result3 = $this->calibre->titlesSlice('en', 3, 2, new CalibreFilter());
        $this->assertEquals(1, count($result3['entries']));
        $this->assertEquals(3, $result3['page']);
        $this->assertEquals(4, $result3['pages']);
        $no_result = $this->calibre->titlesSlice('en', 100, 2, new CalibreFilter());
        $this->assertEquals(0, count($no_result['entries']));
        $this->assertEquals(100, $no_result['page']);
        $this->assertEquals(4, $no_result['pages']);

        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 3));
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(1, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(1, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEquals(0, count($result0['entries']));
        $this->assertEquals(0, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(1, $result0['pages']);
    }

    public function testCount(): void
    {
        $count = 'select count(*) from books where lower(title) like :search';
        $params = ['search' => '%i%'];
        $result = $this->calibre->count($count, $params);
        $this->assertEquals(6, $result);
    }

    public function testTitlesSliceSearch(): void
    {
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter(), 'I');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter(), 'I');
        $this->assertEquals(2, count($result1['entries']));
        $result3 = $this->calibre->titlesSlice('en', 2, 2, new CalibreFilter(), 'I');
        $this->assertEquals(2, count($result3['entries']));
    }

    public function testAuthorDetails(): void
    {
        $result = $this->calibre->authorDetails(7);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('Lessing, Gotthold Ephraim', $result['author']->sort);
    }

    public function testTagDetails(): void
    {
        $result = $this->calibre->tagDetails(3);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('Fachbücher', $result['tag']->name);
        $this->assertEquals(2, count($result['books']));
    }

    public function testTitle(): void
    {
        $result = $this->calibre->title(3);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals('Der seltzame Springinsfeld', $result->title);
    }

    public function testTitleCover(): void
    {
        $result = $this->calibre->titleCover(3);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('cover.jpg', basename((string) $result));
    }

    public function testTitleFile(): void
    {
        $result = $this->calibre->titleFile(3, 'Der seltzame Springinsfeld - Hans Jakob Christoffel von Grimmelshausen.epub');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('Der seltzame Springinsfeld - Hans Jakob Christoffel von Grimmelshausen.epub', basename($result));
    }

    public function testTitleDetails(): void
    {
        $result = $this->calibre->titleDetails('en', 3);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals('Der seltzame Springinsfeld', $result['book']->title);
        $this->assertEquals('Fachbücher', $result['tags'][0]->name);
        $this->assertEquals('Serie Grimmelshausen', $result['series'][0]->name);
    }

    public function testTitleDetailsOpds(): void
    {
        $book = $this->calibre->title(3);
        $result = $this->calibre->titleDetailsOpds($book);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals('Der seltzame Springinsfeld', $result['book']->title);
        $this->assertEquals('Fachbücher', $result['tags'][0]->name);
    }

    public function testTitleDetailsFilteredOpds(): void
    {
        $books = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($books['entries']));
        $result = $this->calibre->titleDetailsFilteredOpds($books['entries']);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result));
    }

    public function testSeriesSlice(): void
    {
        $result0 = $this->calibre->seriesSlice(0, 2);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->seriesSlice(1, 2);
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(2, $result1['pages']);
    }

    public function testSeriesSliceSearch(): void
    {
        $result0 = $this->calibre->seriesSlice(0, 2, 'I');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->seriesSlice(1, 2, 'I');
        $this->assertEquals(2, count($result1['entries']));
    }

    public function testSeriesDetailsSlice(): void
    {
        $result0 = $this->calibre->seriesDetailsSlice('en', 1, 0, 1, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->seriesDetailsSlice('en', 1, 1, 1, new CalibreFilter());
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(2, $result1['pages']);
    }

    public function testSeriesInitials(): void
    {
        $result = $this->calibre->seriesInitials();
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result));
        $this->assertEquals('S', $result[0]->initial);
        $this->assertEquals(3, $result[0]->ctr);
    }

    public function testSeriesNamesForInitial(): void
    {
        $result = $this->calibre->seriesNamesForInitial('S');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(3, count($result));
        $this->assertEquals(2, $result[0]->anzahl);
        $this->assertEquals('Serie Grimmelshausen', $result[0]->name);
    }
}
