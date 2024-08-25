<?php
/**
 * EPUB metadata converter test suite.
 *
 */

use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Calibre\Author;
use BicBucStriim\Calibre\Tag;
use BicBucStriim\Utilities\EPub;
use BicBucStriim\Utilities\MetadataEpub;

/**
 * @covers \BicBucStriim\Utilities\MetadataEpub
 * @covers \BicBucStriim\Utilities\EPub
 * @covers \BicBucStriim\Utilities\EPubDOMXPath
 * @covers \BicBucStriim\Utilities\EPubDOMElement
 */
class MetadataEpubTest extends PHPUnit\Framework\TestCase
{
    public const DATA = './tests/data';
    public const FDIR = './tests/fixtures/';
    public const CDIR = './tests/fixtures/lib2/';
    public const CDB2 = './tests/fixtures/lib2/metadata.db';

    /** @var ?Calibre */
    public $calibre;

    public function setUp(): void
    {
        if (file_exists(self::DATA)) {
            system("rm -rf " . self::DATA);
        }
        mkdir(self::DATA);
        chmod(self::DATA, 0o777);
        $this->calibre = new Calibre(self::CDB2);
    }

    public function tearDown(): void
    {
        $this->calibre = null;
        system("rm -rf " . self::DATA);
    }

    public function compareCover($bookFile, $imageFile)
    {
        $conv2 = new EPub($bookFile);
        $imageData = $conv2->Cover();
        $byteArray = unpack("C*", $imageData['data']);
        $dsize = count($byteArray);
        $handle = fopen($imageFile, "r");
        $fsize = filesize($imageFile);
        $contents = fread($handle, $fsize);
        fclose($handle);
        //printf("compareCover epub cover size %d\n", $dsize);
        //printf("compareCover image cover size %d\n", $fsize);
        if ($dsize != $fsize) {
            return false;
        }
        return true;
    }

    public function testConstructStdDir(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $conv = new MetadataEpub($orig);
        $tmpfile = $conv->getUpdatedFile();
        $this->assertTrue(file_exists($tmpfile));
        $parts = pathinfo($tmpfile);
        $this->assertEquals(sys_get_temp_dir(), $parts['dirname']);
        $this->assertEquals(filesize($orig), filesize($tmpfile));
    }

    public function testConstructOtherDir(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $conv = new MetadataEpub($orig, self::DATA);
        $tmpfile = $conv->getUpdatedFile();
        $this->assertTrue(file_exists($tmpfile));
        $parts = pathinfo($tmpfile);
        $this->assertEquals(realpath(self::DATA), $parts['dirname']);
        $this->assertEquals(filesize($orig), filesize($tmpfile));
    }

    public function testDestruct(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $conv = new MetadataEpub($orig);
        $tmpfile = $conv->getUpdatedFile();
        $this->assertTrue(file_exists($tmpfile));
        $conv = null;
        $this->assertFalse(file_exists($tmpfile));
    }

    public function testUpdateMetadataTitle(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $new_title = 'Kein Lob der Faulheit';
        $conv = new MetadataEpub($orig);
        $md = $this->calibre->titleDetails('de', 1);
        $md['book']->title = $new_title;
        $conv->updateMetadata($md);

        $tmpfile = $conv->getUpdatedFile();
        $check = new EPub($tmpfile);
        $this->assertEquals($new_title, $check->Title());
    }

    public function testUpdateMetadataAuthors(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $new_author = new Author();
        $new_author->sort = 'Lastname, Firstname';
        $new_author->name = 'Firstname Lastname';
        $conv = new MetadataEpub($orig);
        $md = $this->calibre->titleDetails('de', 1);
        array_unshift($md['authors'], $new_author);
        $conv->updateMetadata($md);

        $tmpfile = $conv->getUpdatedFile();
        $check = new EPub($tmpfile);
        $authors_check = $check->Authors();
        $this->assertEquals(2, count($authors_check));
        $this->assertEquals('Firstname Lastname', $authors_check['Lastname, Firstname']);
        $this->assertEquals('Gotthold Ephraim Lessing', $authors_check['Lessing, Gotthold Ephraim']);
    }

    public function testUpdateMetadataLanguage(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $new_lang = 'eng';
        $conv = new MetadataEpub($orig);
        $md = $this->calibre->titleDetails('de', 1);
        $md['langcodes'][0] = $new_lang;
        $conv->updateMetadata($md);

        $tmpfile = $conv->getUpdatedFile();
        $check = new EPub($tmpfile);
        if (extension_loaded('intl')) {
            $this->assertEquals('en', $check->Language());
        } else {
            $this->assertEquals('de', $check->Language());
        }
    }

    public function testUpdateMetadataMultipleLanguages(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $conv = new MetadataEpub($orig);
        $md = $this->calibre->titleDetails('de', 1);
        $md['langcodes'][0] = 'eng';
        $md['langcodes'][1] = 'lat';
        $conv->updateMetadata($md);

        $tmpfile = $conv->getUpdatedFile();
        $check = new EPub($tmpfile);
        if (extension_loaded('intl')) {
            $this->assertEquals('en', $check->Language());
        } else {
            $this->assertEquals('de', $check->Language());
        }
    }

    public function testUpdateId(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $conv = new MetadataEpub($orig);
        $md = $this->calibre->titleDetails('de', 1);
        $md['ids']['isbn'] = '000000';
        $md['ids']['google'] = '111111';
        $md['ids']['amazon'] = '222222';
        $conv->updateMetadata($md);

        $tmpfile = $conv->getUpdatedFile();
        $check = new EPub($tmpfile);
        $this->assertEquals('000000', $check->ISBN());
        $this->assertEquals('111111', $check->Google());
        $this->assertEquals('222222', $check->Amazon());
    }

    public function testUpdateMetadataTags(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $new_tag1 = new Tag();
        $new_tag1->name = 'Subject 1';
        $new_tag2 = new Tag();
        $new_tag2->name = 'Subject 2';
        $conv = new MetadataEpub($orig);
        $md = $this->calibre->titleDetails('de', 1);
        $md['tags'] = [$new_tag1, $new_tag2];
        $conv->updateMetadata($md);

        $tmpfile = $conv->getUpdatedFile();
        $check = new EPub($tmpfile);
        $subjects_check = $check->Subjects();
        $this->assertEquals(2, count($subjects_check));
        $this->assertEquals('Subject 1', $subjects_check[0]);
        $this->assertEquals('Subject 2', $subjects_check[1]);
    }

    public function testUpdateMetadataCover(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $cover = self::FDIR . 'test-cover.jpg';
        $cover2 = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/cover.jpg';
        $md = $this->calibre->titleDetails('de', 1);
        $conv = new MetadataEpub($orig);
        $conv->updateMetadata($md, $cover);
        $tmpfile = $conv->getUpdatedFile();
        $this->assertTrue($this->compareCover($tmpfile, $cover));
        $this->assertFalse($this->compareCover($tmpfile, $cover2));
    }

    public function testUpdateMetadataDescription(): void
    {
        $orig = self::CDIR . '/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $new_desc = '<div><p>Kein Lob der Faulheit</p></div>';
        $conv = new MetadataEpub($orig);
        $md = $this->calibre->titleDetails('de', 1);
        $md['comment'] = $new_desc;
        $conv->updateMetadata($md);

        $tmpfile = $conv->getUpdatedFile();
        $check = new EPub($tmpfile);
        $this->assertEquals($new_desc, $check->Description());
    }
}
