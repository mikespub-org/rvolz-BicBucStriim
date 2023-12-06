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

use BicBucStriim\Calibre\Author;
use Michelf\MarkdownExtra;
use Exception;
use Mailer;
use Twig\TwigFilter;
use Utilities;
use MetadataEpub;

/*********************************************************************
 * Main actions
 ********************************************************************/
class MainActions extends DefaultActions
{
    /**
     * Add routes for main actions
    */
    public static function addRoutes($app, $prefix = null)
    {
        $self = new self($app);
        $app->notFound([$self, 'myNotFound']);
        $app->get('/', [$self, 'main']);
        $app->get('/login/', [$self, 'show_login']);
        $app->post('/login/', [$self, 'perform_login']);
        $app->get('/logout/', [$self, 'logout']);
        $app->get('/authors/:id/notes/', [$self, 'check_admin'], [$self, 'authorNotes']);
        #$app->post('/authors/:id/notes/', [$self, 'check_admin'], [$self, 'authorNotesEdit']);
        $app->get('/authors/:id/:page/', [$self, 'authorDetailsSlice']);
        $app->get('/authorslist/:id/', [$self, 'authorsSlice']);
        $app->get('/search/', [$self, 'globalSearch']);
        $app->get('/series/:id/:page/', [$self, 'seriesDetailsSlice']);
        $app->get('/serieslist/:id/', [$self, 'seriesSlice']);
        $app->get('/tags/:id/:page/', [$self, 'tagDetailsSlice']);
        $app->get('/tagslist/:id/', [$self, 'tagsSlice']);
        $app->get('/titles/:id/', [$self, 'title']);
        $app->get('/titles/:id/cover/', [$self, 'cover']);
        $app->get('/titles/:id/file/:file', [$self, 'book']);
        $app->post('/titles/:id/kindle/:file', [$self, 'kindle']);
        $app->get('/titles/:id/thumbnail/', [$self, 'thumbnail']);
        $app->get('/titleslist/:id/', [$self, 'titlesSlice']);
    }

    /**
    * 404 page for invalid URLs
    */
    public function myNotFound()
    {
        $app = $this->app;
        $app->render('error.html', [
            'page' => $this->mkPage('not_found1'),
            'title' => $this->getMessageString('not_found1'),
            'error' => $this->getMessageString('not_found2')]);
    }

    public function show_login()
    {
        $app = $this->app;
        if ($this->is_authenticated()) {
            $app->getLog()->info('user is already logged in : ' . $app->auth->getUserName());
            $app->redirect($app->request->getRootUri() . '/');
        } else {
            $app->render('login.html', [
                'page' => $this->mkPage('login')]);
        }
    }

    public function perform_login()
    {
        $app = $this->app;
        $login_data = $app->request()->post();
        $app->getLog()->debug('login: ' . var_export($login_data, true));
        if (isset($login_data['username']) && isset($login_data['password'])) {
            $uname = $login_data['username'];
            $upw = $login_data['password'];
            if (empty($uname) || empty($upw)) {
                $app->render('login.html', [
                    'page' => $this->mkPage('login')]);
            } else {
                $app->login_service->login($app->auth, ['username' => $uname, 'password' => $upw]);
                $success = $app->auth->getStatus();
                $app->getLog()->debug('login success: ' . $success);
                if ($this->is_authenticated()) {
                    $app->getLog()->info('logged in user : ' . $app->auth->getUserName());
                    $app->redirect($app->request->getRootUri() . '/');
                } else {
                    $app->getLog()->error('error logging in user : ' . $login_data['username']);
                    $app->render('login.html', [
                        'page' => $this->mkPage('login')]);
                }
            }
        } else {
            $app->render('login.html', [
                'page' => $this->mkPage('login')]);
        }
    }

    public function logout()
    {
        $app = $this->app;
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
        $app->render('logout.html', [
            'page' => $this->mkPage('logout')]);
    }

    /*********************************************************************
     * HTML presentation functions
     ********************************************************************/

