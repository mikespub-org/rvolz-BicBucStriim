<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Calibre;

use BicBucStriim\Utilities\CalibreUtil;
use PDO;
use Locale;
use Exception;

/**
 * Calibre DB
 * @see https://github.com/kovidgoyal/calibre/blob/master/resources/metadata_sqlite.sql
 */
class Calibre
{
    # Calibre DB user version
    public const USER_VERSION = 26;
    # Calibre Notes DB file
    public const NOTES_DB_FILE = '.calnotes/notes.db';
    # Calibre Notes DB name
    public const NOTES_DB_NAME = 'notes_db';

    # last sqlite error
    public $last_error = 0;

    # calibre sqlite db
    protected $calibre = null;
    # calibre library dir
    public $calibre_dir = '';
    # calibre library file, last modified date
    public $calibre_last_modified;
    # calibre user version
    public $calibre_version = null;
    # is there a calibre notes db
    public $hasNotes = false;

    /**
     * Check if the Calibre DB is readable
     * @param  string $path Path to Calibre DB
     * @return boolean            true if exists and is readable, else false
     */
    public static function checkForCalibre($path)
    {
        $rp = realpath($path);
        $rpm = $rp . '/metadata.db';
        return is_readable($rpm);
    }

    /**
     * Open the Calibre DB.
     * @param string $calibrePath Complete path to Calibre library file
     */
    public function __construct($calibrePath)
    {
        $rp = realpath($calibrePath);
        $this->calibre_dir = dirname($rp);
        if (file_exists($rp) && is_readable($rp)) {
            $this->calibre_last_modified = filemtime($rp);
            $this->calibre = new PDO('sqlite:' . $rp, null, null, []);
            //$this->calibre->setAttribute(1002, 'SET NAMES utf8');
            $this->calibre->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->calibre->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $notes_db = realpath($this->calibre_dir . '/' . self::NOTES_DB_FILE);
            if (!empty($notes_db) && is_readable($notes_db)) {
                $sql = $this->mkAttachDatabase($notes_db, self::NOTES_DB_NAME);
                try {
                    $this->calibre->exec($sql);
                    $this->hasNotes = true;
                } catch (Exception $e) {
                    // ...
                }
            }
            $this->last_error = $this->calibre->errorCode();
        } else {
            $this->calibre = null;
        }
    }

    /**
     * Is the Calibre library open?
     * @return boolean    true if open, else false
     */
    public function libraryOk()
    {
        return !is_null($this->calibre);
    }

    /**
     * Return an array with library statistics for titles, authors etc.
     *
     * @param object $filter    a QueryFilter
     * @return array            array of numbers fir titles, authers etc.
     */
    public function libraryStats($filter)
    {
        $stats = [];
        $countParams = $this->mkCountParams(null, $filter, null);
        $queryFilter = $filter->getBooksFilter();
        $stats["titles"] = $this->count($this->mkBooksCount($queryFilter, false), $countParams);
        $stats["authors"] = $this->count($this->mkAuthorsCount($queryFilter, false), []);
        $stats["tags"] = $this->count($this->mkTagsCount($queryFilter, false), []);
        $stats["series"] = $this->count($this->mkSeriesCount($queryFilter, false), []);
        $stats["version"] = $this->getUserVersion();
        return $stats;
    }

    /**
     * Execute a query $sql on the Calibre DB and return the result
     * as an array of objects of class $class
     *
     * @param string $class Calibre item class name
     * @param string $sql SQL statement
     * @param array $params array of query parameters
     * @return array found items
     */
    protected function findPrepared($class, $sql, $params)
    {
        if (!str_contains($class, __NAMESPACE__ . '\\')) {
            $class = __NAMESPACE__ . '\\' . $class;
        }
        $stmt = $this->calibre->prepare($sql);
        $stmt->execute($params);
        $this->last_error = $stmt->errorCode();
        $items = $stmt->fetchAll(PDO::FETCH_CLASS, $class);
        $stmt->closeCursor();
        return $items;
    }

    /**
     * Return a single object or NULL if not found
     * @param  string $class Calibre Item class
     * @param  string $sql SQL statement
     * @param array $params array of query parameters
     * @return ?object                instance of class $class or NULL
     */
    protected function findOne($class, $sql, $params = [])
    {
        $result = $this->findPrepared($class, $sql, $params);
        if ($result == null || $result == false) {
            return null;
        } else {
            return $result[0];
        }
    }

    private function mkCountParams($id, $filter, $search)
    {
        $params = [];
        if (!is_null($id)) {
            $params['id'] = $id;
        }
        if (!is_null($filter->tag_id)) {
            $params['tag'] = $filter->tag_id;
        }
        if (!is_null($filter->lang_id)) {
            $params['lang'] = $filter->lang_id;
        }
        if (!is_null($search)) {
            $params['search_l'] = '%' . mb_convert_case($search, MB_CASE_LOWER, 'UTF-8') . '%';
            $params['search_t'] = '%' . mb_convert_case($search, MB_CASE_TITLE, 'UTF-8') . '%';
        }
        return $params;
    }

    private function mkQueryParams($id, $filter, $search, $length, $offset)
    {
        $params = [];
        if (!is_null($id)) {
            $params['id'] = $id;
        }
        if (!is_null($filter->tag_id)) {
            $params['tag'] = $filter->tag_id;
        }
        if (!is_null($filter->lang_id)) {
            $params['lang'] = $filter->lang_id;
        }
        if (!is_null($search)) {
            $params['search_l'] = '%' . mb_convert_case($search, MB_CASE_LOWER, 'UTF-8') . '%';
            $params['search_t'] = '%' . mb_convert_case($search, MB_CASE_TITLE, 'UTF-8') . '%';
        }
        if (!is_null($length)) {
            $params['length'] = $length;
        }
        if (!is_null($offset)) {
            $params['offset'] = $offset;
        }
        return $params;
    }

