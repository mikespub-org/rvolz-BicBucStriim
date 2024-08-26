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

        $file = $this->requester->files('file');
        $root = $this->requester->getBasePath();
        if (empty($file)) {
            $this->log()->debug('edit_author_thm: upload error ' . 'file is empty');
            $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error1') . ': ' . 'file is empty');
            return $this->responder->redirect($root . '/authors/' . $id . '/0/');
        }

        $allowedExts = ["jpeg", "jpg", "png"];
        #$temp = explode(".", $file["name"]);
        #$extension = end($temp);
        $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $this->log()->debug('edit_author_thm: ' . $file["name"]);
        if ((($file["type"] == "image/jpeg")
                || ($file["type"] == "image/jpg")
                || ($file["type"] == "image/pjpeg")
                || ($file["type"] == "image/x-png")
                || ($file["type"] == "image/png"))
            && ($file["size"] < 3145728)
            && in_array($extension, $allowedExts)
        ) {
            $this->log()->debug('edit_author_thm: filetype ' . $file["type"] . ', size ' . $file["size"]);
            if ($file["error"] > 0) {
                $this->log()->debug('edit_author_thm: upload error ' . $file["error"]);
                $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error1') . ': ' . $file["error"]);
                return $this->responder->redirect($root . '/authors/' . $id . '/0/');
            } else {
                $this->log()->debug('edit_author_thm: upload ok, converting');
                $author = $this->calibre()->author($id);
                $created = $this->bbs()->editAuthorThumbnail($id, $author->name, $settings->thumb_gen_clipped, $file["tmp_name"], $file["type"]);
                $this->log()->debug('edit_author_thm: converted, redirecting');
                return $this->responder->redirect($root . '/authors/' . $id . '/0/');
            }
        } else {
            $this->log()->warning('edit_author_thm: Uploaded thumbnail too big or wrong type');
            $this->setFlash('error', $this->getMessageString('author_thumbnail_upload_error2'));
            return $this->responder->redirect($root . '/authors/' . $id . '/0/');
        }
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
        $del = $this->bbs()->deleteAuthorThumbnail($id);
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
            $author = $this->calibre()->author($id);
            $note = $this->bbs()->editAuthorNote($id, $author->name, $note_data['mime'], $note_data['ntext']);
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
        $del = $this->bbs()->deleteAuthorNote($id);
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
        $author = $this->calibre()->author($id);
        $link = null;
        if (!is_null($author)) {
            $link = $this->bbs()->addAuthorLink($id, $author->name, $link_data['label'], $link_data['url']);
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
        $ret = $this->bbs()->deleteAuthorLink($id, $link);
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
