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
use BicBucStriim\Utilities\RouteUtil;
use Psr\Http\Message\ResponseInterface as Response;

/*********************************************************************
 * OPDS Catalog actions
 ********************************************************************/
class OpdsActions extends DefaultActions
{
    public const PREFIX = '/opds';

    /**
     * Add routes for OPDS actions
     */
    public static function addRoutes($app, $prefix = self::PREFIX, $gatekeeper = null)
    {
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
            RouteUtil::mapRoutes($group, $routes);
        });
    }

    /**
     * Get routes for OPDS actions
     * @param self|class-string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // name => method(s), path, ...middleware(s), callable
            'opds-home' => ['GET', '/', [$self, 'opdsRoot']],
            'opds-newest' => ['GET', '/newest/', [$self, 'opdsNewest']],
            'opds-title-page' => ['GET', '/titleslist/{page}/', [$self, 'opdsByTitle']],
            'opds-author-initials' => ['GET', '/authorslist/', [$self, 'opdsByAuthorInitial']],
            'opds-author-names' => ['GET', '/authorslist/{initial}/', [$self, 'opdsByAuthorNamesForInitial']],
            'opds-author-page' => ['GET', '/authorslist/{initial}/{id}/{page}/', [$self, 'opdsByAuthor']],
            'opds-tag-initials' => ['GET', '/tagslist/', [$self, 'opdsByTagInitial']],
            'opds-tag-names' => ['GET', '/tagslist/{initial}/', [$self, 'opdsByTagNamesForInitial']],
            'opds-tag-page' => ['GET', '/tagslist/{initial}/{id}/{page}/', [$self, 'opdsByTag']],
            'opds-series-initials' => ['GET', '/serieslist/', [$self, 'opdsBySeriesInitial']],
            'opds-series-names' => ['GET', '/serieslist/{initial}/', [$self, 'opdsBySeriesNamesForInitial']],
            'opds-series-page' => ['GET', '/serieslist/{initial}/{id}/{page}/', [$self, 'opdsBySeries']],
            'opds-opensearch' => ['GET', '/opensearch.xml', [$self, 'opdsSearchDescriptor']],
            'opds-search-page' => ['GET', '/searchlist/{page}/', [$self, 'opdsBySearch']],
            'opds-logout' => ['GET', '/logout/', [$self, 'opdsLogout']],
        ];
    }

    /**
     * Generate and send the OPDS root navigation catalog
     * @return Response
     */
    public function opdsRoot()
    {
        $gen = $this->getOpdsGenerator();
        $cat = $gen->rootCatalog(null);
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_NAV);
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
        $gen = $this->getOpdsGenerator();
        $cat = $gen->newestCatalog(null, $books, false);
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_ACQ);
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
            return $this->badParameter();
        }

        $filter = $this->getFilter();
        $search = $this->requester->get('search');
        if (isset($search)) {
            $tl = $this->calibre()->titlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
        } else {
            $tl = $this->calibre()->titlesSlice($settings['lang'], $page, $settings->page_size, $filter);
        }
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->getOpdsGenerator();
        $cat = $gen->titlesCatalog(
            null,
            $books,
            false,
            $tl['page'],
            $this->getNextSearchPage($tl),
            $this->getLastSearchPage($tl)
        );
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with author names initials
     * @return Response
     */
    public function opdsByAuthorInitial()
    {
        $initials = $this->calibre()->authorsInitials();
        $gen = $this->getOpdsGenerator();
        $cat = $gen->authorsRootCatalog(null, $initials);
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_NAV);
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
            return $this->badParameter();
        }

        $authors = $this->calibre()->authorsNamesForInitial($initial);
        $gen = $this->getOpdsGenerator();
        $cat = $gen->authorsNamesForInitialCatalog(null, $authors, $initial);
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_NAV);
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
            return $this->badParameter();
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->authorDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        $this->log()->debug('opdsByAuthor 1 ' . var_export($tl, true));
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $this->log()->debug('opdsByAuthor 2 ' . var_export($books, true));
        $gen = $this->getOpdsGenerator();
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
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with tag initials
     * @return Response
     */
    public function opdsByTagInitial()
    {
        $initials = $this->calibre()->tagsInitials();
        $gen = $this->getOpdsGenerator();
        $cat = $gen->tagsRootCatalog(null, $initials);
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_NAV);
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
            return $this->badParameter();
        }

        $tags = $this->calibre()->tagsNamesForInitial($initial);
        $gen = $this->getOpdsGenerator();
        $cat = $gen->tagsNamesForInitialCatalog(null, $tags, $initial);
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_NAV);
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
            return $this->badParameter();
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->tagDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->getOpdsGenerator();
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
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Return a page with series initials
     * @return Response
     */
    public function opdsBySeriesInitial()
    {
        $initials = $this->calibre()->seriesInitials();
        $gen = $this->getOpdsGenerator();
        $cat = $gen->seriesRootCatalog(null, $initials);
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_NAV);
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
            return $this->badParameter();
        }

        $tags = $this->calibre()->seriesNamesForInitial($initial);
        $gen = $this->getOpdsGenerator();
        $cat = $gen->seriesNamesForInitialCatalog(null, $tags, $initial);
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_NAV);
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
            return $this->badParameter();
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->seriesDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->getOpdsGenerator();
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
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Format and send the OpenSearch descriptor document
     * @return Response
     */
    public function opdsSearchDescriptor()
    {
        $gen = $this->getOpdsGenerator();
        $cat = $gen->searchDescriptor(null, '/opds/searchlist/0/');
        return $this->responder->opds($cat, OpdsGenerator::OPENSEARCH_MIME);
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
            return $this->badParameter();
        }

        $search = $this->requester->get('search');
        if (!isset($search)) {
            $this->log()->error('opdsBySearch called without search criteria, page ' . $page);
            // 400 Bad request
            return $this->responder->error(400);
        }
        $filter = $this->getFilter();
        $tl = $this->calibre()->titlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
        $books1 = $this->calibre()->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $gen = $this->getOpdsGenerator();
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
        return $this->responder->opds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }

    /**
     * Basic authentication logout for OPDS
     * @return Response
     */
    public function opdsLogout()
    {
        $settings = $this->settings();

        $this->log()->debug('opdsLogout: OPDS logout request');
        if ($this->requester->isAuthenticated()) {
            $username = $this->requester->getUserName();
            $this->log()->debug("logging out user: " . $username);
            $this->container('logout_service')->logout($this->requester->getAuth());
            if ($this->requester->isAuthenticated()) {
                $this->log()->error("error logging out user: " . $username);
            } else {
                $this->log()->info("logged out user: " . $username);
            }
        }
        return $this->responder->authenticate($settings['appname']);
    }

    /*********************************************************************
     * Utility and helper functions, private
     ********************************************************************/

    public function checkThumbnailOpds($record)
    {
        $record['book']->thumbnail = $this->thumbs()->isTitleThumbnailAvailable($record['book']->id);
        return $record;
    }

    /**
     * Initialize the OPDS generator
     * @return OpdsGenerator
     */
    public function getOpdsGenerator()
    {
        $settings = $this->settings();

        $root = $this->requester->getRootUrl();
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
