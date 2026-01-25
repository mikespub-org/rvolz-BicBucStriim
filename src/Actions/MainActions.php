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

use BicBucStriim\AppData\Settings;
use BicBucStriim\Calibre\Author;
use BicBucStriim\Utilities\CalibreUtil;
use BicBucStriim\Utilities\InputUtil;
use BicBucStriim\Utilities\MetadataEpub;
use BicBucStriim\Utilities\RouteUtil;
use Michelf\MarkdownExtra;
use Psr\Http\Message\ResponseInterface as Response;
use Exception;
use Throwable;
use Twig\TwigFilter;

/*********************************************************************
 * Main actions
 ********************************************************************/
class MainActions extends DefaultActions
{
    public const PREFIX = null;

    /**
     * Add routes for main actions
     */
    public static function addRoutes($app, $prefix = self::PREFIX, $gatekeeper = null)
    {
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        RouteUtil::mapRoutes($app, $routes);
    }

    /**
     * Get routes for main actions
     * @param self|class-string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // name => method(s), path, ...middleware(s), callable
            'main-home' => ['GET', '/', [$self, 'main']],
            'main-login' => ['GET', '/login/', [$self, 'showLogin']],
            'main-login-post' => ['POST', '/login/', [$self, 'performLogin']],
            'main-logout' => ['GET', '/logout/', [$self, 'logout']],
            // use $gatekeeper for individual routes here - @todo move to MetadataActions?
            'main-author-note' => ['GET', '/authors/{id}/notes/', $gatekeeper, [$self, 'authorNotes']],
            'main-author-v1' => ['GET', '/authors/{id}/{page}/', [$self, 'authorDetailsSlice']],
            'main-authors-v1' => ['GET', '/authorslist/{page}/', [$self, 'authorsSlice']],
            'main-search' => ['GET', '/search/', [$self, 'globalSearch']],
            'main-search-post' => ['POST', '/search/', [$self, 'globalSearch']],
            'main-serie-v1' => ['GET', '/series/{id}/{page}/', [$self, 'seriesDetailsSlice']],
            'main-series-v1' => ['GET', '/serieslist/{page}/', [$self, 'seriesSlice']],
            'main-tag-v1' => ['GET', '/tags/{id}/{page}/', [$self, 'tagDetailsSlice']],
            'main-tags-v1' => ['GET', '/tagslist/{page}/', [$self, 'tagsSlice']],
            'main-title' => ['GET', '/titles/{id}/', [$self, 'title']],
            'main-cover-v1' => ['GET', '/titles/{id}/cover/', [$self, 'cover']],
            'main-book' => ['GET', '/titles/{id}/file/{file}', [$self, 'book']],
            'main-kindle' => ['POST', '/titles/{id}/kindle/{file}', [$self, 'kindle']],
            'main-thumbnail-v1' => ['GET', '/titles/{id}/thumbnail/', [$self, 'thumbnail']],
            'main-titles-v1' => ['GET', '/titleslist/{page}/', [$self, 'titlesSlice']],
            // temporary routes for the tailwind templates (= based on the v2.x frontend)
            'main-authors-v2' => ['GET', '/authors/', [$self, 'authorsSlice']],
            'main-author-v2' => ['GET', '/authors/{id}/', [$self, 'authorDetailsSlice']],
            'main-series-v2' => ['GET', '/series/', [$self, 'seriesSlice']],
            'main-serie-v2' => ['GET', '/series/{id}/', [$self, 'seriesDetailsSlice']],
            'main-tags-v2' => ['GET', '/tags/', [$self, 'tagsSlice']],
            'main-tag-v2' => ['GET', '/tags/{id}/', [$self, 'tagDetailsSlice']],
            'main-titles-v2' => ['GET', '/titles/', [$self, 'titlesSlice']],
            'main-cover-v2' => ['GET', '/static/covers/{id}/', [$self, 'cover']],
            'main-thumbnail-v2' => ['GET', '/static/titlethumbs/{id}/', [$self, 'thumbnail']],
            // @todo handle jumpTarget for author/series/tag slice if we want to support this
            'params-scope-type' => ['GET', '/params/{scope}/{type}/', [$self, 'getTailwindParams']],
        ];
    }

    /**
     * 404 Not Found page for invalid URLs
     * @return Response
     */
    public function notFound()
    {
        return $this->render('error.twig', [
            'page' => $this->buildPage('not_found1'),
            'title' => $this->getMessageString('not_found1'),
            'error' => $this->getMessageString('not_found2')]);
    }

