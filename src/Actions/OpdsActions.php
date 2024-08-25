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

use BicBucStriim\Utilities\OpdsGenerator;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\RouteUtil;
use Psr\Http\Message\ResponseInterface as Response;

/*********************************************************************
 * OPDS Catalog actions
 ********************************************************************/
class OpdsActions extends DefaultActions
{
    /**
     * Add routes for OPDS actions
     */
    public static function addRoutes($app, $prefix = '/opds', $gatekeeper = null)
    {
        //$self = new self($app);
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
            RouteUtil::mapRoutes($group, $routes);
        });
    }

    /**
     * Get routes for OPDS actions
     * @param self|string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // method(s), path, ...middleware(s), callable
            ['GET', '/', [$self, 'opdsRoot']],
            ['GET', '/newest/', [$self, 'opdsNewest']],
            ['GET', '/titleslist/{page}/', [$self, 'opdsByTitle']],
            ['GET', '/authorslist/', [$self, 'opdsByAuthorInitial']],
            ['GET', '/authorslist/{initial}/', [$self, 'opdsByAuthorNamesForInitial']],
            ['GET', '/authorslist/{initial}/{id}/{page}/', [$self, 'opdsByAuthor']],
            ['GET', '/tagslist/', [$self, 'opdsByTagInitial']],
            ['GET', '/tagslist/{initial}/', [$self, 'opdsByTagNamesForInitial']],
            ['GET', '/tagslist/{initial}/{id}/{page}/', [$self, 'opdsByTag']],
            ['GET', '/serieslist/', [$self, 'opdsBySeriesInitial']],
            ['GET', '/serieslist/{initial}/', [$self, 'opdsBySeriesNamesForInitial']],
            ['GET', '/serieslist/{initial}/{id}/{page}/', [$self, 'opdsBySeries']],
            ['GET', '/opensearch.xml', [$self, 'opdsSearchDescriptor']],
            ['GET', '/searchlist/{page}/', [$self, 'opdsBySearch']],
            // @todo either split off titles actions and call here, or adapt partialAcquisitionEntry() in OPDS Generator
            //['GET', '/titles/{id}/', [$self, 'title']],
            //['GET', '/titles/{id}/cover/', [$self, 'cover']],
            //['GET', '/titles/{id}/file/{file}', [$self, 'book']],
            //['GET', '/titles/{id}/thumbnail/', [$self, 'thumbnail']],
            ['GET', '/logout/', [$self, 'opdsLogout']],
        ];
    }

    /**
     * Generate and send the OPDS root navigation catalog
     * @return Response
     */
    public function opdsRoot()
    {
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->rootCatalog(null);
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Generate and send the OPDS 'newest' catalog. This catalog is an
     * acquisition catalog with a subset of the title details.
     *
     * Note: OPDS acquisition feeds need an acquisition link for every item,
     * so books without formats are removed from the output.
     * @return Response
     */
    public function opdsNewest()
    {
        $settings = $this->settings();

        $filter = $this->getFilter();
        $just_books = $this->calibre()->last30Books($settings['lang'], $settings->page_size, $filter);
        $books1 = [];
        foreach ($just_books as $book) {
            $record = $this->calibre()->titleDetailsOpds($book);
            if (!empty($record['formats'])) {
                array_push($books1, $record);
            }
        }
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->newestCatalog(null, $books, false);
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page of the titles.
     *
     * Note: OPDS acquisition feeds need an acquisition link for every item,
     * so books without formats are removed from the output.
     *
     * @param  integer $page =0 page index
     * @return Response
     */
    public function opdsByTitle($page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($page)) {
            $this->log()->warning('opdsByTitle: invalid page id ' . $page);
            return $this->mkError(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $search = $this->get('search');
        if (isset($search)) {
            $tl = $this->calibre()->titlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
        } else {
            $tl = $this->calibre()->titlesSlice($settings['lang'], $page, $settings->page_size, $filter);
        }
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
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
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with author names initials
     * @return Response
     */
    public function opdsByAuthorInitial()
    {
        $initials = $this->calibre()->authorsInitials();
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->authorsRootCatalog(null, $initials);
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a page with author names for a initial
     * @param string $initial single uppercase character
     * @return Response
     */
    public function opdsByAuthorNamesForInitial($initial)
    {
        // parameter checking
        if (!(ctype_upper($initial))) {
            $this->log()->warning('opdsByAuthorNamesForInitial: invalid initial ' . $initial);
            return $this->mkError(400, "Bad parameter");
        }

        $authors = $this->calibre()->authorsNamesForInitial($initial);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->authorsNamesForInitialCatalog(null, $authors, $initial);
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a feed with partial acquisition entries for the author's books
     * @param  string    $initial initial character
     * @param  int       $id      author id
     * @param  int       $page    page number
     * @return Response
     */
    public function opdsByAuthor($initial, $id, $page)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->log()->warning('opdsByAuthor: invalid author id ' . $id . ' or page id ' . $page);
            return $this->mkError(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->authorDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        $this->log()->debug('opdsByAuthor 1 ' . var_export($tl, true));
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $this->log()->debug('opdsByAuthor 2 ' . var_export($books, true));
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
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with tag initials
     * @return Response
     */
    public function opdsByTagInitial()
    {
        $initials = $this->calibre()->tagsInitials();
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->tagsRootCatalog(null, $initials);
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a page with author names for a initial
     * @param string $initial single uppercase character
     * @return Response
     */
    public function opdsByTagNamesForInitial($initial)
    {
        // parameter checking
        if (!(ctype_upper($initial))) {
            $this->log()->warning('opdsByTagNamesForInitial: invalid initial ' . $initial);
            return $this->mkError(400, "Bad parameter");
        }

        $tags = $this->calibre()->tagsNamesForInitial($initial);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->tagsNamesForInitialCatalog(null, $tags, $initial);
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a feed with partial acquisition entries for the tags's books
     * @param  string $initial initial character
     * @param  int $id tag id
     * @param  int $page page index
     * @return Response
     */
    public function opdsByTag($initial, $id, $page)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->log()->warning('opdsByTag: invalid tag id ' . $id . ' or page id ' . $page);
            return $this->mkError(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->tagDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
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
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with series initials
     * @return Response
     */
    public function opdsBySeriesInitial()
    {
        $initials = $this->calibre()->seriesInitials();
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->seriesRootCatalog(null, $initials);
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a page with author names for a initial
     * @param string $initial "all" or single uppercase character
     * @return Response
     */
    public function opdsBySeriesNamesForInitial($initial)
    {
        // parameter checking
        if (!($initial == 'all' || ctype_upper($initial))) {
            $this->log()->warning('opdsBySeriesNamesForInitial: invalid initial ' . $initial);
            return $this->mkError(400, "Bad parameter");
        }

        $tags = $this->calibre()->seriesNamesForInitial($initial);
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->seriesNamesForInitialCatalog(null, $tags, $initial);
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_NAV);
    }

    /**
     * Return a feed with partial acquisition entries for the series' books
     * @param  string    $initial initial character
     * @param  int       $id        tag id
     * @param  int       $page    page index
     * @return Response
     */
    public function opdsBySeries($initial, $id, $page)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->log()->warning('opdsBySeries: invalid series id ' . $id . ' or page id ' . $page);
            return $this->mkError(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->seriesDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
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
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Format and send the OpenSearch descriptor document
     * @return Response
     */
    public function opdsSearchDescriptor()
    {
        $gen = $this->mkOpdsGenerator();
        $cat = $gen->searchDescriptor(null, '/opds/searchlist/0/');
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPENSEARCH_MIME);
    }

    /**
     * Create and send the catalog page for the current search criteria.
     * The search criteria is a GET paramter string.
     *
     * @param  integer $page index of page in search
     * @return Response
     */
    public function opdsBySearch($page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($page)) {
            $this->log()->warning('opdsBySearch: invalid page id ' . $page);
            return $this->mkError(400, "Bad parameter");
        }

        $search = $this->get('search');
        if (!isset($search)) {
            $this->log()->error('opdsBySearch called without search criteria, page ' . $page);
            // 400 Bad request
            return $this->mkError(400);
        }
        $filter = $this->getFilter();
        $tl = $this->calibre()->titlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
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
            $settings->page_size
        );
        return $this->mkOpdsResponse($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Basic authentication logout for OPDS
     * @return Response
     */
    public function opdsLogout()
    {
        $settings = $this->settings();

        $this->log()->debug('opdsLogout: OPDS logout request');
        if ($this->is_authenticated()) {
            $username = $this->getAuth()->getUserName();
            $this->log()->debug("logging out user: " . $username);
            $this->container('logout_service')->logout($this->getAuth());
            if ($this->is_authenticated()) {
                $this->log()->error("error logging out user: " . $username);
            } else {
                $this->log()->info("logged out user: " . $username);
            }
        }
        return $this->mkAuthenticate($settings['appname']);
    }

    /*********************************************************************
     * Utility and helper functions, private
     ********************************************************************/

    public function checkThumbnailOpds($record)
    {
        $record['book']->thumbnail = $this->bbs()->isTitleThumbnailAvailable($record['book']->id);
        return $record;
    }

    /**
     * Initialize the OPDS generator
     * @return OpdsGenerator
     */
    public function mkOpdsGenerator()
    {
        $settings = $this->settings();

        $requestUtil = new RequestUtil($this->request, $this->settings());
        $root = $requestUtil->getRootUrl();
        $gen = new OpdsGenerator(
            $root,
            $settings['version'],
            $this->calibre()->calibre_dir,
            date(DATE_ATOM, $this->calibre()->calibre_last_modified),
            $settings['l10n']
        );
        return $gen;
    }

    /**
     * Create and send the typical OPDS response
     * @return Response
     */
    public function mkOpdsResponse($content, $type, $status = 200)
    {
        return $this->mkResponse($content, $type, $status);
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
