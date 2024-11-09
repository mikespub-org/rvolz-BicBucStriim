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
    /**
     * Add routes for metadata actions
     */
    public static function addRoutes($app, $prefix = '/metadata', $gatekeeper = null)
    {
        //$self = new self($app);
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        // use $gatekeeper for all actions in this group
        $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
            RouteUtil::mapRoutes($group, $routes);
        })->add($gatekeeper);
    }

    /**
     * Get routes for metadata actions
     * @param self|string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // method(s), path, ...middleware(s), callable
            ['POST', '/authors/{id}/thumbnail/', [$self, 'edit_author_thm']],
            ['DELETE', '/authors/{id}/thumbnail/', [$self, 'del_author_thm']],
            ['POST', '/authors/{id}/notes/', [$self, 'edit_author_notes']],
            ['DELETE', '/authors/{id}/notes/', [$self, 'del_author_notes']],
            ['POST', '/authors/{id}/links/', [$self, 'new_author_link']],
            ['DELETE', '/authors/{id}/links/{link}/', [$self, 'del_author_link']],
            ['POST', '/series/{id}/notes/', [$self, 'edit_series_notes']],
            ['DELETE', '/series/{id}/notes/', [$self, 'del_series_notes']],
            ['POST', '/series/{id}/links/', [$self, 'new_series_link']],
            ['DELETE', '/series/{id}/links/{link}/', [$self, 'del_series_link']],
        ];
    }

    /**
     * Upload an author thumbnail picture -> POST /metadata/authors/{id}/thumbnail/
     * Works only with JPG/PNG, max. size 3MB
     * @return Response
     */
    public function edit_author_thm($id)
    {
        $settings = $this->settings();

        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('edit_author_thm: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        /** @var \Psr\Http\Message\UploadedFileInterface|null $file */
        $file = $this->requester->files('file');
        $root = $this->requester->getBasePath();
        if (empty($file)) {
            $this->log()->debug('edit_author_thm: upload error ' . 'file is empty');
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
        $this->log()->debug('edit_author_thm: ' . $name);
        if ((($type == "image/jpeg")
                || ($type == "image/jpg")
                || ($type == "image/pjpeg")
                || ($type == "image/x-png")
                || ($type == "image/png"))
            && ($size < 3145728)
            && in_array($extension, $allowedExts)
        ) {
            $this->log()->debug('edit_author_thm: filetype ' . $type . ', size ' . $size);
            if ($error > 0) {
                $this->log()->debug('edit_author_thm: upload error ' . $error);
                $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error1') . ': ' . $error);
                return $this->responder->redirect($root . '/authors/' . $id . '/0/');
            }
            try {
                $tmpfile = tempnam(sys_get_temp_dir(), 'BBS');
                $file->moveTo($tmpfile);
            } catch (Exception $e) {
                $this->log()->debug('edit_author_thm: moveTo error ' . $e->getMessage());
                $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error1') . ': ' . $e->getMessage());
                return $this->responder->redirect($root . '/authors/' . $id . '/0/');
            }
            $this->log()->debug('edit_author_thm: upload ok, converting');
            // we need the author name to create the calibre entity if needed
            $author = $this->calibre()->author($id);
            $artefact = $this->bbs()->author($id, $author->name)->editThumbnail($settings->thumb_gen_clipped, $tmpfile, $type);
            $this->log()->debug('edit_author_thm: converted, redirecting');
            return $this->responder->redirect($root . '/authors/' . $id . '/0/');
        }
        $this->log()->warning('edit_author_thm: Uploaded thumbnail too big or wrong type');
        $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error2'));
        return $this->responder->redirect($root . '/authors/' . $id . '/0/');
    }

    /**
     * Delete the author's thumbnail -> DELETE /metadata/authors/{id}/thumbnail/ JSON
     * @return Response
     */
    public function del_author_thm($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('del_author_thm: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('del_author_thm: ' . $id);
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
     * Edit the notes about the author -> POST /metadata/authors/{id}/notes/ JSON
     * @return Response
     */
    public function edit_author_notes($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('edit_author_notes: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('edit_author_notes: ' . $id);
        $note_data = $this->requester->post();
        $this->log()->debug('edit_author_notes: note ' . var_export($note_data, true));
        try {
            $markdownParser = new MarkdownExtra();
            $html = $markdownParser->transform($note_data['ntext']);
            // we need the author name to create the calibre entity if needed
            $author = $this->calibre()->author($id);
            $note = $this->bbs()->author($id, $author->name)->editNote($note_data['mime'], $note_data['ntext']);
        } catch (Exception $e) {
            $this->log()->error('edit_author_notes: error for editing note ' . var_export($note_data, true));
            $this->log()->error('edit_author_notes: exception ' . $e->getMessage());
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
    public function del_author_notes($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('del_author_notes: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('del_author_notes: ' . $id);
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
     * Add a new author link -> POST /metadata/authors/{id}/links JSON
     * @return Response
     */
    public function new_author_link($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('new_author_link: invalid author id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $link_data = $this->requester->post();
        $this->log()->debug('new_author_link: ' . var_export($link_data, true));
        // we need the author name to create the calibre entity if needed
        $author = $this->calibre()->author($id);
        $link = null;
        if (!is_null($author)) {
            $link = $this->bbs()->author($id, $author->name)->addLink($link_data['label'], $link_data['url']);
        }
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
    public function del_author_link($id, $link)
    {
        // parameter checking
        if (!is_numeric($id) || !is_numeric($link)) {
            $this->log()->warning('del_author_link: invalid author id ' . $id . ' or link id ' . $link);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('del_author_link: author ' . $id . ', link ' . $link);
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
     * Edit the notes about the series -> POST /metadata/series/{id}/notes/ JSON
     * @return Response
     */
    public function edit_series_notes($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('edit_series_notes: invalid series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('edit_series_notes: ' . $id);
        $note_data = $this->requester->post();
        $this->log()->debug('edit_series_notes: note ' . var_export($note_data, true));
        try {
            $markdownParser = new MarkdownExtra();
            $html = $markdownParser->transform($note_data['ntext']);
            // we need the series name to create the calibre entity if needed
            $series = $this->calibre()->series($id);
            $note = $this->bbs()->series($id, $series->name)->editNote($note_data['mime'], $note_data['ntext']);
        } catch (Exception $e) {
            $this->log()->error('edit_series_notes: error for editing note ' . var_export($note_data, true));
            $this->log()->error('edit_series_notes: exception ' . $e->getMessage());
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
    public function del_series_notes($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('del_series_notes: invalid series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('del_series_notes: ' . $id);
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
     * Add a new series link -> POST /metadata/series/{id}/links JSON
     * @return Response
     */
    public function new_series_link($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('new_series_link: invalid series id ' . $id);
            return $this->responder->error(400, "Bad parameter");
        }

        $link_data = $this->requester->post();
        $this->log()->debug('new_series_link: ' . var_export($link_data, true));
        // we need the series name to create the calibre entity if needed
        $series = $this->calibre()->series($id);
        $link = null;
        if (!is_null($series)) {
            $link = $this->bbs()->series($id, $series->name)->addLink($link_data['label'], $link_data['url']);
        }
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
    public function del_series_link($id, $link)
    {
        // parameter checking
        if (!is_numeric($id) || !is_numeric($link)) {
            $this->log()->warning('del_series_link: invalid series id ' . $id . ' or link id ' . $link);
            return $this->responder->error(400, "Bad parameter");
        }

        $this->log()->debug('del_series_link: series ' . $id . ', link ' . $link);
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
