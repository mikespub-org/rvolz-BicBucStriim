<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Models;

/**
 * RedBeanPHP FUSE model for 'calibrething' bean with one-to-many relation
 * of Calibre entity (author, series, tag, ...) with link, note and artefact
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
     * Summary of build
     * @param mixed $ctype
     * @param mixed $cid
     * @param mixed $cname
     * @return self
     */
    public static function build($ctype, $cid, $cname)
    {
        $calibreThing = self::cast(R::dispense('calibrething'));
        $calibreThing->ctype = $ctype;
        $calibreThing->cid = $cid;
        $calibreThing->cname = $cname;
        $calibreThing->ownArtefactList = [];
        $calibreThing->ownLinkList = [];
        $calibreThing->ownNoteList = [];
        $calibreThing->refctr = 0;
        return $calibreThing;
    }

    /**
     * Return links releated to this Calibre entity.
     * @param ?int $ltype link type (not actually needed at the moment)
     * @return array<Link> all available links
     */
    public function getLinks($ltype = null)
    {
        // Unless/until we support different types of artefacts per entity, the default is the Calibre type
        $ltype ??= $this->ctype;
        // Note: we cannot use $this->ownLinkList ?? [] directly here due to lazy loading
        $links = $this->ownLinkList;
        return array_values(array_filter($links ?? [], function ($link) use ($ltype) {
            return $link->ltype == $ltype;
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
     * Return the note text related to this Calibre entity.
     * @param ?int $ntype note type (not actually needed at the moment)
     * @return ?Note text or null
     */
    public function getNote($ntype = null)
    {
        // Unless/until we support different types of artefacts per entity, the default is the Calibre type
        $ntype ??= $this->ctype;
        // Note: we cannot use $this->ownNoteList ?? [] directly here due to lazy loading
        $notes = $this->ownNoteList;
        $notes = array_values(array_filter($notes ?? [], function ($note) use ($ntype) {
            return($note->ntype == $ntype);
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
     * Return the thumbnail file related to this Calibre entity.
     * @param ?int $atype artefact type (not actually needed at the moment)
     * @return ?Artefact Path to thumbnail file or null
     */
    public function getThumbnail($atype = null)
    {
        // Unless/until we support different types of artefacts per entity, the default is the Calibre type
        $atype ??= $this->ctype;
        // Note: we cannot use $this->ownArtefactList ?? [] directly here due to lazy loading
        $artefacts = $this->ownArtefactList;
        $artefacts = array_values(array_filter($artefacts ?? [], function ($artefact) use ($atype) {
            return $artefact->atype == $atype;
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