    /**
     * Return a slice of entries defined by the parameters $index and $length.
     * If $search is defined it is used to filter the titles, ignoring case.
     * Return an array with elements: current page, no. of pages, $length entries
     *
     * @param  integer          $searchType      index of search type to use, see CalibreSearchType
     * @param  integer          $index=0         page index
     * @param  integer          $length=100      length of page
     * @param  CalibreFilter    $filter          filter expression
     * @param  string           $search=NULL     search pattern for sort/name fields
     * @param  integer          $id=NULL         optional author/tag/series ID     *
     * @return array                            an array with current page (key 'page'),
     *                                          number of pages (key 'pages'),
     *                                          an array of $class instances (key 'entries') or NULL
     *
     * Changed thanks to QNAP who insist on publishing outdated libraries in their firmware
     * TODO revert back to real SQL, not the outdated-QNAP stlyle
 */
    protected function findSliceFiltered($searchType, $index = 0, $length = 100, $filter = null, $search = null, $id = null)
    {
        if ($index < 0 || $length < 1 || $searchType < CalibreSearchType::Author || $searchType > CalibreSearchType::LastModifiedOrderedBook) {
            return ['page' => 0, 'pages' => 0, 'entries' => null];
        }
        $offset = $index * $length;
        $searching = !is_null($search);
        $countParams = $this->mkCountParams($id, $filter, $search);
        $queryParams = $this->mkQueryParams($id, $filter, $search, $length, $offset);
        $queryFilter = $filter->getBooksFilter();
        switch ($searchType) {
            case CalibreSearchType::Author:
                $class = Author::class;
                $count = $this->mkAuthorsCount($queryFilter, $searching);
                if (is_null($search)) {
                    $query = 'SELECT a.id, a.name, a.sort, (SELECT COUNT(*) FROM books_authors_link b WHERE b.author=a.id) AS anzahl FROM authors AS a ORDER BY a.sort';
                } else {
                    $query = 'SELECT a.id, a.name, a.sort, (SELECT COUNT(*) FROM books_authors_link b WHERE b.author=a.id) AS anzahl FROM authors AS a WHERE lower(a.name) LIKE :search_l OR lower(a.name) LIKE :search_t ORDER BY a.sort';
                }
                break;
            case CalibreSearchType::AuthorBook:
                $class = AuthorBook::class;
                if (is_null($search)) {
                    $count = 'SELECT count(*) FROM (SELECT BAL.book, Books.* FROM books_authors_link BAL, ' . $queryFilter . ' Books WHERE Books.id=BAL.book AND author=:id)';
                    $query = 'SELECT BAL.book, Books.* FROM books_authors_link BAL, ' . $queryFilter . ' Books WHERE Books.id=BAL.book AND author=:id ORDER BY Books.sort';
                } else {
                    $count = 'SELECT count(*) FROM (SELECT BAL.book, Books.* FROM books_authors_link BAL, ' . $queryFilter . ' Books WHERE Books.id=BAL.book AND author=:id) WHERE Books.sort LIKE :search_l OR Books.sort LIKE :search_u OR Books.sort LIKE :search_t';
                    $query = 'SELECT BAL.book, Books.* FROM books_authors_link BAL, ' . $queryFilter . ' Books WHERE Books.id=BAL.book AND author=:id AND (lower(Books.sort) LIKE :search_l OR lower(Books.sort) LIKE :search_t) ORDER BY Books.sort';
                }
                break;
            case CalibreSearchType::Book:
                $class = Book::class;
                $count = $this->mkBooksCount($queryFilter, $searching);
                $query = $this->mkBooksQuery($searchType, true, $queryFilter, $searching);
                break;
            case CalibreSearchType::Series:
                $class = Series::class;
                $count = $this->mkSeriesCount($queryFilter, $searching);
                if (is_null($search)) {
                    $query = 'SELECT series.id, series.name, (SELECT COUNT(*) FROM books_series_link AS bsl WHERE series.id = bsl.series ) AS anzahl FROM series ORDER BY series.name';
                } else {
                    $query = 'SELECT series.id, series.name, (SELECT COUNT(*) FROM books_series_link AS bsl WHERE series.id = bsl.series ) AS anzahl FROM series WHERE lower(series.name) LIKE :search_l OR lower(series.name) LIKE :search_t ORDER BY series.name';
                }
                break;
            case CalibreSearchType::SeriesBook:
                $class = SeriesBook::class;
                if (is_null($search)) {
                    $count = 'SELECT count (*) FROM (SELECT BSL.book, Books.* FROM books_series_link BSL, ' . $queryFilter . ' Books WHERE Books.id=BSL.book AND series=:id)';
                    $query = 'SELECT BSL.book, Books.* FROM books_series_link BSL, ' . $queryFilter . ' Books WHERE Books.id=BSL.book AND series=:id ORDER BY series_index';
                } else {
                    $count = 'SELECT count (*) FROM (SELECT BSL.book, Books.* FROM books_series_link BSL, ' . $queryFilter . ' Books WHERE Books.id=BSL.book AND series=:id) WHERE sort LIKE :search_l OR sort LIKE :search_u OR sort LIKE :search_t';
                    $query = 'SELECT BSL.book, Books.* FROM books_series_link BSL, ' . $queryFilter . ' Books WHERE Books.id=BSL.book AND series=:id AND (lower(Books.sort) LIKE :search_l OR lower(Books.sort) LIKE :search_t) ORDER BY series_index';
                }
                break;
            case CalibreSearchType::Tag:
                $class = Tag::class;
                $count = $this->mkTagsCount($queryFilter, $searching);
                if (is_null($search)) {
                    $query = 'SELECT tags.id, tags.name, (SELECT COUNT(*) FROM books_tags_link AS btl WHERE tags.id = btl.tag) AS anzahl FROM tags ORDER BY tags.name';
                } else {
                    $query = 'SELECT tags.id, tags.name, (SELECT COUNT(*) FROM books_tags_link AS btl WHERE tags.id = btl.tag) AS anzahl FROM tags WHERE lower(tags.name) LIKE :search_l OR lower(tags.name) LIKE :search_t ORDER BY tags.name';
                }
                break;
            case CalibreSearchType::TagBook:
                $class = TagBook::class;
                if (is_null($search)) {
                    $count = 'SELECT count (*) FROM (SELECT BTL.book, Books.* FROM books_tags_link BTL, ' . $queryFilter . ' Books WHERE Books.id=BTL.book AND tag=:id)';
                    $query = 'SELECT BTL.book, Books.* FROM books_tags_link BTL, ' . $queryFilter . ' Books WHERE Books.id=BTL.book AND tag=:id ORDER BY Books.sort';
                } else {
                    $count = 'SELECT count (*) FROM (SELECT BTL.book, Books.* FROM books_tags_link BTL, ' . $queryFilter . ' Books WHERE Books.id=BTL.book AND tag=:id) WHERE sort LIKE :search_l OR sort LIKE :search_u OR sort LIKE :search_t';
                    $query = 'SELECT BTL.book, Books.* FROM books_tags_link BTL, ' . $queryFilter . ' Books WHERE Books.id=BTL.book AND tag=:id AND (lower(Books.sort) LIKE :search_l OR lower(Books.sort) LIKE :search_t) ORDER BY Books.sort';
                }
                break;
            case CalibreSearchType::TimestampOrderedBook:
            case CalibreSearchType::PubDateOrderedBook:
            case CalibreSearchType::LastModifiedOrderedBook:
                $class = Book::class;
                $count = $this->mkBooksCount($queryFilter, $searching);
                $query = $this->mkBooksQuery($searchType, false, $queryFilter, $searching);
                break;
            default:
                throw new Exception('Invalid search type');
        }
        $query = $query . ' limit :length offset :offset';
        $no_entries = $this->count($count, $countParams);
        if ($no_entries > 0) {
            $no_pages = (int) ($no_entries / $length);
            if ($no_entries % $length > 0) {
                $no_pages += 1;
            }
            $entries = $this->findPrepared($class, $query, $queryParams);
        } else {
            $no_pages = 0;
            $entries = [];
        }
        return ['page' => $index, 'pages' => $no_pages, 'entries' => $entries, 'total' => $no_entries];
    }