    /**
     * Show login page
     * @return Response
     */
    public function showLogin()
    {
        if ($this->requester->isAuthenticated()) {
            $this->log()->info('user is already logged in : ' . $this->requester->getUserName());
            return $this->responder->redirect($this->requester->getBasePath() . '/');
        } else {
            return $this->render('login.twig', [
                'page' => $this->buildPage('login')]);
        }
    }

    /**
     * Perform login page
     * @return Response
     */
    public function performLogin()
    {
        $login_data = $this->requester->post();
        $this->log()->debug('login: ' . var_export($login_data, true));
        if (isset($login_data['username']) && isset($login_data['password'])) {
            $uname = $login_data['username'];
            $upw = $login_data['password'];
            if (empty($uname) || empty($upw)) {
                return $this->render('login.twig', [
                    'page' => $this->buildPage('login')]);
            } else {
                try {
                    $authService = $this->getAuthService();
                    $authService->login($this->requester->getAuth(), ['username' => $uname, 'password' => $upw]);
                    $success = $this->requester->getAuth()->getStatus();
                    $this->log()->debug('login success: ' . $success);
                    if ($this->requester->isAuthenticated()) {
                        $this->log()->info('logged in user : ' . $this->requester->getUserName());
                        return $this->responder->redirect($this->requester->getBasePath() . '/');
                    }
                } catch (Exception $e) {
                    $this->log()->error('error logging in user : ' . $e->getMessage());
                }
                $this->log()->error('error logging in user : ' . $login_data['username']);
                return $this->render('login.twig', [
                    'page' => $this->buildPage('login')]);
            }
        } else {
            return $this->render('login.twig', [
                'page' => $this->buildPage('login')]);
        }
    }

    /**
     * Logout page
     * @return Response
     */
    public function logout()
    {
        if ($this->requester->isAuthenticated()) {
            $username = $this->requester->getUserName();
            $this->log()->debug("logging out user: " . $username);
            $authService = $this->getAuthService();
            $authService->logout($this->requester->getAuth());
            // @phpstan-ignore if.alwaysTrue
            if ($this->requester->isAuthenticated()) {
                $this->log()->error("error logging out user: " . $username);
            } else {
                $this->log()->info("logged out user: " . $username);
            }
        }
        return $this->render('logout.twig', [
            'page' => $this->buildPage('logout')]);
    }

    /*********************************************************************
     * HTML presentation functions
     ********************************************************************/

    /**
     * Generate the main page with the 30 most recent titles
     * @return Response
     */
    public function main()
    {
        $settings = $this->settings();

        $filter = $this->getFilter();
        $books1 = $this->calibre()->last30Books($settings['lang'], $settings->page_size, $filter);
        $books = array_map([$this, 'checkThumbnail'], $books1);
        $stats = $this->calibre()->libraryStats($filter);
        $outdated = '';
        $version = $this->calibre()::USER_VERSION;
        if (!empty($stats['version']) && $stats['version'] < $version) {
            $outdated = sprintf($this->getMessageString('admin_new_version'), $version, $stats['version']);
        }
        return $this->render('index_last30.twig', [
            'page' => $this->buildPage('dl30', 1, 1),
            'books' => $books,
            'stats' => $stats,
            'outdated' => $outdated,
        ]);
    }

