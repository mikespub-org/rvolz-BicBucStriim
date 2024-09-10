<?php
/**
 * OPDS Generator test suite.
 *
 * Needs the follwoing external tools installed:
 * - jing (http://code.google.com/p/jing-trang/)
 * - opds_validator (https://github.com/zetaben/opds-validator)
 */

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Calibre\CalibreFilter;
use BicBucStriim\Utilities\OpdsGenerator;
use BicBucStriim\Utilities\L10n;

#[\PHPUnit\Framework\Attributes\CoversClass(\BicBucStriim\Utilities\OpdsGenerator::class)]
class OpdsGeneratorTest extends PHPUnit\Framework\TestCase
{
    public const OPDS_RNG = './tests/fixtures/opds_catalog_1_1.rng';
    public const DATA = './tests/data';
    public const DB2 = './tests/fixtures/data2.db';
    public const CDB2 = './tests/fixtures/lib2/metadata.db';
    public const DATADB = './tests/data/data.db';

    /** @var ?BicBucStriim */
    public $bbs;
    /** @var OpdsGenerator */
    public $gen;
    /** @var ?Calibre */
    public $calibre;

    public function setUp(): void
    {
        if (file_exists(self::DATA)) {
            system("rm -rf " . self::DATA);
        }
        mkdir(self::DATA);
        chmod(self::DATA, 0o777);
        copy(self::DB2, self::DATADB);
        $this->bbs = new BicBucStriim(self::DATADB);
        $this->calibre = new Calibre(self::CDB2);
        $l10n = new L10n('en');
        $this->gen = new OpdsGenerator(
            '/bbs',
            '0.9.0',
            $this->calibre->calibre_dir,
            date(DATE_ATOM, strtotime('2012-01-01T11:59:59')),
            $l10n
        );
    }

    public function tearDown(): void
    {
        $this->calibre = null;
        $this->bbs = null;
        system("rm -rf " . self::DATA);
    }

    # Validation helper: validate relaxng
    public function opdsValidateSchema($feed)
    {
        $res = system('cd ~/seblucas-cops/tests;java -jar jing.jar ' . realpath(self::OPDS_RNG) . ' ' . realpath($feed));
        if ($res != '') {
            echo 'RelaxNG validation error: ' . $res;
            return false;
        } else {
            return true;
        }
    }

    # Validation helper: validate opds
    public function opdsValidate($feed, $version)
    {
        $cmd = 'cd ~/seblucas-cops/tests;java -jar OPDSValidator.jar -v' . $version . ' ' . realpath($feed);
        $res = system($cmd);
        if ($res != '') {
            echo 'OPDS validation error: ' . $res;
            return false;
        } else {
            return true;
        }
    }

    # Timestamp helper: generate proper timezone offsets
    # see http://www.php.net/manual/en/datetimezone.getoffset.php
    public function genTimestampOffset($phpTime)
    {
        if (date_default_timezone_get() == 'UTC') {
            $offsetString = '+00:00'; // No need to calculate offset, as default timezone is already UTC
        } else {
            $millis = strtotime((string) $phpTime); // Convert time to milliseconds since 1970, using default timezone
            $timezone = new DateTimeZone(date_default_timezone_get()); // Get default system timezone to create a new DateTimeZone object
            $offset = $timezone->getOffset(new DateTime($phpTime)); // Offset in seconds to UTC
            $offsetHours = round(abs($offset) / 3600);
            $offsetMinutes = round((abs($offset) - $offsetHours * 3600) / 60);
            $offsetString = ($offset < 0 ? '-' : '+')
                        . ($offsetHours < 10 ? '0' : '') . $offsetHours
                        . ':'
                        . ($offsetMinutes < 10 ? '0' : '') . $offsetMinutes;
        }
        return $offsetString;
    }