    /**
     * Generate a SQL query for selecting books ordered by various fields
     * @param int $searchType CalibreSearchType
     * @param boolean $sortAscending ASC, result should be sorted ASC or DESC?
     * @param string $queryFilter CalibreFilter
     * @param bool $search false, a query with a search filter?
     * @return string                               SQL query
     */
    private function mkBooksQuery($searchType, $sortAscending, $queryFilter, $search = false)
    {
        switch ($searchType) {
            case CalibreSearchType::Book:
                $sortField = 'sort';
                break;
            case CalibreSearchType::TimestampOrderedBook:
                $sortField = 'timestamp';
                break;
            case CalibreSearchType::PubDateOrderedBook:
                $sortField = 'pubdate';
                break;
            case CalibreSearchType::LastModifiedOrderedBook:
                $sortField = 'last_modified';
                break;
            default:
                throw new Exception('Invalid search type');
        }
        if ($sortAscending) {
            $sortModifier = " ASC";
        } else {
            $sortModifier = " DESC";
        }
        if ($search) {
            $query = 'SELECT * FROM ' . $queryFilter . ' WHERE lower(title) LIKE :search_l OR lower(title) LIKE :search_t ORDER BY ' . $sortField . ' ' . $sortModifier;
        } else {
            $query = 'SELECT * FROM ' . $queryFilter . ' ORDER BY ' . $sortField . ' ' . $sortModifier;
        }
        return $query;
    }

    private function mkBooksCount($queryFilter, $search = false)
    {
        if (!$search) {
            $count = 'SELECT count(*) FROM ' . $queryFilter;
        } else {
            $count = 'SELECT count(*) FROM ' . $queryFilter . ' WHERE lower(title) LIKE :search_l OR lower(title) LIKE :search_t';
        }
        return $count;
    }

    private function mkAuthorsCount($queryFilter, $search = false)
    {
        if (!$search) {
            $count = 'SELECT count(*) FROM authors';
        } else {
            $count = 'SELECT count(*) FROM authors WHERE lower(sort) LIKE :search_l OR lower(sort) LIKE :search_t';
        }
        return $count;
    }

    private function mkTagsCount($queryFilter, $search = false)
    {
        if (!$search) {
            $count = 'SELECT count(*) FROM tags';
        } else {
            $count = 'SELECT count(*) FROM tags WHERE lower(tags.name) LIKE :search_l OR lower(tags.name) LIKE :search_t';
        }
        return $count;
    }

    private function mkSeriesCount($queryFilter, $search = false)
    {
        if (!$search) {
            $count = 'SELECT count(*) FROM series';
        } else {
            $count = 'SELECT count(*) FROM series WHERE lower(name) LIKE :search_l OR lower(name) LIKE :search_t';
        }
        return $count;
    }

    private function mkUserVersion()
    {
        $sql = 'PRAGMA user_version';
        return $sql;
    }

    /**
     * Summary of mkAttachDatabase
     * @param string $dbFileName
     * @param string $attachDatabase
     * @return string
     */
    private function mkAttachDatabase($dbFileName, $attachDatabase)
    {
        $sql = "ATTACH DATABASE '{$dbFileName}' AS {$attachDatabase};";
        return $sql;
    }

    /**
     * Return the number (int) of rows for a SQL COUNT Statement, e.g.
     * SELECT COUNT(*) FROM books;
     *
     * @param string $sql sql query
     * @param array $params query parameters
     * @return int                    number of result rows
     */
    public function count($sql, $params)
    {
        $stmt = $this->calibre->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchColumn();
        if ($result == null || $result == false) {
            return 0;
        } else {
            return (int) $result;
        }
    }

    /**
     * Return the Calibre DB user version
     * @return int
     */
    public function getUserVersion()
    {
        $this->calibre_version ??= $this->count($this->mkUserVersion(), []);
        return $this->calibre_version;
    }

    /**
     * Get item notes from Calibre Notes DB
     * @param string $colname table name
     * @param integer $item item id
     * @return string|false
     */
    public function getItemNotes($colname, $item)
    {
        if (!$this->hasNotes) {
            return false;
        }
        $sql = 'SELECT doc FROM ' . self::NOTES_DB_NAME . '.notes WHERE colname=:colname AND item=:item';
        $params = ['colname' => $colname, 'item' => $item];
        $stmt = $this->calibre->prepare($sql);
        $stmt->execute($params);
        $this->last_error = $stmt->errorCode();
        $result = $stmt->fetchColumn();
        $stmt->closeCursor();
        return $result;
    }

