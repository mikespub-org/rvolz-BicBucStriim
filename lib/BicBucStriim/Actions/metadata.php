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

use Michelf\MarkdownExtra;
use Exception;

/*********************************************************************
 * Metadata actions
 ********************************************************************/
class MetadataActions extends DefaultActions
{
    /**
     * Add routes for metadata actions
    */
    public static function addRoutes($app, $prefix = '/metadata')
    {
        $self = new self($app);
        $app->group($prefix, [$self, 'check_admin'], function () use ($app, $self) {
            $app->post('/authors/:id/thumbnail/', [$self, 'edit_author_thm']);
            $app->delete('/authors/:id/thumbnail/', [$self, 'del_author_thm']);
            $app->post('/authors/:id/notes/', [$self, 'edit_author_notes']);
            $app->delete('/authors/:id/notes/', [$self, 'del_author_notes']);
            $app->post('/authors/:id/links/', [$self, 'new_author_link']);
            $app->delete('/authors/:id/links/:link_id/', [$self, 'del_author_link']);
        });
    }

    /**
     * Upload an author thumbnail picture -> POST /metadata/authors/:id/thumbnail/
     * Works only with JPG/PNG, max. size 3MB
     */
    public function edit_author_thm($id)
    {
        $app = $this->app;
        $globalSettings = $app->config('globalSettings');

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('edit_author_thm: invalid author id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        $allowedExts = ["jpeg", "jpg", "png"];
        #$temp = explode(".", $_FILES["file"]["name"]);
        #$extension = end($temp);
        $extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
        $app->getLog()->debug('edit_author_thm: ' . $_FILES["file"]["name"]);
        if ((($_FILES["file"]["type"] == "image/jpeg")
                || ($_FILES["file"]["type"] == "image/jpg")
                || ($_FILES["file"]["type"] == "image/pjpeg")
                || ($_FILES["file"]["type"] == "image/x-png")
                || ($_FILES["file"]["type"] == "image/png"))
            && ($_FILES["file"]["size"] < 3145728)
            && in_array($extension, $allowedExts)
        ) {
            $app->getLog()->debug('edit_author_thm: filetype ' . $_FILES["file"]["type"] . ', size ' . $_FILES["file"]["size"]);
            if ($_FILES["file"]["error"] > 0) {
                $app->getLog()->debug('edit_author_thm: upload error ' . $_FILES["file"]["error"]);
                $app->flash('error', $this->getMessageString('author_thumbnail_upload_error1') . ': ' . $_FILES["file"]["error"]);
                $rot = $app->request()->getRootUri();
                $app->redirect($rot . '/authors/' . $id . '/0/');
            } else {
                $app->getLog()->debug('edit_author_thm: upload ok, converting');
                $author = $app->calibre->author($id);
                $created = $app->bbs->editAuthorThumbnail($id, $author->name, $globalSettings[THUMB_GEN_CLIPPED], $_FILES["file"]["tmp_name"], $_FILES["file"]["type"]);
                $app->getLog()->debug('edit_author_thm: converted, redirecting');
                $rot = $app->request()->getRootUri();
                $app->redirect($rot . '/authors/' . $id . '/0/');
            }
        } else {
            $app->getLog()->warn('edit_author_thm: Uploaded thumbnail too big or wrong type');
            $app->flash('error', $this->getMessageString('author_thumbnail_upload_error2'));
            $rot = $app->request()->getRootUri();
            $app->redirect($rot . '/authors/' . $id . '/0/');
        }
    }

    /**
     * Delete the author's thumbnail -> DELETE /metadata/authors/:id/thumbnail/ JSON
     */
    public function del_author_thm($id)
    {
        $app = $this->app;

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('del_author_thm: invalid author id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        $app->getLog()->debug('del_author_thm: ' . $id);
        $del = $app->bbs->deleteAuthorThumbnail($id);
        $resp = $app->response();
        if ($del) {
            $resp->setStatus(200);
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['msg' => $msg]);
            $resp->headers->set('Content-type', 'application/json');
        } else {
            $resp->setStatus(500);
            $resp->headers->set('Content-type', 'text/plain');
            $answer = $this->getMessageString('admin_modify_error');
        }
        $resp->headers->set('Content-Length', strlen($answer));
        $resp->setBody($answer);
    }

    /**
     * Edit the notes about the author -> POST /metadata/authors/:id/notes/ JSON
     */
    public function edit_author_notes($id)
    {
        $app = $this->app;

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('edit_author_notes: invalid author id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        $app->getLog()->debug('edit_author_notes: ' . $id);
        $note_data = $app->request()->post();
        $app->getLog()->debug('edit_author_notes: note ' . var_export($note_data, true));
        try {
            $markdownParser = new MarkdownExtra();
            $html = $markdownParser->transform($note_data['ntext']);
            $author = $app->calibre->author($id);
            $note = $app->bbs->editAuthorNote($id, $author->name, $note_data['mime'], $note_data['ntext']);
        } catch (Exception $e) {
            $app->getLog()->error('edit_author_notes: error for editing note ' . var_export($note_data, true));
            $app->getLog()->error('edit_author_notes: exception ' . $e->getMessage());
            $html = null;
            $note = null;
        }
        $resp = $app->response();
        if (!is_null($note)) {
            $resp->setStatus(200);
            $msg = $this->getMessageString('admin_modified');
            $note2 = $note->unbox()->getProperties();
            $note2['html'] = $html;
            $answer = json_encode(['note' => $note2, 'msg' => $msg]);
            $resp->headers->set('Content-type', 'application/json');
        } else {
            $resp->setStatus(500);
            $resp->headers->set('Content-type', 'text/plain');
            $answer = $this->getMessageString('admin_modify_error');
        }
        $resp->headers->set('Content-Length', strlen($answer));
        $resp->setBody($answer);
    }

    /**
     * Delete notes about the author -> DELETE /metadata/authors/:id/notes/ JSON
     */
    public function del_author_notes($id)
    {
        $app = $this->app;

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('del_author_notes: invalid author id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        $app->getLog()->debug('del_author_notes: ' . $id);
        $del = $app->bbs->deleteAuthorNote($id);
        $resp = $app->response();
        if ($del) {
            $resp->setStatus(200);
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['msg' => $msg]);
            $resp->headers->set('Content-type', 'application/json');
        } else {
            $resp->setStatus(500);
            $resp->headers->set('Content-type', 'text/plain');
            $answer = $this->getMessageString('admin_modify_error');
        }
        $resp->headers->set('Content-Length', strlen($answer));
        $resp->setBody($answer);
    }

    /**
     * Add a new author link -> POST /metadata/authors/:id/links JSON
     */
    public function new_author_link($id)
    {
        $app = $this->app;

        // parameter checking
        if (!is_numeric($id)) {
            $app->getLog()->warn('new_author_link: invalid author id ' . $id);
            $app->halt(400, "Bad parameter");
        }

        $link_data = $app->request()->post();
        $app->getLog()->debug('new_author_link: ' . var_export($link_data, true));
        $author = $app->calibre->author($id);
        $link = null;
        if (!is_null($author)) {
            $link = $app->bbs->addAuthorLink($id, $author->name, $link_data['label'], $link_data['url']);
        }
        $resp = $app->response();
        if (!is_null($link)) {
            $resp->setStatus(200);
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['link' => $link->unbox()->getProperties(), 'msg' => $msg]);
            $resp->headers->set('Content-type', 'application/json');
        } else {
            $resp->setStatus(500);
            $resp->headers->set('Content-type', 'text/plain');
            $answer = $this->getMessageString('admin_modify_error');
        }
        $resp->headers->set('Content-Length', strlen($answer));
        $resp->setBody($answer);
    }

    /**
     * Delete an author link -> DELETE /metadata/authors/:id/links/:link/ JSON
     */
    public function del_author_link($id, $link)
    {
        $app = $this->app;

        // parameter checking
        if (!is_numeric($id) || !is_numeric($link)) {
            $app->getLog()->warn('del_author_link: invalid author id ' . $id . ' or link id ' . $link);
            $app->halt(400, "Bad parameter");
        }

        $app->getLog()->debug('del_author_link: author ' . $id . ', link ' . $link);
        $ret = $app->bbs->deleteAuthorLink($id, $link);
        $resp = $app->response();
        if ($ret) {
            $resp->setStatus(200);
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['msg' => $msg]);
            $resp->headers->set('Content-type', 'application/json');
        } else {
            $resp->setStatus(500);
            $resp->headers->set('Content-type', 'text/plain');
            $answer = $this->getMessageString('admin_modify_error');
        }
        $resp->headers->set('Content-Length', strlen($answer));
        $resp->setBody($answer);
    }
}