    public function testRootCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $xml = $this->gen->rootCatalog($feed);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }



    public function testPartialAcquisitionEntry(): void
    {
        // @todo align opds routes with or without /opds/
        $expected = '<entry>
 <id>urn:bicbucstriim:/bbs/titles/2</id>
 <title>Trutz Simplex</title>
 <dc:issued>2012</dc:issued>
 <updated>2012-01-01T11:59:59' . $this->genTimestampOffset('2012-01-01 11:59:59') . '</updated>
 <author>
  <name>Grimmelshausen, Hans Jakob Christoffel von</name>
 </author>
 <content type="text/html"></content>
 <dc:language>deu</dc:language>
 <link href="/bbs/titles/2/thumbnail/" type="image/png" rel="http://opds-spec.org/image/thumbnail"/>
 <link href="/bbs/titles/2/cover/" type="image/jpeg" rel="http://opds-spec.org/image"/>
 <link href="/bbs/titles/2/file/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub" type="application/epub+zip" rel="http://opds-spec.org/acquisition"/>
 <link href="/bbs/titles/2/file/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.mobi" type="application/x-mobipocket-ebook" rel="http://opds-spec.org/acquisition"/>
 <category term="Biografien &amp; Memoiren" label="Biografien &amp; Memoiren"/>
</entry>
';
        $just_book = $this->calibre->title(2);
        #print_r($just_book);
        $book = $this->calibre->titleDetailsOpds($just_book);
        $this->gen->openStream(null);
        $this->gen->partialAcquisitionEntry($book, false);
        $result = $this->gen->closeStream();
        #print_r($result);
        $this->assertEquals($expected, $result);
    }

    public function testPartialAcquisitionEntryWithProtection(): void
    {
        // @todo align opds routes with or without /opds/
        $expected = '<entry>
 <id>urn:bicbucstriim:/bbs/titles/2</id>
 <title>Trutz Simplex</title>
 <dc:issued>2012</dc:issued>
 <updated>2012-01-01T11:59:59' . $this->genTimestampOffset('2012-01-01T11:59:59') . '</updated>
 <author>
  <name>Grimmelshausen, Hans Jakob Christoffel von</name>
 </author>
 <content type="text/html"></content>
 <dc:language>deu</dc:language>
 <link href="/bbs/titles/2/thumbnail/" type="image/png" rel="http://opds-spec.org/image/thumbnail"/>
 <link href="/bbs/titles/2/cover/" type="image/jpeg" rel="http://opds-spec.org/image"/>
 <link href="/bbs/titles/2/showaccess/" type="text/html" rel="http://opds-spec.org/acquisition">
  <opds:indirectAcquisition type="application/epub+zip"/>
 </link>
 <link href="/bbs/titles/2/showaccess/" type="text/html" rel="http://opds-spec.org/acquisition">
  <opds:indirectAcquisition type="application/x-mobipocket-ebook"/>
 </link>
 <category term="Biografien &amp; Memoiren" label="Biografien &amp; Memoiren"/>
</entry>
';
        $just_book = $this->calibre->title(2);
        $book = $this->calibre->titleDetailsOpds($just_book);
        $this->gen->openStream(null);
        $this->gen->partialAcquisitionEntry($book, true);
        $result = $this->gen->closeStream();
        #print_r($result);
        $this->assertEquals($expected, $result);
    }

    public function testNewestCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $just_books = $this->calibre->last30Books('en', 30, new CalibreFilter());
        $books = $this->calibre->titleDetailsFilteredOpds($just_books);
        $xml = $this->gen->newestCatalog($feed, $books, false);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testTitlesCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $tl = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter());
        $books = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $xml = $this->gen->titlesCatalog(
            $feed,
            $books,
            false,
            $tl['page'],
            $tl['page'] + 1,
            $tl['pages'] - 1
        );
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testTitlesCatalogOpenSearch(): void
    {
        $tl = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter());
        $books = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $xml = $this->gen->titlesCatalog(
            null,
            $books,
            false,
            $tl['page'],
            $tl['page'] + 1,
            $tl['pages'] - 1
        );
        $feed = new SimpleXMLElement($xml);
        $this->assertEquals(7, count($feed->link));
        $oslnk = $feed->link[0];
        $this->assertEquals(OpdsGenerator::OPENSEARCH_MIME, (string) $oslnk['type']);
        $this->assertTrue(strpos((string) $oslnk['href'], 'opensearch.xml') > 0);
    }


    public function testAuthorsInitialCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $tl = $this->calibre->authorsInitials();
        $xml = $this->gen->authorsRootCatalog($feed, $tl);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testAuthorsNamesForInitialCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $tl = $this->calibre->authorsNamesForInitial('R');
        $xml = $this->gen->authorsNamesForInitialCatalog($feed, $tl, 'R');
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testAuthorsBooksForAuthorCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $adetails = $this->calibre->authorDetails(5);
        $books = $this->calibre->titleDetailsFilteredOpds($adetails['books']);
        $xml = $this->gen->booksForAuthorCatalog($feed, $books, 'E', $adetails['author'], false, 0, 1, 2);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testTagsInitialCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $tl = $this->calibre->tagsInitials();
        $xml = $this->gen->tagsRootCatalog($feed, $tl);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testTagsNamesForInitialCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $tl = $this->calibre->tagsNamesForInitial('B');
        $xml = $this->gen->tagsNamesForInitialCatalog($feed, $tl, 'B');
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testTagsBooksForTagCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $adetails = $this->calibre->tagDetails(9);
        $books = $this->calibre->titleDetailsFilteredOpds($adetails['books']);
        $xml = $this->gen->booksForTagCatalog($feed, $books, 'B', $adetails['tag'], false, 0, 1, 2);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testSeriesInitialCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $tl = $this->calibre->seriesInitials();
        $xml = $this->gen->seriesRootCatalog($feed, $tl);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    public function testSeriesNamesForInitialCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $tl = $this->calibre->seriesNamesForInitial('S');
        $xml = $this->gen->seriesNamesForInitialCatalog($feed, $tl, 'S');
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }

    protected function testSeriesBooksForSeriesCatalogValidation(): void
    {
        $feed = self::DATA . '/feed.xml';
        $adetails = $this->calibre->seriesDetailsSlice('en', 1);
        $books = $this->calibre->titleDetailsFilteredOpds($adetails['books']);
        $xml = $this->gen->booksForSeriesCatalog($feed, $books, 'S', $adetails['series'], false, 0, 1, 2);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed, '1.0'));
        $this->assertTrue($this->opdsValidate($feed, '1.1'));
    }
}