    /**
     * Return the ID for a language code from the Calibre languages table
     * @param string $languageCode ISO 639-2 code, e.g. 'deu', 'eng'
     * @return          ?integer         ID or null
     */
    public function getLanguageId($languageCode)
    {
        $result = $this->calibre->query('SELECT id FROM languages WHERE lang_code = "' . $languageCode . '"')->fetchColumn();
        if ($result == null || $result == false) {
            return null;
        } else {
            return $result;
        }
    }

    /**
     * Return the ID for a tag  from the Calibre tags table
     * @param string    $tagName     textual tag name
     * @return          ?integer     ID or null
     */
    public function getTagId($tagName)
    {
        $result = $this->calibre->query('SELECT id FROM tags WHERE name = "' . $tagName . '"')->fetchColumn();
        if ($result == null || $result == false) {
            return null;
        } else {
            return $result;
        }
    }

    /**
     * Return the most recent books, sorted by modification date.
     * @param string $lang target language code
     * @param  int $nrOfTitles number of titles, page size. Default is 30.
     * @param  object $filter CalibreFilter
     * @return array of books
     * @todo use timestampOrderedTitlesSlice
     */
    public function last30Books($lang, $nrOfTitles = 30, $filter = null)
    {
        $queryParams = $this->mkQueryParams(null, $filter, null, $nrOfTitles, null);
        $books = $this->findPrepared(Book::class, 'SELECT * FROM ' . $filter->getBooksFilter() . ' ORDER BY timestamp DESC LIMIT :length', $queryParams);
        $this->addBookDetails($lang, $books);
        return $books;
    }

    /**
     * Add formatted book language and formats info to a collection of books.
     * book->formats contains the list of available formats as a comma-separated string
     * book->language contains the book's language, only available if the PHP extension 'intl' is installed
     * book->addInfo contains a formatted string with language and formats, e.g. "(English; MOBI,PDF,EPUB)"
     * @param string $lang the target language code for the display
     * @param array $books array of books
     */
    protected function addBookDetails($lang, $books)
    {
        foreach ((array) $books as $book) {
            $fmts = $this->titleGetFormats($book->id);
            $fmtnames = [];
            foreach ($fmts as $format) {
                array_push($fmtnames, $format->format);
            }
            $book->formats = join(',', $fmtnames);
        }
        if (extension_loaded('intl')) {
            foreach ($books as $book) {
                $langcodes = $this->getLanguages($book->id);
                $langtexts = [];
                foreach ($langcodes as $langcode) {
                    $bol = Locale::getDisplayLanguage($langcode, $lang);
                    array_push($langtexts, $bol);
                }
                $book->language = join(',', $langtexts);
            }
        }
        /** @var Book $book */
        foreach ((array) $books as $book) {
            if (empty($book->formats) && !isset($book->language)) {
                $book->addInfo = '';
            } elseif (empty($book->formats) && isset($book->language)) {
                $book->addInfo = '(' . $book->language . ')';
            } elseif (!empty($book->formats) && !isset($book->language)) {
                $book->addInfo = '(' . $book->formats . ')';
            } else {
                $book->addInfo = '(' . $book->language . '; ' . $book->formats . ')';
            }
        }
    }

    /**
     * Return just the pure author information.
     * @param integer $id Calibre ID for author
     * @return ?Author    Calibre author record
     */
    public function author($id)
    {
        return $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', ['id' => $id]);
    }

    /**
     * Find a single author and return the details plus all books.
     * @param  integer $id author id
     * @return ?array            array with elements: author data, books
     */
    public function authorDetails($id)
    {
        $author = $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', ['id' => $id]);
        if (is_null($author)) {
            return null;
        }
        $book_ids = $this->findPrepared(
            BookAuthorLink::class,
            'SELECT * FROM books_authors_link WHERE author=:id',
            ['id' => $id]
        );
        $books = [];
        foreach ($book_ids as $bid) {
            $book = $this->title($bid->book);
            array_push($books, $book);
        }
        return ['author' => $author, 'books' => $books];
    }

    /**
    * Gets a unique list of all series from the author.
    * @param integer $id author id
    * @param array $books array of all books from the author
    */
    public function authorSeries($id, $books)
    {
        $allSeriesIds = [];
        foreach ($books as $book) {
            $series_ids = $this->findPrepared(BookSeriesLink::class, 'SELECT * FROM books_series_link WHERE book=:id', ['id' => $book->id]);
            foreach ($series_ids as $sid) {
                array_push($allSeriesIds, $sid->series);
            }
        }
        $uniqueSeriesIds = array_unique($allSeriesIds);
        $series = [];
        foreach ($uniqueSeriesIds as $sid) {
            $this_series = $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', ['id' => $sid]);
            array_push($series, $this_series);
        }
        return $series;
    }

    /**
     * Find a single author and return the details plus some books.
     *
     * @param  string $lang target language code
     * @param  integer $id author id
     * @param  integer $index page index
     * @param  integer $length page length
     * @param  object $filter CalibreFilter
     * @return ?array           array with elements: author data, current page,
     *                               no. of pages, $length entries
     */
    public function authorDetailsSlice($lang, $id, $index = 0, $length = 100, $filter = null)
    {
        $author = $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', ['id' => $id]);
        if (is_null($author)) {
            return null;
        }
        // get author notes from Calibre Notes DB
        if (empty($index)) {
            $author->note = $this->getItemNotes('authors', $id);
        }
        $slice = $this->findSliceFiltered(CalibreSearchType::AuthorBook, $index, $length, $filter, null, $id);
        $this->addBookDetails($lang, $slice['entries']);
        return ['author' => $author] + $slice;
    }

    /**
     * Search a list of authors defined by the parameters $index and $length.
     * If $search is defined it is used to filter the names, ignoring case.
     *
     * @param  integer $index page index
     * @param  integer $length page length
     * @param  string $search search string
     * @return array                with elements: current page,
     *                      no. of pages, $length entries
     */
    public function authorsSlice($index = 0, $length = 100, $search = null)
    {
        return $this->findSliceFiltered(CalibreSearchType::Author, $index, $length, new CalibreFilter(), $search, null);
    }