    /**
     * Make a search over all categories. Returns only the first PAGES_SIZE items per category.
     * If there are more entries per category, there will be a link to the full results.
     * @return Response
     */
    public function globalSearch()
    {
        $settings = $this->settings();

        // TODO check search paramater?

        $filter = $this->getFilter();
        $search = $this->requester->get('search') ?? $this->requester->post('search') ?? '';
        if ($search == '') {
            return $this->render('global_search.twig', [
                'page' => $this->buildPage('pagination_search', 0),
                'search' => $search]);
        }
        $tlb = $this->calibre()->titlesSlice($settings['lang'], 0, $settings->page_size, $filter, trim($search));
        $tlb_books = array_map([$this, 'checkThumbnail'], $tlb['entries']);
        $tla = $this->calibre()->authorsSlice(0, $settings->page_size, trim($search));
        $tla_books = array_map([$this, 'checkThumbnail'], $tla['entries']);
        $tlt = $this->calibre()->tagsSlice(0, $settings->page_size, trim($search));
        $tlt_books = array_map([$this, 'checkThumbnail'], $tlt['entries']);
        $tls = $this->calibre()->seriesSlice(0, $settings->page_size, trim($search));
        $tls_books = array_map([$this, 'checkThumbnail'], $tls['entries']);
        return $this->render('global_search_result.twig', [
            'page' => $this->buildPage('pagination_search', 0),
            'books' => $tlb_books,
            'books_total' => $tlb['total'] == -1 ? 0 : $tlb['total'],
            'more_books' => ($tlb['total'] > $settings->page_size),
            'authors' => $tla_books,
            'authors_total' => $tla['total'] == -1 ? 0 : $tla['total'],
            'more_authors' => ($tla['total'] > $settings->page_size),
            'tags' => $tlt_books,
            'tags_total' => $tlt['total'] == -1 ? 0 : $tlt['total'],
            'more_tags' => ($tlt['total'] > $settings->page_size),
            'series' => $tls_books,
            'series_total' => $tls['total'] == -1 ? 0 : $tls['total'],
            'more_series' => ($tls['total'] > $settings->page_size),
            'search' => $search]);
    }

    /**
     * A list of titles at $page -> /titleslist/{page}
     * @return Response
     */
    public function titlesSlice($page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($page)) {
            $this->log()->warning('titlesSlice: invalid page id ' . $page);
            return $this->badParameter();
        }

        $filter = $this->getFilter();
        $search = $this->requester->get('search');
        if (isset($search)) {
            $search = trim($search);
        }
        $sort = $this->requester->get('sort');

