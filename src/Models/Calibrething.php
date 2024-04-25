<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Models;

use BicBucStriim\AppData\DataConstants;

/**
 * RedBeanPHP FUSE model for 'calibrething' bean with one-to-many relation of author with link, note and artefact
 * See https://www.redbeanphp.com/index.php?p=/one_to_many
 * @property mixed $ownLinkList
 * @property mixed $ownNoteList
 * @property mixed $ownArtefactList
 * @property mixed $id
 * @property mixed $ctype
 * @property mixed $cid
 * @property mixed $cname
 * @property mixed $refctr
 */
class Calibrething extends Model
{
    /**
     * Return author links releated to this Calibre entitiy.
     * @return array<Link> all available author links
     */
    public function getAuthorLinks()
    {
        // Note: we cannot use $this->ownLinkList ?? [] directly here due to lazy loading
        $links = $this->ownLinkList;
        return array_values(array_filter($links ?? [], function ($link) {
            return($link->ltype == DataConstants::AUTHOR_LINK);
        }));
    }

    public function addLink($link)
    {
        $this->bean->ownLinkList[] = $link;
    }

    public function deleteLink($linkId)
    {
        unset($this->bean->ownLinkList[$linkId]);
    }

    /**
     * Return the author note text related to this Calibre entitiy.
     * @return ?Note 	text or null
     */
    public function getAuthorNote()
    {
        // Note: we cannot use $this->ownNoteList ?? [] directly here due to lazy loading
        $notes = $this->ownNoteList;
        $notes = array_values(array_filter($notes ?? [], function ($note) {
            return($note->ntype == DataConstants::AUTHOR_NOTE);
        }));
        if (empty($notes)) {
            return null;
        } else {
            return Note::cast($notes[0]);
        }
    }

    public function addNote($note)
    {
        $this->bean->ownNoteList[] = $note;
    }

    public function deleteNote($noteId)
    {
        unset($this->bean->ownNoteList[$noteId]);
    }

    /**
     * Return the author thumbnail file related to this Calibre entitiy.
     * @return ?Artefact 	Path to thumbnail file or null
     */
    public function getAuthorThumbnail()
    {
        // Note: we cannot use $this->ownArtefactList ?? [] directly here due to lazy loading
        $artefacts = $this->ownArtefactList;
        $artefacts = array_values(array_filter($artefacts ?? [], function ($artefact) {
            return($artefact->atype == DataConstants::AUTHOR_THUMBNAIL_ARTEFACT);
        }));
        if (empty($artefacts)) {
            return null;
        } else {
            return Artefact::cast($artefacts[0]);
        }
    }

    public function addArtefact($artefact)
    {
        $this->bean->ownArtefactList[] = $artefact;
    }

    public function deleteArtefact($artefactId)
    {
        unset($this->bean->ownArtefactList[$artefactId]);
    }
}