    /**
     * Find the initials of all authors and their count
     * @return array an array of Items with initial character and author count
     *
     * Changed thanks to QNAP who insist on publishing outdated libraries in their firmware
     * TODO revert back to real SQL, not the outdated-QNAP stlyle
     */
    public function authorsInitials()
    {
        $initials = $this->findPrepared(
            Initial::class,
            'SELECT DISTINCT substr(upper(sort),1,1) AS initial FROM authors ORDER BY initial ASC',
            []
        );
        $ret = [];
        foreach ($initials as $initial) {
            $i = new Initial();
            $ctr = $this->findOne(ItemCount::class, 'SELECT COUNT(*) as ctr FROM authors WHERE substr(upper(sort),1,1)=:initial', ['initial' => $initial->initial]);
            $i->initial = $initial->initial;
            $i->ctr = $ctr->ctr;
            array_push($ret, $i);
        }
        return $ret;
    }

    /**
     * Find all authors with a given initial and return their names and book count
     * @param  string $initial initial character of last name, uppercase
     * @return array           array of authors with book count
     */
    public function authorsNamesForInitial($initial)
    {
        return $this->findPrepared(
            Author::class,
            'SELECT a.id, a.name, a.sort, (SELECT COUNT(*) FROM books_authors_link AS bal WHERE a.id = bal.author) AS anzahl FROM authors AS a WHERE substr(upper(a.sort),1,1)=:initial ORDER BY a.sort',
            ['initial' => $initial]
        );
    }

    /**
     * Find all ID types in the Calibre identifiers table
     * @return array id type names
     */
    public function idTypes()
    {
        $stmt = $this->calibre->query('SELECT DISTINCT type FROM identifiers');
        $this->last_error = $stmt->errorCode();
        $items = $stmt->fetchAll();
        $stmt->closeCursor();
        return $items;
    }


    /**
     * Return a list of all languages
     */
    public function languages()
    {
        return $this->findPrepared(Language::class, 'SELECT * FROM languages', []);
    }


    /**
     * Return a list of all tags, ordered by name
     */
    public function tags()
    {
        return $this->findPrepared(Tag::class, 'SELECT * FROM tags ORDER BY name', []);
    }

    /**
     * Return just the pure tag information.
     * @param integer $id Calibre ID for tag
     * @return ?Tag    Calibre tag record
     */
    public function tag($id)
    {
        return $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', ['id' => $id]);
    }

    /**
     * Returns a tag and the related books
     * @param  integer $id tag id
     * @return ?array            array with elements: tag data, books
     */
    public function tagDetails($id)
    {
        $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', ['id' => $id]);
        if (is_null($tag)) {
            return null;
        }
        $book_ids = $this->findPrepared(BookTagLink::class, 'SELECT * FROM books_tags_link WHERE tag=:id', ['id' => $id]);
        $books = [];
        foreach ($book_ids as $bid) {
            $book = $this->title($bid->book);
            array_push($books, $book);
        }
        return ['tag' => $tag, 'books' => $books];
    }

    /**
     * Find a single tag and return the details plus some books.
     *
     * @param  string $lang target language code
     * @param  integer $id tagid
     * @param  integer $index page index
     * @param  integer $length page length
     * @param  object $filter CalibreFilter
     * @return ?array           array with elements: tag data, current page,
     *                               no. of pages, $length entries
     */
    public function tagDetailsSlice($lang, $id, $index = 0, $length = 100, $filter = null)
    {
        $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', ['id' => $id]);
        if (is_null($tag)) {
            return null;
        }
        // get tag notes from Calibre Notes DB
        if (empty($index)) {
            $tag->note = $this->getItemNotes('tags', $id);
        }
        $slice = $this->findSliceFiltered(CalibreSearchType::TagBook, $index, $length, $filter, null, $id);
        $this->addBookDetails($lang, $slice['entries']);
        return ['tag' => $tag] + $slice;
    }

    /**
     * Search a list of tags defined by the parameters $index and $length.
     * If $search is defined it is used to filter the tag names, ignoring case.
     * Return an array with elements: current page, no. of pages, $length entries
     */
    public function tagsSlice($index = 0, $length = 100, $search = null)
    {
        return $this->findSliceFiltered(CalibreSearchType::Tag, $index, $length, new CalibreFilter(), $search, null);
    }

    /**
     * Find the initials of all tags and their count
     * @return array an array of Items with initial character and tag count
     *
     * Changed thanks to QNAP who insist on publishing outdated libraries in their firmware
     * TODO revert back to real SQL, not the outdated-QNAP stlyle
     */
    public function tagsInitials()
    {
        $initials = $this->findPrepared(
            Initial::class,
            'SELECT DISTINCT substr(upper(name),1,1) AS initial FROM tags ORDER BY initial ASC',
            []
        );
        $ret = [];
        foreach ($initials as $initial) {
            $i = new Initial();
            $ctr = $this->findOne(ItemCount::class, 'SELECT COUNT(*) as ctr FROM tags WHERE substr(upper(name),1,1)=:initial', ['initial' => $initial->initial]);
            $i->initial = $initial->initial;
            $i->ctr = $ctr->ctr;
            array_push($ret, $i);
        }
        return $ret;
    }

    /**
     * Find all authors with a given initial and return their names and book count
     * @param  string $initial initial character of last name, uppercase
     * @return array           array of authors with book count
     */
    public function tagsNamesForInitial($initial)
    {
        return $this->findPrepared(
            Tag::class,
            'SELECT tags.id, tags.name, (SELECT COUNT(*) FROM books_tags_link AS btl WHERE tags.id = btl.tag ) AS anzahl FROM tags WHERE substr(upper(tags.name),1,1)=:initial ORDER BY tags.name',
            ['initial' => $initial]
        );
    }

