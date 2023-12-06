<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Actions;

use Utilities;
use OpdsGenerator;

/*********************************************************************
 * OPDS Catalog actions
 ********************************************************************/
class OpdsActions extends DefaultActions
{
    /**
     * Add routes for OPDS actions
     */
    public static function addRoutes($app, $prefix = '/opds')
    {
        $self = new self($app);
        $app->group($prefix, function () use ($app, $self) {
            $app->get('/', [$self, 'opdsRoot']);
            $app->get('/newest/', [$self, 'opdsNewest']);
            $app->get('/titleslist/:id/', [$self, 'opdsByTitle']);
            $app->get('/authorslist/', [$self, 'opdsByAuthorInitial']);
            $app->get('/authorslist/:initial/', [$self, 'opdsByAuthorNamesForInitial']);
            $app->get('/authorslist/:initial/:id/:page/', [$self, 'opdsByAuthor']);
            $app->get('/tagslist/', [$self, 'opdsByTagInitial']);
            $app->get('/tagslist/:initial/', [$self, 'opdsByTagNamesForInitial']);
            $app->get('/tagslist/:initial/:id/:page/', [$self, 'opdsByTag']);
            $app->get('/serieslist/', [$self, 'opdsBySeriesInitial']);
            $app->get('/serieslist/:initial/', [$self, 'opdsBySeriesNamesForInitial']);
            $app->get('/serieslist/:initial/:id/:page/', [$self, 'opdsBySeries']);
            $app->get('/opensearch.xml', [$self, 'opdsSearchDescriptor']);
            $app->get('/searchlist/:id/', [$self, 'opdsBySearch']);
            // @todo either split off titles actions and call here, or adapt partialAcquisitionEntry() in OPDS Generator
            //$app->get('/titles/:id/', [$self, 'title']);
            //$app->get('/titles/:id/cover/', [$self, 'cover']);
            //$app->get('/titles/:id/file/:file', [$self, 'book']);
            //$app->get('/titles/:id/thumbnail/', [$self, 'thumbnail']);
            $app->get('/logout/', [$self, 'opdsLogout']);
        });
    }

