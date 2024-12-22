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

use BicBucStriim\Utilities\RouteUtil;
use Michelf\MarkdownExtra;
use Psr\Http\Message\ResponseInterface as Response;
use Exception;

/*********************************************************************
 * Metadata actions
 ********************************************************************/
class MetadataActions extends DefaultActions
{
    public const PREFIX = '/metadata';

    /**
     * Add routes for metadata actions
     */
    public static function addRoutes($app, $prefix = self::PREFIX, $gatekeeper = null)
    {
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        // use $gatekeeper for all actions in this group
        $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
            RouteUtil::mapRoutes($group, $routes);
        })->add($gatekeeper);
    }

    /**
     * Get routes for metadata actions
     * @param self|class-string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // name => method(s), path, ...middleware(s), callable
            'meta-author-thumb' => ['GET', '/authors/{id}/thumbnail/', [$self, 'getAuthorThumbnail']],
            'meta-author-thumb-post' => ['POST', '/authors/{id}/thumbnail/', [$self, 'editAuthorThumbnail']],
            'meta-author-thumb-delete' => ['DELETE', '/authors/{id}/thumbnail/', [$self, 'delAuthorThumbnail']],
            'meta-author-note' => ['GET', '/authors/{id}/notes/', [$self, 'getAuthorNote']],
            'meta-author-note-post' => ['POST', '/authors/{id}/notes/', [$self, 'editAuthorNote']],
            'meta-author-note-delete' => ['DELETE', '/authors/{id}/notes/', [$self, 'delAuthorNote']],
            'meta-author-links' => ['GET', '/authors/{id}/links/', [$self, 'getAuthorLinks']],
            'meta-author-link-post' => ['POST', '/authors/{id}/links/', [$self, 'newAuthorLink']],
            'meta-author-link-delete' => ['DELETE', '/authors/{id}/links/{link}/', [$self, 'delAuthorLink']],
            'meta-series-note' => ['GET', '/series/{id}/notes/', [$self, 'getSeriesNote']],
            'meta-series-note-post' => ['POST', '/series/{id}/notes/', [$self, 'editSeriesNotes']],
            'meta-series-note-delete' => ['DELETE', '/series/{id}/notes/', [$self, 'delSeriesNotes']],
            'meta-series-links' => ['GET', '/series/{id}/links/', [$self, 'getSeriesLinks']],
            'meta-series-link-post' => ['POST', '/series/{id}/links/', [$self, 'newSeriesLink']],
            'meta-series-link-delete' => ['DELETE', '/series/{id}/links/{link}/', [$self, 'delSeriesLink']],
        ];
    }

    /**
     * Get the author's thumbnail -> GET /metadata/authors/{id}/thumbnail/ JSON
     * @return Response
     */
    public function getAuthorThumbnail($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('getAuthorThumbnail: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        $this->log()->debug('getAuthorThumbnail: ' . $id);
        $thumb = $this->bbs()->author($id)->getThumbnail();
        return $this->responder->json(['data' => $thumb]);
    }

    /**
     * Upload an author thumbnail picture -> POST /metadata/authors/{id}/thumbnail/
     * Works only with JPG/PNG, max. size 3MB
     * @return Response
     */
    public function editAuthorThumbnail($id)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('editAuthorThumbnail: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        // we need the author name to create the calibre entity if needed
        $author = $this->calibre()->author($id);
        if (empty($author) || empty($author->name)) {
            $this->log()->warning('editAuthorThumbnail: unknown author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        /** @var \Psr\Http\Message\UploadedFileInterface|null $file */
        $file = $this->requester->files('file');
        $root = $this->requester->getBasePath();
        if (empty($file)) {
            $this->log()->debug('editAuthorThumbnail: upload error ' . 'file is empty');
            $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error1') . ': ' . 'file is empty');
            return $this->responder->redirect($root . '/authors/' . $id . '/0/');
        }

        $name = $file->getClientFilename();
        $type = $file->getClientMediaType();
        $size = $file->getSize();
        $error = $file->getError();

        $allowedExts = ["jpeg", "jpg", "png"];
        #$temp = explode(".", $file["name"]);
        #$extension = end($temp);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $this->log()->debug('editAuthorThumbnail: ' . $name);
        if ((($type == "image/jpeg")
                || ($type == "image/jpg")
                || ($type == "image/pjpeg")
                || ($type == "image/x-png")
                || ($type == "image/png"))
            && ($size < 3145728)
            && in_array($extension, $allowedExts)
        ) {
            $this->log()->debug('editAuthorThumbnail: filetype ' . $type . ', size ' . $size);
            if ($error > 0) {
                $this->log()->debug('editAuthorThumbnail: upload error ' . $error);
                $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error1') . ': ' . $error);
                return $this->responder->redirect($root . '/authors/' . $id . '/0/');
            }
            try {
                $tmpfile = tempnam(sys_get_temp_dir(), 'BBS');
                $file->moveTo($tmpfile);
            } catch (Exception $e) {
                $this->log()->debug('editAuthorThumbnail: moveTo error ' . $e->getMessage());
                $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error1') . ': ' . $e->getMessage());
                return $this->responder->redirect($root . '/authors/' . $id . '/0/');
            }
            $this->log()->debug('editAuthorThumbnail: upload ok, converting');
            $artefact = $this->bbs()->author($id, $author->name)->editThumbnail($settings->thumb_gen_clipped, $tmpfile, $type);
            $this->log()->debug('editAuthorThumbnail: converted, redirecting');
            return $this->responder->redirect($root . '/authors/' . $id . '/0/');
        }
        $this->log()->warning('editAuthorThumbnail: Uploaded thumbnail too big or wrong type');
        $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error2'));
        return $this->responder->redirect($root . '/authors/' . $id . '/0/');
    }

    /**
     * Delete the author's thumbnail -> DELETE /metadata/authors/{id}/thumbnail/ JSON
     * @return Response
     */
    public function delAuthorThumbnail($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('delAuthorThumbnail: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('delAuthorThumbnail: ' . $id);
        $del = $this->bbs()->author($id)->deleteThumbnail();
        if ($del) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Get the author's note -> GET /metadata/authors/{id}/notes/ JSON
     * @return Response
     */
    public function getAuthorNote($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('getAuthorNote: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        $this->log()->debug('getAuthorNote: ' . $id);
        $note = $this->bbs()->author($id)->getNote();
        return $this->responder->json(['data' => $note]);
    }

    /**
     * Edit the notes about the author -> POST /metadata/authors/{id}/notes/ JSON
     * @return Response
     */
    public function editAuthorNote($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('editAuthorNote: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        // we need the author name to create the calibre entity if needed
        $author = $this->calibre()->author($id);
        if (empty($author) || empty($author->name)) {
            $this->log()->warning('editAuthorNote: unknown author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('editAuthorNote: ' . $id);
        $note_data = $this->requester->post();
        $this->log()->debug('editAuthorNote: note ' . var_export($note_data, true));
        try {
            $markdownParser = new MarkdownExtra();
            $html = $markdownParser->transform($note_data['ntext']);
            $note = $this->bbs()->author($id, $author->name)->editNote($note_data['mime'], $note_data['ntext']);
        } catch (Exception $e) {
            $this->log()->error('editAuthorNote: error for editing note ' . var_export($note_data, true));
            $this->log()->error('editAuthorNote: exception ' . $e->getMessage());
            $html = null;
            $note = null;
        }
        if (!is_null($note)) {
            $msg = $this->getMessageString('admin_modified');
            $note2 = $note->unbox()->getProperties();
            $note2['html'] = $html;
            $data = ['note' => $note2, 'msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Delete notes about the author -> DELETE /metadata/authors/{id}/notes/ JSON
     * @return Response
     */
    public function delAuthorNote($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('delAuthorNote: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('delAuthorNote: ' . $id);
        $del = $this->bbs()->author($id)->deleteNote();
        if ($del) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Get the author's links -> GET /metadata/authors/{id}/links/ JSON
     * @return Response
     */
    public function getAuthorLinks($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('getAuthorLinks: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        $this->log()->debug('getAuthorLinks: ' . $id);
        $links = $this->bbs()->author($id)->getLinks();
        return $this->responder->json(['data' => $links]);
    }

    /**
     * Add a new author link -> POST /metadata/authors/{id}/links JSON
     * @return Response
     */
    public function newAuthorLink($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('newAuthorLink: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        // we need the author name to create the calibre entity if needed
        $author = $this->calibre()->author($id);
        if (empty($author) || empty($author->name)) {
            $this->log()->warning('newAuthorLink: unknown author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $link_data = $this->requester->post();
        $this->log()->debug('newAuthorLink: ' . var_export($link_data, true));
        $link = $this->bbs()->author($id, $author->name)->addLink($link_data['label'], $link_data['url']);
        if (!is_null($link)) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['link' => $link->unbox()->getProperties(), 'msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Delete an author link -> DELETE /metadata/authors/{id}/links/{link}/ JSON
     * @return Response
     */
    public function delAuthorLink($id, $link)
    {
        // parameter checking
        if (!is_numeric($id) || !is_numeric($link)) {
            $this->log()->warning('delAuthorLink: invalid author id ' . $id . ' or link id ' . $link);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('delAuthorLink: author ' . $id . ', link ' . $link);
        $ret = $this->bbs()->author($id)->deleteLink($link);
        if ($ret) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Get the series note -> GET /metadata/authors/{id}/notes/ JSON
     * @return Response
     */
    public function getSeriesNote($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('getSeriesNote: invalid series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        $this->log()->debug('getSeriesNote: ' . $id);
        $note = $this->bbs()->series($id)->getNote();
        return $this->responder->json(['data' => $note]);
    }

    /**
     * Edit the notes about the series -> POST /metadata/series/{id}/notes/ JSON
     * @return Response
     */
    public function editSeriesNotes($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('editSeriesNotes: invalid series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        // we need the series name to create the calibre entity if needed
        $series = $this->calibre()->series($id);
        if (empty($series) || empty($series->name)) {
            $this->log()->warning('editSeriesNotes: unknown series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('editSeriesNotes: ' . $id);
        $note_data = $this->requester->post();
        $this->log()->debug('editSeriesNotes: note ' . var_export($note_data, true));
        try {
            $markdownParser = new MarkdownExtra();
            $html = $markdownParser->transform($note_data['ntext']);
            $note = $this->bbs()->series($id, $series->name)->editNote($note_data['mime'], $note_data['ntext']);
        } catch (Exception $e) {
            $this->log()->error('editSeriesNotes: error for editing note ' . var_export($note_data, true));
            $this->log()->error('editSeriesNotes: exception ' . $e->getMessage());
            $html = null;
            $note = null;
        }
        if (!is_null($note)) {
            $msg = $this->getMessageString('admin_modified');
            $note2 = $note->unbox()->getProperties();
            $note2['html'] = $html;
            $data = ['note' => $note2, 'msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Delete notes about the series -> DELETE /metadata/series/{id}/notes/ JSON
     * @return Response
     */
    public function delSeriesNotes($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('delSeriesNotes: invalid series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('delSeriesNotes: ' . $id);
        $del = $this->bbs()->series($id)->deleteNote();
        if ($del) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Get the series links -> GET /metadata/series/{id}/links/ JSON
     * @return Response
     */
    public function getSeriesLinks($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('getSeriesLinks: invalid series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        $this->log()->debug('getSeriesLinks: ' . $id);
        $links = $this->bbs()->series($id)->getLinks();
        return $this->responder->json(['data' => $links]);
    }

    /**
     * Add a new series link -> POST /metadata/series/{id}/links JSON
     * @return Response
     */
    public function newSeriesLink($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('newSeriesLink: invalid series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }
        // we need the series name to create the calibre entity if needed
        $series = $this->calibre()->series($id);
        if (empty($series) || empty($series->name)) {
            $this->log()->warning('newSeriesLink: unknown series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $link_data = $this->requester->post();
        $this->log()->debug('newSeriesLink: ' . var_export($link_data, true));
        $link = $this->bbs()->series($id, $series->name)->addLink($link_data['label'], $link_data['url']);
        if (!is_null($link)) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['link' => $link->unbox()->getProperties(), 'msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Delete an series link -> DELETE /metadata/series/{id}/links/{link}/ JSON
     * @return Response
     */
    public function delSeriesLink($id, $link)
    {
        // parameter checking
        if (!is_numeric($id) || !is_numeric($link)) {
            $this->log()->warning('delSeriesLink: invalid series id ' . $id . ' or link id ' . $link);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('delSeriesLink: series ' . $id . ', link ' . $link);
        $ret = $this->bbs()->series($id)->deleteLink($link);
        if ($ret) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }
}