    /**
     * Search a list of books in publication date order, defined by the parameters $index and $length.
     * If $search is defined it is used to filter the book title, ignoring case.
     * @param string $lang target language code
     * @param integer $index page index, default 0
     * @param integer $length page length, default 100
     * @param object $filter CalibreFilter
     * @param string $search search phrase, default null
     * @return  array               an array with elements: current page, no. of pages, $length entries
     */
    public function pubdateOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter = null, $search = null)
    {
        $books = $this->findSliceFiltered(CalibreSearchType::PubDateOrderedBook, $index, $length, $filter, $search);
        $this->addBookDetails($lang, $books['entries']);
        return $books;
    }

    /**
     * Search a list of books in last modified order, defined by the parameters $index and $length.
     * If $search is defined it is used to filter the book title, ignoring case.
     * @param string $lang target language code
     * @param integer $index page index, default 0
     * @param integer $length page length, default 100
     * @param object $filter CalibreFilter
     * @param string $search search phrase, default null
     * @return  array               an array with elements: current page, no. of pages, $length entries
     */
    public function lastmodifiedOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter = null, $search = null)
    {
        $books = $this->findSliceFiltered(CalibreSearchType::LastModifiedOrderedBook, $index, $length, $filter, $search);
        $this->addBookDetails($lang, $books['entries']);
        return $books;
    }

    /**
     * Search a list of books in timestamp order, defined by the parameters $index and $length.
     * If $search is defined it is used to filter the book title, ignoring case.
     * @param string $lang target language code
     * @param integer $index page index, default 0
     * @param integer $length page length, default 100
     * @param object $filter CalibreFilter
     * @param string $search search phrase, default null
     * @return  array               an array with elements: current page, no. of pages, $length entries
     */
    public function timestampOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter = null, $search = null)
    {
        $books = $this->findSliceFiltered(CalibreSearchType::TimestampOrderedBook, $index, $length, $filter, $search);
        $this->addBookDetails($lang, $books['entries']);
        return $books;
    }
    /**
     * Search a list of books defined by the parameters $index and $length.
     * If $search is defined it is used to filter the book title, ignoring case.
     * @param string $lang        target language code
     * @param int $index    page index, default 0
     * @param int $length    page length, default 100
     * @param object $filter    CalibreFilter
     * @param string $search    search phrase, default null
     * @return  array          an array with elements: current page, no. of pages, $length entries
     */
    public function titlesSlice($lang, $index = 0, $length = 100, $filter = null, $search = null)
    {
        $books = $this->findSliceFiltered(CalibreSearchType::Book, $index, $length, $filter, $search);
        $this->addBookDetails($lang, $books['entries']);
        return $books;
    }

    /**
     * Find only one book
     */
    public function title($id)
    {
        return $this->findOne(Book::class, 'SELECT * FROM books WHERE id=:id', ['id' => $id]);
    }

    /**
     * Returns the path to the cover image of a book or NULL.
     */
    public function titleCover($id)
    {
        $book = $this->title($id);
        if (is_null($book)) {
            return null;
        } else {
            return CalibreUtil::bookPath($this->calibre_dir, $book->path, 'cover.jpg');
        }
    }


    /**
     * Try to find the language of a book. Returns an emty string, if there is none.
     * @param  int $book_id the Calibre book ID
     * @return string                the language string or an empty string
     **/
    public function getLanguage($book_id)
    {
        $lang_code = null;
        $lang_id = $this->findOne(BookLanguageLink::class, 'SELECT * FROM books_languages_link WHERE book=:id', ['id' => $book_id]);
        if (!is_null($lang_id)) {
            $lang_code = $this->findOne(Language::class, 'SELECT * FROM languages WHERE id=:id', ['id' => $lang_id->lang_code]);
        }
        if (is_null($lang_code)) {
            $lang_text = '';
        } else {
            $lang_text = $lang_code->lang_code;
        }
        return $lang_text;
    }

    /**
     * Try to find the languages of a book. Returns an empty array, if there is none.
     * @param  int $book_id the Calibre book ID
     * @return array                the language strings
     **/
    public function getLanguages($book_id)
    {
        $lang_codes = [];
        $lang_ids = $this->findPrepared(BookLanguageLink::class, 'SELECT * FROM books_languages_link WHERE book=:id', ['id' => $book_id]);
        foreach ($lang_ids as $lang_id) {
            $lang_code = $this->findOne(Language::class, 'SELECT * FROM languages WHERE id=:id', ['id' => $lang_id->lang_code]);
            if (!is_null($lang_code)) {
                array_push($lang_codes, $lang_code->lang_code);
            }
        }
        return $lang_codes;
    }

    /**
     * Find a single book plus all kinds of details.
     * @param  string   $lang   the user's language code
     * @param  int      $id     the Calibre book ID
     * @return ?array            the book, its authors, series, tags, formats, languages, ids and comment.
     */
    public function titleDetails($lang, $id)
    {
        $book = $this->title($id);
        if (is_null($book)) {
            return null;
        }
        $author_ids = $this->findPrepared(
            BookAuthorLink::class,
            'SELECT * FROM books_authors_link WHERE book=:id',
            ['id' => $id]
        );
        $authors = [];
        foreach ($author_ids as $aid) {
            $author = $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', ['id' => $aid->author]);
            array_push($authors, $author);
        }
        $series_ids = $this->findPrepared(
            BookSeriesLink::class,
            'SELECT * FROM books_series_link WHERE book=:id',
            ['id' => $id]
        );
        $series = [];
        foreach ($series_ids as $aid) {
            $this_series = $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', ['id' => $aid->series]);
            array_push($series, $this_series);
        }
        $tag_ids = $this->findPrepared(
            BookTagLink::class,
            'SELECT * FROM books_tags_link WHERE book=:id',
            ['id' => $id]
        );
        $tags = [];
        foreach ($tag_ids as $tid) {
            $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', ['id' => $tid->tag]);
            array_push($tags, $tag);
        }
        $langcodes = $this->getLanguages($id);
        if (extension_loaded('intl')) {
            $langtexts = [];
            foreach ($langcodes as $langcode) {
                $bol = Locale::getDisplayLanguage($langcode, $lang);
                array_push($langtexts, $bol);
            }
            $language = join(', ', $langtexts);
        } else {
            $language = null;
        }
        $formats = $this->findPrepared(
            Data::class,
            'SELECT * FROM data WHERE book=:id',
            ['id' => $id]
        );
        $comment = $this->findOne(Comment::class, 'SELECT * FROM comments WHERE book=:id', ['id' => $id]);
        $ids = $this->findPrepared(
            Identifier::class,
            'SELECT * FROM identifiers WHERE book=:id',
            ['id' => $id]
        );
        if (is_null($comment)) {
            $comment_text = '';
        } else {
            $comment_text = $comment->text;
        }
        $customColumns = $this->customColumns($id);
        return ['book' => $book,
            'authors' => $authors,
            'series' => $series,
            'tags' => $tags,
            'formats' => $formats,
            'comment' => $comment_text,
            'language' => $language,
            'langcodes' => $langcodes,
            'custom' => $customColumns,
            'ids' => $ids];
    }

    /**
     * Find a single book, its tags and languages. Mainly used for restriction checks.
     * @param  int $id the Calibre book ID
     * @return ?array            the book, its tags and languages
     */
    public function titleDetailsMini($id)
    {
        $book = $this->title($id);
        if (is_null($book)) {
            return null;
        }
        $tag_ids = $this->findPrepared(BookTagLink::class, 'SELECT * FROM books_tags_link WHERE book=:id', ['id' => $id]);
        $tags = [];
        foreach ($tag_ids as $tid) {
            $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', ['id' => $tid->tag]);
            array_push($tags, $tag);
        }
        $langcodes = $this->getLanguages($id);
        return ['book' => $book,
            'tags' => $tags,
            'langcodes' => $langcodes];
    }

    /**
     * Add a new cc value. If the key already exists, combine the values with a string join.
     */
    private function addCc($def, $value, $result)
    {
        if (array_key_exists($def->name, $result)) {
            $oldv = $result[$def->name];
            $oldv['value'] = $oldv['value'] . ', ' . $value;
            $result[$def->name] = $oldv;
        } else {
            $result[$def->name] = ['name' => $def->name, 'type' => $def->datatype, 'value' => $value];
        }
        return $result;
    }

    /**
     * Find the custom colums for a book.
     * Composite columns are ignored, because there are (currently?) no values in
     * the db tables.
     * @param  integer $book_id ID of the book
     * @return array                an array of arrays. one entry for each custom column
     *                                with name, type and value
     */
    public function customColumns($book_id)
    {
        $columns = $this->findPrepared(CustomColumns::class, 'SELECT * FROM custom_columns ORDER BY name', []);
        $ccs = [];
        foreach ($columns as $column) {
            $column_id = $column->id;
            if ($column->datatype == 'composite' || $column->datatype == 'series') {
                # composites have no data in the tables; they are template expressions
                # that are apparently evalued dynamically, so we ignore them
                # series contain two data values -- one in the link table, one in the cc table -- handling?
                continue;
            } elseif ($column->datatype == 'text' || $column->datatype == 'enumeration' || $column->datatype == 'rating') {
                # these have extra link tables
                $lvs = $this->findPrepared(
                    BooksCustomColumnLink::class,
                    'SELECT * FROM books_custom_column_' . $column_id . '_link WHERE book=:id',
                    ['id' => $book_id]
                );
                foreach ($lvs as $lv) {
                    $cvs = $this->findPrepared(
                        CustomColumn::class,
                        'SELECT * FROM custom_column_' . $column_id . ' WHERE id=:id',
                        ['id' => $lv->value]
                    );
                    foreach ($cvs as $cv) {
                        $ccs = $this->addCc($column, $cv->value, $ccs);
                    }
                }
            } else {
                # these need just the cc table
                $cvs = $this->findPrepared(
                    CustomColumn::class,
                    'SELECT * FROM custom_column_' . $column_id . ' WHERE book=:id',
                    ['id' => $book_id]
                );
                foreach ($cvs as $cv) {
                    $ccs = $this->addCc($column, $cv->value, $ccs);
                }
            }
        }

        return $ccs;
    }

    /**
     * Find a subset of the details for a book that is sufficient for an OPDS
     * partial acquisition feed. The function assumes that the book record has
     * already been loaded.
     * @param  ?Book $book complete book record from title()
     * @return ?array        the book and its authors, tags and formats
     */
    public function titleDetailsOpds($book)
    {
        if (is_null($book)) {
            return null;
        }
        $author_ids = $this->findPrepared(BookAuthorLink::class, 'SELECT * FROM books_authors_link WHERE book=:id', ['id' => $book->id]);
        $authors = [];
        foreach ($author_ids as $aid) {
            $author = $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', ['id' => $aid->author]);
            array_push($authors, $author);
        }
        $tag_ids = $this->findPrepared(BookTagLink::class, 'SELECT * FROM books_tags_link WHERE book=:id', ['id' => $book->id]);
        $tags = [];
        foreach ($tag_ids as $tid) {
            $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', ['id' => $tid->tag]);
            array_push($tags, $tag);
        }
        $lang_id = $this->findOne(BookLanguageLink::class, 'SELECT * FROM books_languages_link WHERE book=:id', ['id' => $book->id]);
        if (is_null($lang_id)) {
            $lang_text = '';
        } else {
            $lang_code = $this->findOne(Language::class, 'SELECT * FROM languages WHERE id=:id', ['id' => $lang_id->lang_code]);
            if (is_null($lang_code)) {
                $lang_text = '';
            } else {
                $lang_text = $lang_code->lang_code;
            }
        }
        $comment = $this->findOne(Comment::class, 'SELECT * FROM comments WHERE book=:id', ['id' => $book->id]);
        if (is_null($comment)) {
            $comment_text = '';
        } else {
            $comment_text = $comment->text;
        }
        # Strip html excluding the most basic tags and remove all tag attributes
        $comment_text = strip_tags($comment_text, '<div><strong><i><em><b><p><br><br/>');
        $comment_text = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i", '<$1$2>', $comment_text);
        $formats = $this->findPrepared(Data::class, 'SELECT * FROM data WHERE book=:id', ['id' => $book->id]);
        return ['book' => $book, 'authors' => $authors, 'tags' => $tags,
            'formats' => $formats, 'comment' => $comment_text, 'language' => $lang_text];
    }

    /**
     * Retrieve the OPDS title details for a collection of Books and
     * filter out the titles without a downloadable format.
     *
     * This is a utilty function for OPDS, because OPDS acquisition feeds don't
     * valdate if there are entries without acquisition links to downloadable files.
     *
     * @param  array $books a collection of Book instances
     * @return array         the book and its authors, tags and formats
     */
    public function titleDetailsFilteredOpds($books)
    {
        $filtered_books = [];
        foreach ($books as $book) {
            $record = $this->titleDetailsOpds($book);
            if (!empty($record['formats'])) {
                array_push($filtered_books, $record);
            }
        }
        return $filtered_books;
    }

    /**
     * Returns the path to the file of a book or NULL.
     * @param  int $id book id
     * @param  string $file file name
     * @return ?string       full path to image file or NULL
     */
    public function titleFile($id, $file)
    {
        $book = $this->title($id);
        if (is_null($book)) {
            return null;
        } else {
            return CalibreUtil::bookPath($this->calibre_dir, $book->path, $file);
        }
    }

    /**
     * Return the formats for a book
     * @param  int $bookid Calibre book id
     * @return array                the formats for the book
     */
    public function titleGetFormats($bookid)
    {
        return $this->findPrepared(Data::class, 'SELECT * FROM data WHERE book=:id', ['id' => $bookid]);
    }

    /**
     * Returns a Kindle supported format of a book or NULL.
     * We always return the best of the available formats supported by Kindle devices
     * E.g. when there is both a Epub and a PDF file for a given book, we always return the Epub
     * @param  int $id book id
     * @return ?Data   $format    the kindle format object for the book or NULL
     */
    public function titleGetKindleFormat($id)
    {
        $book = $this->title($id);
        if (is_null($book)) {
            return null;
        }
        $formats = $this->findPrepared(
            Data::class,
            "SELECT * FROM data WHERE book=:id AND (format='EPUB' OR format='HTML' OR format='PDF')",
            ['id' => $id]
        );
        if (empty($formats)) {
            return null;
        } else {
            usort($formats, [$this, 'kindleFormatSort']);
            $format = $formats[0];
        }
        return $format;
    }

    /**
     * Return just the pure series information.
     * @param integer $id Calibre ID for series
     * @return ?Series    Calibre series record
     */
    public function series($id)
    {
        return $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', ['id' => $id]);
    }

    /**
     * Find a single series and return the details plus some books.
     *
     * @param  string $lang target language code
     * @param  integer $id series id
     * @param  integer $index page index
     * @param  integer $length page length
     * @param  object $filter CalibreFilter
     * @return ?array           array with elements: series data, current page,
     *                               no. of pages, $length entries
     */
    public function seriesDetailsSlice($lang, $id, $index = 0, $length = 100, $filter = null)
    {
        $series = $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', ['id' => $id]);
        if (is_null($series)) {
            return null;
        }
        // get series notes from Calibre Notes DB
        if (empty($index)) {
            $series->note = $this->getItemNotes('series', $id);
        }
        $slice = $this->findSliceFiltered(CalibreSearchType::SeriesBook, $index, $length, $filter, null, $id);
        $this->addBookDetails($lang, $slice['entries']);
        return ['series' => $series] + $slice;
    }

    /**
     * Search a list of books defined by the parameters $index and $length.
     * If $search is defined it is used to filter the book title, ignoring case.
     * Return an array with elements: current page, no. of pages, $length entries
     *
     * @param  integer $index =0     page indes
     * @param  integer $length =100  page length
     * @param  string $search =NULL search criteria for series name
     * @return array                see findSlice
     */
    public function seriesSlice($index = 0, $length = 100, $search = null)
    {
        return $this->findSliceFiltered(CalibreSearchType::Series, $index, $length, new CalibreFilter(), $search, null);
    }

    /**
     * Find the initials of all series and their number
     * @return array an array of Items with initial character and series count
     *
     * Changed thanks to QNAP who insist on publishing outdated libraries in their firmware
     * TODO revert back to real SQL, not the outdated-QNAP stlyle
     */
    public function seriesInitials()
    {
        $initials = $this->findPrepared(
            Initial::class,
            'SELECT DISTINCT substr(upper(name),1,1) AS initial FROM series ORDER BY initial ASC',
            []
        );
        $ret = [];
        foreach ($initials as $initial) {
            $i = new Initial();
            $ctr = $this->findOne(ItemCount::class, 'SELECT COUNT(*) as ctr FROM series WHERE substr(upper(name),1,1)=:initial', ['initial' => $initial->initial]);
            $i->initial = $initial->initial;
            $i->ctr = $ctr->ctr;
            array_push($ret, $i);
        }
        return $ret;
    }

    /**
     * Find all series with a given initial and return their names and book count
     * @param  string $initial initial character of name, uppercase
     * @return array           array of Series with book count
     */
    public function seriesNamesForInitial($initial)
    {
        if (strcasecmp($initial, "all") == 0) {
            $seriesNames = $this->findPrepared(
                Series::class,
                'SELECT series.id, series.name, (SELECT COUNT(*) FROM books_series_link AS btl WHERE series.id = btl.series) AS anzahl FROM series ORDER BY series.name',
                []
            );
        } else {
            $seriesNames = $this->findPrepared(
                Series::class,
                'SELECT series.id, series.name, (SELECT COUNT(*) FROM books_series_link AS btl WHERE series.id = btl.series) AS anzahl FROM series WHERE substr(upper(series.name),1,1)=:initial ORDER BY series.name',
                ['initial' => $initial]
            );
        }
        return $seriesNames;
    }

    /**
     * Generate a list where the items are grouped and separated by
     * the initial character.
     * If the item has a 'sort' field that is used, else the name.
     */
    public function mkInitialedList($items)
    {
        $grouped_items = [];
        $initial_item = "";
        foreach ($items as $item) {
            if (isset($item->sort)) {
                $is = $item->sort;
            } else {
                $is = $item->name;
            }
            $ix = mb_strtoupper(mb_substr($is, 0, 1, 'UTF-8'), 'UTF-8');
            if ($ix != $initial_item) {
                array_push($grouped_items, ['initial' => $ix]);
                $initial_item = $ix;
            }
            array_push($grouped_items, $item);
        }
        return $grouped_items;
    }

    /**
     * Usort helper function
     * sorts the formats array-of-objects by priority set by kindleformats array
     * @param object $a
     * @param object $b
     * @return int
     */
    public function kindleFormatSort($a, $b)
    {
        //global $kindleformats;
        $kindleformats[0] = "EPUB";
        $kindleformats[1] = "HTML";
        $kindleformats[2] = "PDF";
        $sort = 0;
        foreach ($kindleformats as $key => $value) {
            if ($a->format == $value) {
                $sort = 0;
                break;
            } elseif ($b->format == $value) {
                $sort = 1;
                break;
            }
        }
        return $sort;
    }
}