    /**
     * Generate the main page with the 30 most recent titles
     */
    public function main()
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        $filter = $this->getFilter();
        $books1 = $app->calibre->last30Books($globalSettings['lang'], $globalSettings[PAGE_SIZE], $filter);
        $books = array_map([$this, 'checkThumbnail'], $books1);
        $stats = $app->calibre->libraryStats($filter);
        $app->render('index_last30.html', [
            'page' => $this->mkPage('dl30', 1, 1),
            'books' => $books,
            'stats' => $stats]);
    }

    /**
     * Make a search over all categories. Returns only the first PAGES_SIZE items per category.
     * If there are more entries per category, there will be a link to the full results.
     */
    public function globalSearch()
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // TODO check search paramater?

        $filter = $this->getFilter();
        $search = $app->request()->get('search');
        $tlb = $app->calibre->titlesSlice($globalSettings['lang'], 0, $globalSettings[PAGE_SIZE], $filter, trim($search));
        $tlb_books = array_map([$this, 'checkThumbnail'], $tlb['entries']);
        $tla = $app->calibre->authorsSlice(0, $globalSettings[PAGE_SIZE], trim($search));
        $tla_books = array_map([$this, 'checkThumbnail'], $tla['entries']);
        $tlt = $app->calibre->tagsSlice(0, $globalSettings[PAGE_SIZE], trim($search));
        $tlt_books = array_map([$this, 'checkThumbnail'], $tlt['entries']);
        $tls = $app->calibre->seriesSlice(0, $globalSettings[PAGE_SIZE], trim($search));
        $tls_books = array_map([$this, 'checkThumbnail'], $tls['entries']);
        $app->render('global_search.html', [
            'page' => $this->mkPage('pagination_search', 0),
            'books' => $tlb_books,
            'books_total' => $tlb['total'] == -1 ? 0 : $tlb['total'],
            'more_books' => ($tlb['total'] > $globalSettings[PAGE_SIZE]),
            'authors' => $tla_books,
            'authors_total' => $tla['total'] == -1 ? 0 : $tla['total'],
            'more_authors' => ($tla['total'] > $globalSettings[PAGE_SIZE]),
            'tags' => $tlt_books,
            'tags_total' => $tlt['total'] == -1 ? 0 : $tlt['total'],
            'more_tags' => ($tlt['total'] > $globalSettings[PAGE_SIZE]),
            'series' => $tls_books,
            'series_total' => $tls['total'] == -1 ? 0 : $tls['total'],
            'more_series' => ($tls['total'] > $globalSettings[PAGE_SIZE]),
            'search' => $search]);
    }

    /**
     * A list of titles at $index -> /titlesList/:index
     */
    public function titlesSlice($index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($index)) {
            $app->getLog()->warn('titlesSlice: invalid page id ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $search = $app->request()->get('search');
        if (isset($search)) {
            $search = trim($search);
        }
        $sort = $app->request()->get('sort');

        if (isset($sort) && $sort == 'byReverseDate') {
            switch ($globalSettings[TITLE_TIME_SORT]) {
                case TITLE_TIME_SORT_TIMESTAMP:
                    $tl = $app->calibre->timestampOrderedTitlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter, $search);
                    break;
                case TITLE_TIME_SORT_PUBDATE:
                    $tl = $app->calibre->pubdateOrderedTitlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter, $search);
                    break;
                case TITLE_TIME_SORT_LASTMODIFIED:
                    $tl = $app->calibre->lastmodifiedOrderedTitlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter, $search);
                    break;
                default:
                    $app->getLog()->error('titlesSlice: invalid sort order ' . $globalSettings[TITLE_TIME_SORT]);
                    $tl = $app->calibre->timestampOrderedTitlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter, $search);
                    break;
            }
        } else {
            $tl = $app->calibre->titlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter, $search);
        }

        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);
        $app->render('titles.html', [
            'page' => $this->mkPage('titles', 2, 1),
            'url' => 'titleslist',
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search,
            'sort' => $sort]);
    }

    /**
     * Creates a human readable filesize string
     */
    public function human_filesize($bytes, $decimals = 0)
    {
        $size = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    /**
     * Show a single title > /titles/:id. The ID ist the Calibre ID
     */
    public function title($id)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // Add filter for human readable filesize
        $filter = new TwigFilter('hfsize', function ($string) {
            return $this->human_filesize($string);
        });
        /** @var \BicBucStriim\TwigView $view */
        $view = $app->view();
        $tenv = $view->getInstance();
        $tenv->addFilter($filter);

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('title: invalid title id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        $details = $app->calibre->titleDetails($globalSettings['lang'], $id);
        if (is_null($details)) {
            $app->getLog()->warn("title: book not found: " . $id);
            $app->notFound();
            return;
        }
        // for people trying to circumvent filtering by direct access
        if ($this->title_forbidden($details)) {
            $app->getLog()->warn("title: requested book not allowed for user: " . $id);
            $app->notFound();
            return;
        }
        // Show ID links only if there are templates and ID data
        $idtemplates = $app->bbs->idTemplates();
        $id_tmpls = [];
        if (count($idtemplates) > 0 && count($details['ids']) > 0) {
            $show_idlinks = true;
            foreach ($idtemplates as $idtemplate) {
                $id_tmpls[$idtemplate->name] = [$idtemplate->val, $idtemplate->label];
            }
        } else {
            $show_idlinks = false;
        }
        $kindle_format = ($globalSettings[KINDLE] == 1) ? $app->calibre->titleGetKindleFormat($id) : null;
        $app->getLog()->debug('titleDetails custom columns: ' . count($details['custom']));
        $app->render(
            'title_detail.html',
            ['page' => $this->mkPage('book_details', 2, 2),
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
                'kindle_from_email' => $globalSettings[KINDLE_FROM_EMAIL],
                'protect_dl' => false]
        );
    }

    /**
     * Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
     * If there is no cover, return 404.
     * Route: /titles/:id/cover
     */
    public function cover($id)
    {
        $app = $this->app;

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('cover: invalid title id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        $has_cover = false;
        $rot = $app->request()->getRootUri();
        $book = $app->calibre->title($id);
        if (is_null($book)) {
            $app->getLog()->debug("cover: book not found: " . $id);
            $app->response()->setStatus(404);
            return;
        }

        if ($book->has_cover) {
            $cover = $app->calibre->titleCover($id);
            $has_cover = true;
        }
        if ($has_cover) {
            $app->response()->setStatus(200);
            $app->response()->headers->set('Content-type', 'image/jpeg;base64');
            $app->response()->headers->set('Content-Length', filesize($cover));
            readfile($cover);
        } else {
            $app->response()->setStatus(404);
        }
    }

    /**
     * Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
     * If there is no cover, return 404.
     * Route: /titles/:id/thumbnail
     */
    public function thumbnail($id)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('thumbnail: invalid title id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        $app->getLog()->debug('thumbnail: ' . $id);
        $has_cover = false;
        $rot = $app->request()->getRootUri();
        $book = $app->calibre->title($id);
        if (is_null($book)) {
            $app->getLog()->error("thumbnail: book not found: " . $id);
            $app->response()->setStatus(404);
            return;
        }

        if ($book->has_cover) {
            $cover = $app->calibre->titleCover($id);
            $thumb = $app->bbs->titleThumbnail($id, $cover, $globalSettings[THUMB_GEN_CLIPPED]);
            $app->getLog()->debug('thumbnail: thumb found ' . $thumb);
            $has_cover = true;
        }
        if ($has_cover) {
            $app->response()->setStatus(200);
            $app->response()->headers->set('Content-type', 'image/png;base64');
            $app->response()->headers->set('Content-Length', filesize($thumb));
            readfile($thumb);
        } else {
            $app->response()->setStatus(404);
        }
    }

    /**
     * Return the selected file for the book with ID.
     * Route: /titles/:id/file/:file
     */
    public function book($id, $file)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('book: invalid title id ' . $id);
            $app->halt(400, "Bad parameter");
        }
        // TODO check file parameter?

        $details = $app->calibre->titleDetails($globalSettings['lang'], $id);
        if (is_null($details)) {
            $app->getLog()->warn("book: no book found for " . $id);
            $app->notFound();
            return;
        }
        // for people trying to circumvent filtering by direct access
        if ($this->title_forbidden($details)) {
            $app->getLog()->warn("book: requested book not allowed for user: " . $id);
            $app->notFound();
            return;
        }

        $real_bookpath = $app->calibre->titleFile($id, $file);
        $contentType = Utilities::titleMimeType($real_bookpath);
        if ($this->is_authenticated()) {
            $app->getLog()->info("book download by " . $app->auth->getUserName() . " for " . $real_bookpath .
                " with metadata update = " . $globalSettings[METADATA_UPDATE]);
        } else {
            $app->getLog()->info("book download for " . $real_bookpath .
                " with metadata update = " . $globalSettings[METADATA_UPDATE]);
        }
        if ($contentType == Utilities::MIME_EPUB && $globalSettings[METADATA_UPDATE]) {
            if ($details['book']->has_cover == 1) {
                $cover = $app->calibre->titleCover($id);
            } else {
                $cover = null;
            }
            // If an EPUB update the metadata
            $mdep = new MetadataEpub($real_bookpath);
            $mdep->updateMetadata($details, $cover);
            $bookpath = $mdep->getUpdatedFile();
            $app->getLog()->debug("book(e): file " . $bookpath);
            $app->getLog()->debug("book(e): type " . $contentType);
            $booksize = filesize($bookpath);
            $app->getLog()->debug("book(e): size " . $booksize);
            if ($booksize > 0) {
                header("Content-Length: " . $booksize);
            }
            header("Content-Type: " . $contentType);
            header("Content-Disposition: attachment; filename=\"" . $file . "\"");
            header("Content-Description: File Transfer");
            header("Content-Transfer-Encoding: binary");
            $this->readfile_chunked($bookpath);
        } else {
            // Else send the file as is
            $bookpath = $real_bookpath;
            $app->getLog()->debug("book: file " . $bookpath);
            $app->getLog()->debug("book: type " . $contentType);
            $booksize = filesize($bookpath);
            $app->getLog()->debug("book: size " . $booksize);
            header("Content-Length: " . $booksize);
            header("Content-Type: " . $contentType);
            header("Content-Disposition: attachment; filename=\"" . $file . "\"");
            header("Content-Description: File Transfer");
            header("Content-Transfer-Encoding: binary");
            $this->readfile_chunked($bookpath);
        }
    }


    /**
     * Send the selected file to a Kindle e-mail address
     * Route: /titles/:id/kindle/:file
     */
    public function kindle($id, $file)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('kindle: invalid title id ' . $id);
            $app->halt(400, "Bad parameter");
        }
        // TODO check file parameter?

        $book = $app->calibre->title($id);

        if (is_null($book)) {
            $app->getLog()->debug("kindle: book not found: " . $id);
            $app->notFound();
            return;
        }

        $details = $app->calibre->titleDetails($globalSettings['lang'], $id);
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
        $to_email = $app->request()->post('email');
        if (!Utilities::isEMailValid($to_email)) {
            $app->getLog()->debug("kindle: invalid email, " . $to_email);
            $app->response()->setStatus(400);
            return;
        } else {
            $app->deleteCookie(KINDLE_COOKIE);
            $bookpath = $app->calibre->titleFile($id, $file);
            $app->getLog()->debug("kindle: requested file " . $bookpath);
            if ($globalSettings[MAILER] == Mailer::SMTP) {
                $mail = ['username' => $globalSettings[SMTP_USER],
                    'password' => $globalSettings[SMTP_PASSWORD],
                    'smtp-server' => $globalSettings[SMTP_SERVER],
                    'smtp-port' => $globalSettings[SMTP_PORT]];
                if ($globalSettings[SMTP_ENCRYPTION] == 1) {
                    $mail['smtp-encryption'] = Mailer::SSL;
                } elseif ($globalSettings[SMTP_ENCRYPTION] == 2) {
                    $mail['smtp-encryption'] = Mailer::TLS;
                }
                $app->getLog()->debug('kindle mail config: ' . var_export($mail, true));
                $mailer = new Mailer(Mailer::SMTP, $mail);
            } elseif ($globalSettings[MAILER] == Mailer::SENDMAIL) {
                $mailer = new Mailer(Mailer::SENDMAIL);
            } else {
                $mailer = new Mailer(Mailer::MAIL);
            }
            $send_success = 0;
            try {
                $message = $mailer->createBookMessage($bookpath, $globalSettings[DISPLAY_APP_NAME], $to_email, $globalSettings[KINDLE_FROM_EMAIL], $filename);
                $send_success = $mailer->sendMessage($message);
                if ($send_success == 0) {
                    $app->getLog()->warn('kindle: book delivery to ' . $to_email . ' failed, dump: ' . $mailer->getDump());
                } else {
                    $app->getLog()->debug('kindle: book delivered to ' . $to_email . ', result ' . $send_success);
                }
                # if there was an exception, log it and return gracefully
            } catch (Exception $e) {
                $app->getLog()->warn('kindle: Email exception ' . $e->getMessage());
                $app->getLog()->warn('kindle: Mail dump ' . $mailer->getDump());
            }
            # Store e-mail address in cookie so user needs to enter it only once
            $app->setCookie(KINDLE_COOKIE, $to_email);
            if ($send_success > 0) {
                echo $this->getMessageString('send_success');
            } else {
                $app->response()->setStatus(503);
            }
        }
    }


    /**
     *  A list of authors at $index -> /authorslist/:index
     * @param int $index author list page index
     */
    public function authorsSlice($index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($index)) {
            $app->getLog()->warn('authorsSlice: invalid page id ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $search = $app->request()->get('search');
        if (isset($search)) {
            $tl = $app->calibre->authorsSlice($index, $globalSettings[PAGE_SIZE], trim($search));
        } else {
            $tl = $app->calibre->authorsSlice($index, $globalSettings[PAGE_SIZE]);
        }

        foreach ($tl['entries'] as $author) {
            $author->thumbnail = $app->bbs->getAuthorThumbnail($author->id);
            if ($author->thumbnail) {
                $app->getLog()->debug('authorsSlice thumbnail ' . var_export($author->thumbnail->url, true));
            }
        }
        $app->render('authors.html', [
            'page' => $this->mkPage('authors', 3, 1),
            'url' => 'authorslist',
            'authors' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search]);
    }

    /**
     * Details for a single author -> /authors/:id
     * @param int $id author id
     * @deprecated since 0.9.3
     */
    public function author($id)
    {
        $app = $this->app;

        $details = $app->calibre->authorDetails($id);
        if (is_null($details)) {
            $app->getLog()->debug("no author");
            $app->notFound();
            return;
        }
        $app->render('author_detail.html', [
            'page' => $this->mkPage('author_details', 3, 2),
            'author' => $details['author'],
            'books' => $details['books']]);
    }

    /**
     * Details for a single author -> /authors/:id/:page/
     * Shows the detail data for the author plus a paginated list of books
     *
     * @param  integer $id author id
     * @param  integer $index page index for book list
     * @return void HTML page
     */
    public function authorDetailsSlice($id, $index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id) || !is_numeric($index)) {
            $app->getLog()->warn('authorDetailsSlice: invalid author id ' . $id . ' or page id ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $app->calibre->authorDetailsSlice($globalSettings['lang'], $id, $index, $globalSettings[PAGE_SIZE], $filter);
        if (is_null($tl)) {
            $app->getLog()->debug('no author ' . $id);
            $app->notFound();
            return;
        }
        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);

        $series = $app->calibre->authorSeries($id, $books);

        /** @var Author $author */
        $author = $tl['author'];
        $author->thumbnail = $app->bbs->getAuthorThumbnail($id);
        $note = $app->bbs->authorNote($id);
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

        $author->links = $app->bbs->authorLinks($id);
        $app->render('author_detail.html', [
            'page' => $this->mkPage('author_details', 3, 2),
            'url' => 'authors/' . $id,
            'author' => $author,
            'books' => $books,
            'series' => $series,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'isadmin' => $this->is_admin()]);
    }


    /**
     * Notes for a single author -> /authors/:id/notes/
     *
     * @param  int $id author id
     */
    public function authorNotes($id)
    {
        $app = $this->app;

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('authorNotes: invalid author id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        /** @var ?Author $author */
        $author = $app->calibre->author($id);
        if (is_null($author)) {
            $app->getLog()->debug('authorNotes: author id not found ' . $id);
            $app->notFound();
            return;
        }
        $note = $app->bbs->authorNote($id);
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
        $app->render('author_notes.html', [
            'page' => $this->mkPage('author_notes', 3, 2),
            'url' => 'authors/' . $id,
            'author' => $author,
            'isadmin' => $this->is_admin()]);
    }


    /**
     * Return a HTML page of series at page $index.
     * @param  int $index =0 page index into series list
     */
    public function seriesSlice($index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($index)) {
            $app->getLog()->warn('seriesSlice: invalid series index ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $search = $app->request()->get('search');
        if (isset($search)) {
            $app->getLog()->debug('seriesSlice: search ' . $search);
            $tl = $app->calibre->seriesSlice($index, $globalSettings[PAGE_SIZE], trim($search));
        } else {
            $tl = $app->calibre->seriesSlice($index, $globalSettings[PAGE_SIZE]);
        }
        $app->render('series.html', [
            'page' => $this->mkPage('series', 5, 1),
            'url' => 'serieslist',
            'series' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search]);
        $app->getLog()->debug('seriesSlice ended');
    }

    /**
     * Return a HTML page with details of series $id, /series/:id
     * @param  int $id series id
     * @deprecated since 0.9.3
     */
    public function series($id)
    {
        $app = $this->app;

        $details = $app->calibre->seriesDetails($id);
        if (is_null($details)) {
            $app->getLog()->debug('no series ' . $id);
            $app->notFound();
            return;
        }
        $app->render('series_detail.html', [
            'page' => $this->mkPage('series_details', 5, 3),
            'series' => $details['series'],
            'books' => $details['books']]);
    }

    /**
     * Details for a single series -> /series/:id/:page/
     * Shows the detail data for the series plus a paginated list of books
     *
     * @param  int $id series id
     * @param  int $index page index for books
     */
    public function seriesDetailsSlice($id, $index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id) || !is_numeric($index)) {
            $app->getLog()->warn('seriesDetailsSlice: invalid series id ' . $id . ' or page id ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $app->calibre->seriesDetailsSlice($globalSettings['lang'], $id, $index, $globalSettings[PAGE_SIZE], $filter);
        if (is_null($tl)) {
            $app->getLog()->debug('seriesDetailsSlice: no series ' . $id);
            $app->notFound();
            return;
        }
        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);
        $app->render('series_detail.html', [
            'page' => $this->mkPage('series_details', 5, 2),
            'url' => 'series/' . $id,
            'series' => $tl['series'],
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages']]);
    }


    /**
     * A list of tags at $index -> /tagslist/:index
     * @param int $index
     */
    public function tagsSlice($index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($index)) {
            $app->getLog()->warn('tagsSlice: invalid page id ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $search = $app->request()->get('search');
        if (isset($search)) {
            $tl = $app->calibre->tagsSlice($index, $globalSettings[PAGE_SIZE], trim($search));
        } else {
            $tl = $app->calibre->tagsSlice($index, $globalSettings[PAGE_SIZE]);
        }
        $app->render('tags.html', [
            'page' => $this->mkPage('tags', 4, 1),
            'url' => 'tagslist',
            'tags' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search]);
    }

    /**
     * Details for a single tag -> /tags/:id/:page
     * @deprecated since 0.9.3
     */
    public function tag($id)
    {
        $app = $this->app;

        $details = $app->calibre->tagDetails($id);
        if (is_null($details)) {
            $app->getLog()->debug("no tag");
            $app->notFound();
            return;
        }
        $app->render('tag_detail.html', [
            'page' => $this->mkPage('tag_details', 4, 3),
            'tag' => $details['tag'],
            'books' => $details['books']]);
    }

    /**
     * Details for a single tag -> /tags/:id/:page/
     * Shows the detail data for the tag plus a paginated list of books
     *
     * @param  integer $id series id
     * @param  integer $index page index for books
     * @return void HTML page
     */
    public function tagDetailsSlice($id, $index = 0)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id) || !is_numeric($index)) {
            $app->getLog()->warn('tagsDetailsSlice: invalid tag id ' . $id . ' or page id ' . $index);
            $app->halt(400, "Bad parameter");
        }

        $filter = $this->getFilter();
        $tl = $app->calibre->tagDetailsSlice($globalSettings['lang'], $id, $index, $globalSettings[PAGE_SIZE], $filter);
        if (is_null($tl)) {
            $app->getLog()->debug('no tag ' . $id);
            $app->notFound();
            return;
        }
        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);
        $app->render('tag_detail.html', [
            'page' => $this->mkPage('tag_details', 4, 2),
            'url' => 'tags/' . $id,
            'tag' => $tl['tag'],
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages']]);
    }

    /*********************************************************************
     * Utility and helper functions, private
     ********************************************************************/

    public function checkThumbnail($book)
    {
        $app = $this->app;
        $book->thumbnail = $app->bbs->isTitleThumbnailAvailable($book->id);
        return $book;
    }

    /**
     * Checks if a title is available to the current users
     * @param array $book_details  output of BicBucStriim::title_details()
     * @return  bool      true if the title is not availble for the user, else false
     */
    public function title_forbidden($book_details)
    {
        $app = $this->app;

        if (!$this->is_authenticated()) {
            return false;
        }
        $user = $app->auth->getUserData();
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

    /**
     * Utility function to serve files
     */
    public function readfile_chunked($filename)
    {
        $app = $this->app;
        $app->getLog()->debug('readfile_chunked ' . $filename);
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
            return false;
        }
        while (!feof($handle)) {
            $buffer = fread($handle, 1024 * 1024);
            echo $buffer;
            ob_flush();
            flush();
        }
        $status = fclose($handle);
        return $status;
    }
}