    /**
     * Generate and send the OPDS root navigation catalog
     */
    public function opdsRoot()
    {
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->rootCatalog(null);
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Generate and send the OPDS 'newest' catalog. This catalog is an
     * acquisition catalog with a subset of the title details.
     *
     * Note: OPDS acquisition feeds need an acquisition link for every item,
     * so books without formats are removed from the output.
     */
    public function opdsNewest()
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        $filter = $this->getFilter();
        $just_books = $app->calibre->last30Books($globalSettings['lang'], $globalSettings[PAGE_SIZE], $filter);
        $books1 = [];
        foreach ($just_books as $book) {
            $record = $app->calibre->titleDetailsOpds($book);
            if (!empty($record['formats'])) {
                array_push($books1, $record);
            }
        }
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->newestCatalog(null, $books, false);
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page of the titles.
     *
     * Note: OPDS acquisition feeds need an acquisition link for every item,
     * so books without formats are removed from the output.
     *
     * @param  integer $index =0 page index
     */
    public function opdsByTitle($index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($index)) {
            $app->getLog()->warn('opdsByTitle: invalid page id ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $search = $app->request()->get('search');
        if (isset($search)) {
            $tl = $app->calibre->titlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter, $search);
        } else {
            $tl = $app->calibre->titlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter);
        }
        $books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->titlesCatalog(
            null,
            $books,
            false,
            $tl['page'],
            $this->getNextSearchPage($tl),
            $this->getLastSearchPage($tl)
        );
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with author names initials
     */
    public function opdsByAuthorInitial()
    {
        $app = $this->app;

        $initials = $app->calibre->authorsInitials();
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->authorsRootCatalog(null, $initials);
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a page with author names for a initial
     * @param string $initial single uppercase character
     */
    public function opdsByAuthorNamesForInitial($initial)
    {
        $app = $this->app;

        // parameter checking
        if (!(ctype_upper($initial))) {
            $app->getLog()->warn('opdsByAuthorNamesForInitial: invalid initial ' . $initial);
            $app->halt(400, "Bad parameter");
        }

        $authors = $app->calibre->authorsNamesForInitial($initial);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->authorsNamesForInitialCatalog(null, $authors, $initial);
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a feed with partial acquisition entries for the author's books
     * @param  string    $initial initial character
     * @param  int       $id      author id
     * @param  int       $page    page number
     */
    public function opdsByAuthor($initial, $id, $page)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $app->getLog()->warn('opdsByAuthor: invalid author id ' . $id . ' or page id ' . $page);
            $app->halt(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $app->calibre->authorDetailsSlice($globalSettings['lang'], $id, $page, $globalSettings[PAGE_SIZE], $filter);
        $app->getLog()->debug('opdsByAuthor 1 ' . var_export($tl, true));
        $books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $app->getLog()->debug('opdsByAuthor 2 ' . var_export($books, true));
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->booksForAuthorCatalog(
            null,
            $books,
            $initial,
            $tl['author'],
            false,
            $tl['page'],
            $this->getNextSearchPage($tl),
            $this->getLastSearchPage($tl)
        );
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with tag initials
     */
    public function opdsByTagInitial()
    {
        $app = $this->app;

        $initials = $app->calibre->tagsInitials();
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->tagsRootCatalog(null, $initials);
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a page with author names for a initial
     * @param string $initial single uppercase character
     */
    public function opdsByTagNamesForInitial($initial)
    {
        $app = $this->app;

        // parameter checking
        if (!(ctype_upper($initial))) {
            $app->getLog()->warn('opdsByTagNamesForInitial: invalid initial ' . $initial);
            $app->halt(400, "Bad parameter");
        }

        $tags = $app->calibre->tagsNamesForInitial($initial);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->tagsNamesForInitialCatalog(null, $tags, $initial);
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a feed with partial acquisition entries for the tags's books
     * @param  string $initial initial character
     * @param  int $id tag id
     * @param  int $page page index
     */
    public function opdsByTag($initial, $id, $page)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $app->getLog()->warn('opdsByTag: invalid tag id ' . $id . ' or page id ' . $page);
            $app->halt(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $app->calibre->tagDetailsSlice($globalSettings['lang'], $id, $page, $globalSettings[PAGE_SIZE], $filter);
        $books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->booksForTagCatalog(
            null,
            $books,
            $initial,
            $tl['tag'],
            false,
            $tl['page'],
            $this->getNextSearchPage($tl),
            $this->getLastSearchPage($tl)
        );
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with series initials
     */
    public function opdsBySeriesInitial()
    {
        $app = $this->app;

        $initials = $app->calibre->seriesInitials();
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->seriesRootCatalog(null, $initials);
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a page with author names for a initial
     * @param string $initial "all" or single uppercase character
     */
    public function opdsBySeriesNamesForInitial($initial)
    {
        $app = $this->app;

        // parameter checking
        if (!($initial == 'all' || ctype_upper($initial))) {
            $app->getLog()->warn('opdsBySeriesNamesForInitial: invalid initial ' . $initial);
            $app->halt(400, "Bad parameter");
        }

        $tags = $app->calibre->seriesNamesForInitial($initial);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->seriesNamesForInitialCatalog(null, $tags, $initial);
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a feed with partial acquisition entries for the series' books
     * @param  string    $initial initial character
     * @param  int       $id        tag id
     * @param  int       $page    page index
     */
    public function opdsBySeries($initial, $id, $page)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $app->getLog()->warn('opdsBySeries: invalid series id ' . $id . ' or page id ' . $page);
            $app->halt(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $app->calibre->seriesDetailsSlice($globalSettings['lang'], $id, $page, $globalSettings[PAGE_SIZE], $filter);
        $books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->booksForSeriesCatalog(
            null,
            $books,
            $initial,
            $tl['series'],
            false,
            $tl['page'],
            $this->getNextSearchPage($tl),
            $this->getLastSearchPage($tl)
        );
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Format and send the OpenSearch descriptor document
     */
    public function opdsSearchDescriptor()
    {
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->searchDescriptor(null, '/opds/searchlist/0/');
        $this->mkOpdsResponse($cat, OpdsGenerator::OPENSEARCH_MIME);
    }

    /**
     * Create and send the catalog page for the current search criteria.
     * The search criteria is a GET paramter string.
     *
     * @param  integer $index index of page in search
     */
    public function opdsBySearch($index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($index)) {
            $app->getLog()->warn('opdsBySearch: invalid page id ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $search = $app->request()->get('search');
        if (!isset($search)) {
            $app->getLog()->error('opdsBySearch called without search criteria, page ' . $index);
            // 400 Bad request
            $app->response()->setStatus(400);
            return;
        }
        $filter = $this->getFilter();
        $tl = $app->calibre->titlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter, $search);
        $books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->searchCatalog(
            null,
            $books,
            false,
            $tl['page'],
            $this->getNextSearchPage($tl),
            $this->getLastSearchPage($tl),
            $search,
            $tl['total'],
            $globalSettings[PAGE_SIZE]
        );
        $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Basic authentication logout for OPDS
     */
    public function opdsLogout()
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        $app->getLog()->debug('opdsLogout: OPDS logout request');
        if ($this->is_authenticated()) {
            $username = $app->auth->getUserName();
            $app->getLog()->debug("logging out user: " . $username);
            $app->logout_service->logout($app->auth);
            if ($this->is_authenticated()) {
                $app->getLog()->error("error logging out user: " . $username);
            } else {
                $app->getLog()->info("logged out user: " . $username);
            }
        }
        $app->response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', $globalSettings['appname']));
        $app->halt(401, 'Please authenticate');
    }

    /*********************************************************************
     * Utility and helper functions, private
     ********************************************************************/

    public function checkThumbnailOpds($record)
    {
        $app = $this->app;
        $record['book']->thumbnail = $app->bbs->isTitleThumbnailAvailable($record['book']->id);
        return $record;
    }

    /**
     * Initialize the OPDS generator
     */
    public function mkOpdsGenerator()
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        $root = Utilities::getRootUrl($app);
        $gen = new OpdsGenerator(
            $root,
            $globalSettings['version'],
            $app->calibre->calibre_dir,
            date(DATE_ATOM, $app->calibre->calibre_last_modified),
            $globalSettings['l10n']
        );
        return $gen;
    }

    /**
     * Create and send the typical OPDS response
     */
    public function mkOpdsResponse($content, $type)
    {
        $app = $this->app;
        $resp = $app->response();
        $resp->setStatus(200);
        $resp->headers->set('Content-type', $type);
        $resp->headers->set('Content-Length', strlen($content));
        $resp->setBody($content);
    }


    /**
     * Calculate the next page number for search results
     * @param  array $tl search result
     * @return int       page index or NULL
     */
    public function getNextSearchPage($tl)
    {
        if ($tl['page'] < $tl['pages'] - 1) {
            $nextPage = $tl['page'] + 1;
        } else {
            $nextPage = null;
        }
        return $nextPage;
    }

    /**
     * Calculate the last page numberfor search results
     * @param  array $tl search result
     * @return int            page index
     */
    public function getLastSearchPage($tl)
    {
        if ($tl['pages'] == 0) {
            $lastPage = 0;
        } else {
            $lastPage = $tl['pages'] - 1;
        }
        return $lastPage;
    }
}