        if (isset($sort) && $sort == 'byReverseDate') {
            switch ($settings->title_time_sort) {
                case Settings::TITLE_TIME_SORT_TIMESTAMP:
                    $tl = $this->calibre()->timestampOrderedTitlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
                    break;
                case Settings::TITLE_TIME_SORT_PUBDATE:
                    $tl = $this->calibre()->pubdateOrderedTitlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
                    break;
                case Settings::TITLE_TIME_SORT_LASTMODIFIED:
                    $tl = $this->calibre()->lastmodifiedOrderedTitlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
                    break;
                default:
                    $this->log()->error('titlesSlice: invalid sort order ' . $settings->title_time_sort);
                    $tl = $this->calibre()->timestampOrderedTitlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
                    break;
            }
        } else {
            $tl = $this->calibre()->titlesSlice($settings['lang'], $page, $settings->page_size, $filter, $search);
        }

        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);
        return $this->render('titles.twig', [
            'page' => $this->buildPage('titles', 2, 1),
            'url' => 'titleslist',
            'url_v2' => 'titles',
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search,
            'sort' => $sort]);
    }

    /**
     * Creates a human readable filesize string
     * @return string
     */
    public function humanFilesize($bytes, $decimals = 0)
    {
        $size = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
        $factor = (int) floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    /**
     * Show a single title > /titles/{id}/ The ID ist the Calibre ID
     * @return Response
     */
    public function title($id)
    {
        $settings = $this->settings();

        // Add a function to the renderer for human-readable file sizes
        $this->renderer()->addFunction('hfsize', [$this, 'humanFilesize']);

        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('title: invalid title id ' . $id);
            return $this->badParameter();
        }

        $details = $this->calibre()->titleDetails($settings['lang'], $id);
        if (is_null($details)) {
            $this->log()->warning("title: book not found: " . $id);
            return $this->notFound();
        }
        // for people trying to circumvent filtering by direct access
        if ($this->titleForbidden($details)) {
            $this->log()->warning("title: requested book not allowed for user: " . $id);
            return $this->notFound();
        }
        // Show ID links only if there are templates and ID data
        $idtemplates = $this->bbs()->idTemplates();
        $id_tmpls = [];
        if (count($idtemplates) > 0 && count($details['ids']) > 0) {
            $show_idlinks = true;
            foreach ($idtemplates as $idtemplate) {
                $id_tmpls[$idtemplate->name] = [$idtemplate->val, $idtemplate->label];
            }
        } else {
            $show_idlinks = false;
        }
        $kindle_format = ($settings->kindle == 1) ? $this->calibre()->titleGetKindleFormat($id) : null;
        $this->log()->debug('titleDetails custom columns: ' . count($details['custom']));
        return $this->render(
            'title_detail.twig',
            ['page' => $this->buildPage('book_details', 2, 2),
                'book' => $details['book'],
                'authors' => $details['authors'],
                'series' => $details['series'],
                'tags' => $details['tags'],
                'formats' => $details['formats'],
                'comment' => $details['comment'],
                'language' => $details['language'],
                'ccs' => (count($details['custom']) > 0 ? $details['custom'] : null),
                'show_idlinks' => $show_idlinks,
                'ids' => $details['ids'],
                'id_templates' => $id_tmpls,
                'kindle_format' => $kindle_format,
                'kindle_from_email' => $settings->kindle_from_email,
                'base_url' => $this->requester->getRootUrl(true),
                'protect_dl' => false]
        );
    }

    /**
     * Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
     * If there is no cover, return 404.
     * Route: /titles/{id}/cover/
     * @return Response
     */
    public function cover($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('cover: invalid title id ' . $id);
            return $this->badParameter();
        }

        $has_cover = false;
        $book = $this->calibre()->title($id);
        if (is_null($book)) {
            $this->log()->debug("cover: book not found: " . $id);
            return $this->responder->error(404);
        }

        if ($book->has_cover) {
            $cover = $this->calibre()->titleCover($id);
            $has_cover = true;
        }
        if ($has_cover) {
            return $this->responder->file($cover, 'image/jpeg;base64');
        } else {
            return $this->responder->error(404);
        }
    }

    /**
     * Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
     * If there is no cover, return 404.
     * Route: /titles/{id}/thumbnail/
     * @return Response
     */
    public function thumbnail($id)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('thumbnail: invalid title id ' . $id);
            return $this->badParameter();
        }

        $this->log()->debug('thumbnail: ' . $id);
        $has_cover = false;
        $book = $this->calibre()->title($id);
        if (is_null($book)) {
            $this->log()->error("thumbnail: book not found: " . $id);
            return $this->responder->error(404);
        }

        if ($book->has_cover) {
            $cover = $this->calibre()->titleCover($id);
            $thumb = $this->thumbs()->titleThumbnail($id, $cover, $settings->thumb_gen_clipped);
            $this->log()->debug('thumbnail: thumb found ' . $thumb);
            $has_cover = true;
        }
        if ($has_cover) {
            return $this->responder->file($thumb, 'image/png;base64');
        } else {
            return $this->responder->error(404);
        }
    }

    /**
     * Return the selected file for the book with ID.
     * Route: /titles/{id}/file/{file}
     * @return Response
     */
    public function book($id, $file)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('book: invalid title id ' . $id);
            return $this->badParameter();
        }
        // TODO check file parameter?
        $file = basename($file);

        $details = $this->calibre()->titleDetails($settings['lang'], $id);
        if (is_null($details)) {
            $this->log()->warning("book: no book found for " . $id);
            return $this->notFound();
        }
        // for people trying to circumvent filtering by direct access
        if ($this->titleForbidden($details)) {
            $this->log()->warning("book: requested book not allowed for user: " . $id);
            return $this->notFound();
        }

        $real_bookpath = $this->calibre()->titleFile($id, $file);
        if (!file_exists($real_bookpath)) {
            $this->log()->warning("book: no file found for book " . $id . " " . $file);
            return $this->notFound();
        }
        $contentType = CalibreUtil::titleMimeType($real_bookpath);
        if ($this->requester->isAuthenticated()) {
            $this->log()->info("book download by " . $this->requester->getUserName() . " for " . $real_bookpath
                . " with metadata update = " . $settings->metadata_update);
        } else {
            $this->log()->info("book download for " . $real_bookpath
                . " with metadata update = " . $settings->metadata_update);
        }
        if ($contentType == CalibreUtil::MIME_EPUB && $settings->metadata_update) {
            if ($details['book']->has_cover == 1) {
                $cover = $this->calibre()->titleCover($id);
            } else {
                $cover = null;
            }
            // If an EPUB update the metadata
            $mdep = new MetadataEpub($real_bookpath);
            $mdep->updateMetadata($details, $cover);
            $bookpath = $mdep->getUpdatedFile();
            $this->log()->debug("book(e): file " . $bookpath);
            $this->log()->debug("book(e): type " . $contentType);
            $booksize = filesize($bookpath);
            $this->log()->debug("book(e): size " . $booksize);
            return $this->responder->download($bookpath, $contentType, $file);
        } else {
            // Else send the file as is
            $bookpath = $real_bookpath;
            $this->log()->debug("book: file " . $bookpath);
            $this->log()->debug("book: type " . $contentType);
            $booksize = filesize($bookpath);
            $this->log()->debug("book: size " . $booksize);
            return $this->responder->download($bookpath, $contentType, $file);
        }
    }


    /**
     * Send the selected file to a Kindle e-mail address
     * Route: /titles/{id}/kindle/{file}
     * @return Response
     */
    public function kindle($id, $file)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('kindle: invalid title id ' . $id);
            return $this->badParameter();
        }
        // TODO check file parameter?
        $file = basename($file);

        $book = $this->calibre()->title($id);

        if (is_null($book)) {
            $this->log()->debug("kindle: book not found: " . $id);
            return $this->notFound();
        }

        $details = $this->calibre()->titleDetails($settings['lang'], $id);
        $filename = "";
        if ($details['series'] != null) {
            $filename .= $details['series'][0]->name;
            $filename .= "[" . $details['book']->series_index . "] ";
        }
        $filename .= $details['book']->title;
        $filename .= " - ";
        foreach ($details['authors'] as $author) {
            $filename .= $author->name;
        }
        $filename .= ".epub";
        # Validate request e-mail format
        $to_email = $this->requester->post('email');
        if (!InputUtil::isEMailValid($to_email)) {
            $this->log()->debug("kindle: invalid email, " . $to_email);
            return $this->responder->error(400, "Invalid email");
        } else {
            $this->responder->deleteCookie(Settings::KINDLE_COOKIE);
            $bookpath = $this->calibre()->titleFile($id, $file);
            if (!file_exists($bookpath)) {
                $this->log()->warning("kindle: no file found for book " . $id . " " . $file);
                return $this->notFound();
            }
            $this->log()->debug("kindle: requested file " . $bookpath);
            $mailer = $this->mailer();
            $send_success = 0;
            try {
                $message_success = $mailer->createBookMessage($bookpath, $settings->display_app_name, $to_email, $settings->kindle_from_email, $filename);
                if (!$message_success) {
                    $this->log()->warning('kindle: book message to ' . $to_email . ' failed, dump: ' . $mailer->getDump());
                    $message = $this->getMessageString('error_kindle_send');
                    return $this->responder->error(503, $message);
                }
                $send_success = $mailer->sendMessage();
                if ($send_success == 0) {
                    $this->log()->warning('kindle: book delivery to ' . $to_email . ' failed, dump: ' . $mailer->getDump());
                } else {
                    $this->log()->debug('kindle: book delivered to ' . $to_email . ', result ' . $send_success);
                }
                # if there was an exception, log it and return gracefully
            } catch (Throwable $e) {
                $this->log()->warning('kindle: Email exception ' . $e->getMessage());
                $this->log()->warning('kindle: Mail dump ' . $mailer->getDump());
            }
            # Store e-mail address in cookie so user needs to enter it only once
            $this->responder->setCookie(Settings::KINDLE_COOKIE, $to_email);
            if ($send_success > 0) {
                $message = $this->getMessageString('send_success');
                return $this->responder->plain($message);
            } else {
                $message = $this->getMessageString('error_kindle_send');
                return $this->responder->error(503, $message);
            }
        }
    }


    /**
     *  A list of authors at $page -> /authorslist/{page}
     * @param int $page author list page index
     * @return Response
     */
    public function authorsSlice($page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($page)) {
            $this->log()->warning('authorsSlice: invalid page id ' . $page);
            return $this->badParameter();
        }

        // @todo handle jumpTarget = initial chosen with alpine.js in tailwind/navbar_list.twig

        $search = $this->requester->get('search');
        if (isset($search)) {
            $tl = $this->calibre()->authorsSlice($page, $settings->page_size, trim($search));
        } else {
            $tl = $this->calibre()->authorsSlice($page, $settings->page_size);
        }

        foreach ($tl['entries'] as $author) {
            $author->thumbnail = $this->bbs()->author($author->id)->getThumbnail();
            if ($author->thumbnail) {
                $this->log()->debug('authorsSlice thumbnail ' . var_export($author->thumbnail->url, true));
            }
        }
        return $this->render('authors.twig', [
            'page' => $this->buildPage('authors', 3, 1),
            'url' => 'authorslist',
            'url_v2' => 'authors',
            'authors' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search]);
    }

    /**
     * Details for a single author -> /authors/{id}/{page}/
     * Shows the detail data for the author plus a paginated list of books
     *
     * @param  integer $id author id
     * @param  integer $page page index for book list
     * @return Response
     */
    public function authorDetailsSlice($id, $page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->log()->warning('authorDetailsSlice: invalid author id ' . $id . ' or page id ' . $page);
            return $this->badParameter();
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->authorDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        if (is_null($tl)) {
            $this->log()->debug('no author ' . $id);
            return $this->notFound();
        }
        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);

        $series = $this->calibre()->authorSeries($id, $books);

        /** @var Author $author */
        $author = $tl['author'];
        $author->thumbnail = $this->bbs()->author($id)->getThumbnail();
        // Note: $author->note comes from Calibre DB
        $note = $this->bbs()->authorNote($id);
        if (!is_null($note)) {
            $author->notes_source = $note->ntext;
        } else {
            $author->notes_source = null;
        }
        if (!empty($author->notes_source)) {
            $markdownParser = new MarkdownExtra();
            $author->notes = $markdownParser->transform($author->notes_source);
        } else {
            $author->notes = null;
        }

        // Note: $author->link comes from Calibre DB
        $author->links = $this->bbs()->author($id)->getLinks();
        return $this->render('author_detail.twig', [
            'page' => $this->buildPage('author_details', 3, 2),
            'url' => 'authors/' . $id,
            'author' => $author,
            'books' => $books,
            'series' => $series,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'isadmin' => $this->requester->isAdmin()]);
    }


    /**
     * Notes for a single author -> /authors/{id}/notes/
     *
     * @param  int $id author id
     * @return Response
     */
    public function authorNotes($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('authorNotes: invalid author id ' . $id);
            return $this->badParameter();
        }

        /** @var ?Author $author */
        $author = $this->calibre()->author($id);
        if (is_null($author)) {
            $this->log()->debug('authorNotes: author id not found ' . $id);
            return $this->notFound();
        }
        $note = $this->bbs()->authorNote($id);
        if (!is_null($note)) {
            $author->notes_source = $note->ntext;
        } else {
            $author->notes_source = null;
        }
        if (!empty($author->notes_source)) {
            $markdownParser = new MarkdownExtra();
            $author->notes = $markdownParser->transform($author->notes_source);
        } else {
            $author->notes = null;
        }
        return $this->render('author_notes.twig', [
            'page' => $this->buildPage('author_notes', 3, 2),
            'url' => 'authors/' . $id,
            'author' => $author,
            'isadmin' => $this->requester->isAdmin()]);
    }


    /**
     * Return a HTML page of series at page $page.
     * @param  int $page =0 page index into series list
     * @return Response
     */
    public function seriesSlice($page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($page)) {
            $this->log()->warning('seriesSlice: invalid series index ' . $page);
            return $this->badParameter();
        }

        $search = $this->requester->get('search');
        if (isset($search)) {
            $this->log()->debug('seriesSlice: search ' . $search);
            $tl = $this->calibre()->seriesSlice($page, $settings->page_size, trim($search));
        } else {
            $tl = $this->calibre()->seriesSlice($page, $settings->page_size);
        }
        $this->log()->debug('seriesSlice ended');
        return $this->render('series.twig', [
            'page' => $this->buildPage('series', 5, 1),
            'url' => 'serieslist',
            'url_v2' => 'series',
            'series' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search]);
    }

    /**
     * Details for a single series -> /series/{id}/{page}/
     * Shows the detail data for the series plus a paginated list of books
     *
     * @param  int $id series id
     * @param  int $page page index for books
     * @return Response
     */
    public function seriesDetailsSlice($id, $page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->log()->warning('seriesDetailsSlice: invalid series id ' . $id . ' or page id ' . $page);
            return $this->badParameter();
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->seriesDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        if (is_null($tl)) {
            $this->log()->debug('seriesDetailsSlice: no series ' . $id);
            return $this->notFound();
        }
        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);
        return $this->render('series_detail.twig', [
            'page' => $this->buildPage('series_details', 5, 2),
            'url' => 'series/' . $id,
            'series' => $tl['series'],
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'isadmin' => $this->requester->isAdmin()]);
    }


    /**
     * A list of tags at $page -> /tagslist/{page}
     * @param int $page
     * @return Response
     */
    public function tagsSlice($page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($page)) {
            $this->log()->warning('tagsSlice: invalid page id ' . $page);
            return $this->badParameter();
        }

        $search = $this->requester->get('search');
        if (isset($search)) {
            $tl = $this->calibre()->tagsSlice($page, $settings->page_size, trim($search));
        } else {
            $tl = $this->calibre()->tagsSlice($page, $settings->page_size);
        }
        return $this->render('tags.twig', [
            'page' => $this->buildPage('tags', 4, 1),
            'url' => 'tagslist',
            'url_v2' => 'tags',
            'tags' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search]);
    }

    /**
     * Details for a single tag -> /tags/{id}/{page}/
     * Shows the detail data for the tag plus a paginated list of books
     *
     * @param  integer $id series id
     * @param  integer $page page index for books
     * @return Response
     */
    public function tagDetailsSlice($id, $page = 0)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->log()->warning('tagsDetailsSlice: invalid tag id ' . $id . ' or page id ' . $page);
            return $this->badParameter();
        }

        $filter = $this->getFilter();
        $tl = $this->calibre()->tagDetailsSlice($settings['lang'], $id, $page, $settings->page_size, $filter);
        if (is_null($tl)) {
            $this->log()->debug('no tag ' . $id);
            return $this->notFound();
        }
        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);
        return $this->render('tag_detail.twig', [
            'page' => $this->buildPage('tag_details', 4, 2),
            'url' => 'tags/' . $id,
            'tag' => $tl['tag'],
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages']]);
    }

    /**
     * Get params for tailwind/navbar_list.twig template -> /params/{scope}/{type}/
     * @param string $scope
     * @param string $type
     * @return Response
     */
    public function getTailwindParams($scope, $type = 'initials')
    {
        switch ($scope) {
            case 'authors':
                $initials = $this->calibre()->authorsInitials();
                // align result format with expected format in template
                $data = array_map(function ($initial) {
                    return $initial->initial;
                }, $initials);
                return $this->responder->json(['data' => $data]);
            case 'series':
                $initials = $this->calibre()->seriesInitials();
                // align result format with expected format in template
                $data = array_map(function ($initial) {
                    return $initial->initial;
                }, $initials);
                return $this->responder->json(['data' => $data]);
            case 'tags':
                $initials = $this->calibre()->tagsInitials();
                // align result format with expected format in template
                $data = array_map(function ($initial) {
                    return $initial->initial;
                }, $initials);
                return $this->responder->json(['data' => $data]);
            case 'titles':
                // @todo add methods from v2 to Calibre to get these params?
                if ($type == 'year') {
                    return $this->badParameter();
                }
                return $this->badParameter();
            default:
                return $this->badParameter();
        }
    }

    /*********************************************************************
     * Utility and helper functions, private
     ********************************************************************/

    public function checkThumbnail($book)
    {
        $book->thumbnail = $this->thumbs()->isTitleThumbnailAvailable($book->id);
        return $book;
    }

    /**
     * Checks if a title is available to the current users
     * @param array $book_details  output of BicBucStriim::title_details()
     * @return  bool      true if the title is not availble for the user, else false
     */
    public function titleForbidden($book_details)
    {
        if (!$this->requester->isAuthenticated()) {
            return false;
        }
        $user = $this->requester->getAuth()->getUserData();
        if (empty($user['languages']) && empty($user['tags'])) {
            return false;
        } else {
            if (!empty($user['languages'])) {
                $lang_found = false;
                foreach ($book_details['langcodes'] as $langcode) {
                    if ($langcode === $user['languages']) {
                        $lang_found = true;
                        break;
                    }
                }
                if (!$lang_found) {
                    return true;
                }
            }
            if (!empty($user['tags'])) {
                $tag_found = false;
                foreach ($book_details['tags'] as $tag) {
                    if ($tag->name === $user['tags']) {
                        $tag_found = true;
                        break;
                    }
                }
                if ($tag_found) {
                    return true;
                }
            }
            return false;
        }
    }
}
