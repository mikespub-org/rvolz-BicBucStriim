<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\Models\Calibrething;
use BicBucStriim\Models\Note;
use BicBucStriim\Models\R;

trait CanAddNote
{
    /**
     * Get the note text for a Calibre entity.
     * @param ?Calibrething $calibreThing
     * @return ?Note note text or null
     */
    public function getCalibreNote($calibreThing)
    {
        if (is_null($calibreThing)) {
            return null;
        }
        return $calibreThing->getNote();
    }

    /**
     * Set the note text for a Calibre entity.
     * @param ?Calibrething $calibreThing
     * @param string 	$mime 		mime type for the note's content
     * @param string 	$noteText	note content
     * @return ?Note 	created/edited note
     */
    public function editCalibreNote($calibreThing, $mime, $noteText)
    {
        if (is_null($calibreThing)) {
            return null;
        }
        $note = $calibreThing->getNote();
        if (is_null($note)) {
            // Unless/until we support different types of notes per entity, the default is the Calibre type
            $note = Note::build($calibreThing->ctype, $mime, $noteText);
            $calibreThing->addNote($note);
            $calibreThing->refctr += 1;
            R::store($calibreThing);
        } else {
            $note->mime = $mime;
            $note->ntext = $noteText;
            R::store($note);
        }
        return $note;
    }

    /**
     * Delete the note for a Calibre entity
     * @param ?Calibrething $calibreThing
     * @return bool
     */
    public function deleteCalibreNote($calibreThing)
    {
        if (is_null($calibreThing)) {
            return false;
        }
        $note = $calibreThing->getNote();
        if (is_null($note)) {
            return false;
        }
        $calibreThing->deleteNote($note->id);
        $calibreThing->refctr -= 1;
        R::trash($note);
        if ($calibreThing->refctr == 0) {
            R::trash($calibreThing);
        } else {
            R::store($calibreThing);
        }
        return true;
    }
}
