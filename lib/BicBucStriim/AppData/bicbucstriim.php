<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\AppData;

use PDO;
use Exception;

class BicBucStriim
{
    # Name to the bbs db
    public const DBNAME = 'data.db';
    # Thumbnail dimension (they are square)
    public const THUMB_RES = 160;


    # bbs sqlite db
    public $mydb = null;
    # calibre library dir
    public $calibre_dir = '';
    # calibre library file, last modified date
    public $calibre_last_modified;
    # last sqlite error
    public $last_error = 0;
    # dir for bbs db
    public $data_dir = '';
    # dir for generated title thumbs
    public $thumb_dir = '';
    # dir for generated title thumbs
    public $authors_dir = '';

    /**
     * Try to open the BBS DB. If the DB file does not exist we do nothing.
     * Creates also the subdirectories for thumbnails etc. if they don't exist.
     *
     * We open it first as PDO, because we need that for the
     * authentication library, then we initialize RedBean.
     *
     * @param string  	$dataPath 	Path to BBS DB, default = data/data.db
     * @param boolean	$freeze 	if true the DB schema is fixed,
     * 								else RedBeanPHP adapt the schema
     * 								default = true
     */
    public function __construct($dataPath = 'data/data.db', $freeze = true)
    {
        $rp = realpath($dataPath);
        $this->data_dir = dirname($dataPath);
        $this->thumb_dir = $this->data_dir . '/titles';
        if (!file_exists($this->thumb_dir)) {
            mkdir($this->thumb_dir);
        }
        $this->authors_dir = $this->data_dir . '/authors';
        if (!file_exists($this->authors_dir)) {
            mkdir($this->authors_dir);
        }
        if (file_exists($rp) && is_writeable($rp)) {
            $this->mydb = new PDO('sqlite:' . $rp, null, null, []);
            //$this->mydb->setAttribute(1002, 'SET NAMES utf8');
            $this->mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->mydb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->last_error = $this->mydb->errorCode();
            if (R::hasDatabase('default')) {
                R::close();
                R::removeToolBoxByKey('default');
            }
            R::setup('sqlite:' . $rp);
            R::freeze($freeze);
        } else {
            $this->mydb = null;
        }
    }

    /**
     * Create an empty BBS DB, just with the initial admin user account, so that login is possible.
     * @param string $dataPath Path to BBS DB
     */
    public function createDataDb($dataPath = 'data/data.db')
    {
        $schema = file($this->data_dir . '/schema.sql');
        $this->mydb = new PDO('sqlite:' . $dataPath, null, null, []);
        //$this->mydb->setAttribute(1002, 'SET NAMES utf8');
        $this->mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->mydb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($schema); $i++) {
            if (strpos($schema[$i], '--') == false) {
                $this->mydb->exec($schema[$i]);
            }
        }
        $mdp = password_hash('admin', PASSWORD_BCRYPT);
        $this->mydb->exec('insert into user (username, password, role) values ("admin", "' . $mdp . '",1)');
        $this->mydb->exec('insert into config (name, val) values ("db_version", "3")');
        $this->mydb = null;
    }


    /**
     * Is our own DB open?
     * @return boolean	true if open, else false
     */
    public function dbOk()
    {
        return (!is_null($this->mydb));
    }

    /**
     * Find all configuration values in the settings DB
     * @return array configuration values
     */
    public function configs()
    {
        return R::findAll('config');
    }

    /**
     * Find a specific configuration value by name
     * @param string 	$name 	configuration parameter name
     * @return ?Model_Config	config paramter or null
     */
    public function config($name)
    {
        $config = R::findOne('config', ' name = :name', [':name' => $name]);
        if (!is_null($config)) {
            $config = Model_Config::cast($config);
        }
        return $config;
    }

    /**
     * Save all configuration values in the settings DB
     * @param  array 	$configs 	array of configuration values
     */
    public function saveConfigs($configs)
    {
        foreach ($configs as $name => $val) {
            $config = $this->config($name);
            if (is_null($config)) {
                /** @var Model_Config @config */
                $config = Model_Config::cast(R::dispense('config'));
                $config->name = $name;
                $config->val = $val;
            } else {
                $config->val = $val;
            }
            if ($config->unbox()->getMeta('tainted')) {
                R::store($config);
            }
        }
    }

    /**
     * Find all user records in the settings DB
     * @return array user data
     */
    public function users()
    {
        return R::findAll('user');
    }

    /**
     * Find a specific user in the settings DB
     * @return ?Model_User user data or NULL if not found
     */
    public function user($userid)
    {
        $user = R::load('user', $userid);
        if (empty($user->id)) {
            return null;
        } else {
            return Model_User::cast($user);
        }
    }

    /**
     * Add a new user account.
     * The username must be unique. Name and password must not be empty.
     * @param $username string login name for the account, must be unique
     * @param $password string clear text password
     * @return ?Model_User user account or null if the user exists or one of the parameters is empty
     * @throws Exception if the DB operation failed
     */
    public function addUser($username, $password)
    {
        if (empty($username) || empty($password)) {
            return null;
        }
        $other = R::findOne('user', ' name = :name', [':name' > $username]);
        if (!is_null($other)) {
            return null;
        }
        $mdp = password_hash($password, PASSWORD_BCRYPT);
        /** @var Model_User $user */
        $user = Model_User::cast(R::dispense('user'));
        $user->username = $username;
        $user->password = $mdp;
        $user->tags = null;
        $user->languages = null;
        $user->role = 0;
        $id = R::store($user);
        return $user;
    }

    /**
     * Delete a user account from the database.
     * The admin account (ID 1) can't be deleted.
     * @param $userid integer
     * @return bool true if a user was deleted else false
     */
    public function deleteUser($userid)
    {
        if ($userid == 1) {
            return false;
        } else {
            /** @var Model_User $user */
            $user = R::load('user', $userid);
            if (!$user->id) {
                return false;
            } else {
                R::trash($user);
                return true;
            }
        }
    }

    /**
     * Update an existing user account.
     * The username cannot be changed and the password must not be empty.
     * @param integer 	$userid 	integer
     * @param string 	$password 	new clear text password or old encrypted password
     * @param string 	$languages 	comma-delimited set of language identifiers
     * @param string 	$tags 		string comma-delimited set of tags
     * @param string 	$role       "1" for admin "0" for normal user
     * @return ?Model_User updated user account or null if there was an error
     */
    public function changeUser($userid, $password, $languages, $tags, $role)
    {
        $user = $this->user($userid);
        if (is_null($user)) {
            return null;
        }
        if (empty($password)) {
            return null;
        } else {
            $mdp = password_hash($password, PASSWORD_BCRYPT);
            if ($password != $user->password) {
                $user->password = $mdp;
            }
            $user->languages = $languages;
            $user->tags = $tags;
            if (strcasecmp($role, "admin") == 0) {
                $user->role = "1";
            } else {
                $user->role = "0";
            }
            try {
                $id = R::store($user);
                return $user;
            } catch (Exception $e) {
                return null;
            }
        }
    }

    /**
     * Find all ID templates in the settings DB
     * @return array id templates
     */
    public function idTemplates()
    {
        return R::findAll('idtemplate', ' order by name');
    }

    /**
     * Find a specific ID template in the settings DB
     * @param string $name 	template name
     * @return ?Model_Idtemplate	IdTemplate or null
     */
    public function idTemplate($name)
    {
        $template = R::findOne('idtemplate', ' name = :name', [':name' => $name]);
        if (!is_null($template)) {
            $template = Model_Idtemplate::cast($template);
        }
        return $template;
    }

    /**
     * Add a new ID template
     * @param string $name 		unique template name
     * @param string $value 	URL template
     * @param string $label 	display label
     * @return Model_Idtemplate template record or null if there was an error
     */
    public function addIdTemplate($name, $value, $label)
    {
        /** @var Model_Idtemplate $template */
        $template = Model_Idtemplate::cast(R::dispense('idtemplate'));
        $template->name = $name;
        $template->val = $value;
        $template->label = $label;
        $id = R::store($template);
        return $template;
    }

    /**
     * Delete an ID template from the database
     * @param string $name 	template namne
     * @return bool true if template was deleted else false
     */
    public function deleteIdTemplate($name)
    {
        $template = $this->idtemplate($name);
        if (!is_null($template)) {
            R::trash($template);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update an existing ID template. The name cannot be changed.
     * @param string $name 		template name
     * @param string $value 	URL template
     * @param string $label 	display label
     * @return ?Model_Idtemplate updated template or null if there was an error
     */
    public function changeIdTemplate($name, $value, $label)
    {
        $template = $this->idtemplate($name);
        if (!is_null($template)) {
            $template->val = $value;
            $template->label = $label;
            try {
                $id = R::store($template);
                return $template;
            } catch (Exception $e) {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Find a Calibre item.
     * @param int 	$calibreType
     * @param int 	$calibreId
     * @return ?Model_Calibrething the Calibre item
     */
    public function getCalibreThing($calibreType, $calibreId)
    {
        $calibreThing = R::findOne(
            'calibrething',
            ' ctype = :type and cid = :id',
            [
                ':type' => $calibreType,
                'id' => $calibreId,
                ]
        );
        if (!is_null($calibreThing)) {
            $calibreThing = Model_Calibrething::cast($calibreThing);
        }
        return $calibreThing;
    }

    /**
     * Add a new reference to a Calibre item.
     *
     * Calibre items are identified by type, ID and name. ID and name
     * are used to find items that can be renamed, like authors.
     *
     * @param int 		$calibreType
     * @param int 		$calibreId
     * @param string 	$calibreName
     * @return Model_Calibrething the Calibre item
     */
    public function addCalibreThing($calibreType, $calibreId, $calibreName)
    {
        /** @var Model_Calibrething $calibreThing */
        $calibreThing = Model_Calibrething::cast(R::dispense('calibrething'));
        $calibreThing->ctype = $calibreType;
        $calibreThing->cid = $calibreId;
        $calibreThing->cname = $calibreName;
        $calibreThing->ownArtefactList = [];
        $calibreThing->refctr = 0;
        $id = R::store($calibreThing);
        return $calibreThing;
    }

    /**
     * Delete an author's thumbnail image.
     *
     * Deletes the thumbnail artefact, and then the CalibreThing if that
     * has no further references.
     *
     * @param int 	$authorId 	Calibre ID of the author
     * @return 	bool	true if deleted, else false
     */
    public function deleteAuthorThumbnail($authorId)
    {
        $ret = true;
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (!is_null($calibreThing)) {
            $artefact = $calibreThing->getAuthorThumbnail();
            if (!is_null($artefact)) {
                $ret = unlink($artefact->url);
                $calibreThing->deleteArtefact($artefact->id);
                $calibreThing->refctr -= 1;
                R::trash($artefact);
                if ($calibreThing->refctr == 0) {
                    R::trash($calibreThing);
                } else {
                    R::store($calibreThing);
                }
            }
        }
        return $ret;
    }

    /**
     * Change the thumbnail image for an author.
     *
     * @param int 		$authorId 	Calibre ID of the author
     * @param string 	$authorName Calibre name of the author
     * @param boolean 	$clipped 	true = image should be clipped, else stuffed
     * @param string 	$file 		File name of the input image
     * @param string 	$mime 		Mime type of the image
     * @return 			bool file name of the thumbnail image, or null
     */
    public function editAuthorThumbnail($authorId, $authorName, $clipped, $file, $mime)
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            $calibreThing = $this->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId, $authorName);
        }

        if (($mime == 'image/jpeg')
        || ($mime == "image/jpg")
        || ($mime == "image/pjpeg")) {
            $png = false;
        } else {
            $png = true;
        }

        $fname = $this->authors_dir . '/author_' . $calibreThing->id . '_thm.png';
        if (file_exists($fname)) {
            unlink($fname);
        }

        if ($clipped) {
            $created = $this->thumbnailClipped($file, $png, self::THUMB_RES, self::THUMB_RES, $fname);
        } else {
            $created = $this->thumbnailStuffed($file, $png, self::THUMB_RES, self::THUMB_RES, $fname);
        }

        $artefact = $calibreThing->getAuthorThumbnail();
        if (is_null($artefact)) {
            /** @var Model_Artefact $artefact */
            $artefact = Model_Artefact::cast(R::dispense('artefact'));
            $artefact->atype = DataConstants::AUTHOR_THUMBNAIL_ARTEFACT;
            $artefact->url = $fname;
            $calibreThing->addArtefact($artefact);
            $calibreThing->refctr += 1;
            R::store($calibreThing);
        }
        return $created;
    }

    /**
     * Get the file name of an author's thumbnail image.
     * @param int 	$authorId 	Calibre ID of the author
     * @return 		?Model_Artefact file name of the thumbnail image, or null
     */
    public function getAuthorThumbnail($authorId)
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            return null;
        } else {
            return $calibreThing->getAuthorThumbnail();
        }
    }

    /**
     * Checks if the thumbnail for a book was already generated.
     * @param int 	$id 	Calibre book ID
     * @return 	bool	true if the thumbnail fiel exists, else false
     */
    public function isTitleThumbnailAvailable($id)
    {
        $thumb_name = 'thumb_' . $id . '.png';
        $thumb_path = $this->thumb_dir . '/' . $thumb_name;
        return file_exists($thumb_path);
    }

    /**
     * Returns the path to a thumbnail of a book's cover image or NULL.
     *
     * If a thumbnail doesn't exist the function tries to make one from the cover.
     * The thumbnail dimension generated is 160*160, which is more than what
     * jQuery Mobile requires (80*80). However, if we send the 80*80 resolution the
     * thumbnails look very pixely.
     *
     * The function expects the input file to be a JPEG.
     *
     * @param  int 		$id 		book id
     * @param  ?string 	$cover 	    path to cover image
     * @param  bool  	$clipped	true = clip the thumbnail, else stuff it
     * @return string thumbnail path or NULL
     */
    public function titleThumbnail($id, $cover, $clipped)
    {
        $thumb_name = 'thumb_' . $id . '.png';
        $thumb_path = $this->thumb_dir . '/' . $thumb_name;
        if (!file_exists($thumb_path)) {
            if (is_null($cover)) {
                $thumb_path = null;
            } else {
                if ($clipped) {
                    $created = $this->thumbnailClipped($cover, false, self::THUMB_RES, self::THUMB_RES, $thumb_path);
                } else {
                    $created = $this->thumbnailStuffed($cover, false, self::THUMB_RES, self::THUMB_RES, $thumb_path);
                }
                if (!$created) {
                    $thumb_path = null;
                }
            }
        }
        return $thumb_path;
    }

    /**
     * Delete existing thumbnail files
     * @return bool false if there was an error
     */
    public function clearThumbnails()
    {
        $cleared = true;
        if ($dh = opendir($this->thumb_dir)) {
            while (($file = readdir($dh)) !== false) {
                $fn = $this->thumb_dir . '/' . $file;
                if (preg_match("/^thumb.*\\.png$/", $file) && file_exists($fn)) {
                    if (!@unlink($fn)) {
                        $cleared = false;
                        break;
                    }
                }
            }
            closedir($dh);
        } else {
            $cleared = false;
        }
        return $cleared;
    }

    /**
     * Return all links defined for an author.
     * @param int 	$authorId 	Calibre ID for the author
     * @return array<Model_Link> author links
     */
    public function authorLinks($authorId)
    {
        $links = [];
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (!is_null($calibreThing)) {
            $links = $calibreThing->getAuthorLinks();
        }
        return $links;
    }

    /**
     * Add a link for an author.
     * @param int 		$authorId 	Calibre ID for author
     * @param string 	$authorName 	Calibre name for author
     * @param string 	$label 		link label
     * @param string 	$url 		link url
     * @return Model_Link 	created author link
     */
    public function addAuthorLink($authorId, $authorName, $label, $url)
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            $calibreThing = $this->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId, $authorName);
        }
        /** @var Model_Link $link */
        $link = Model_Link::cast(R::dispense('link'));
        $link->ltype = DataConstants::AUTHOR_LINK;
        $link->label = $label;
        $link->url = $url;
        $calibreThing->addLink($link);
        $calibreThing->refctr += 1;
        R::store($calibreThing);
        return $link;
    }

    /**
     * Delete a link from the collection defined for an author.
     * @param int 	$authorId 	Calibre ID for author
     * @param int 	$linkId 		ID of the author link
     * @return boolean 			true if the link was deleted, else false
     */
    public function deleteAuthorLink($authorId, $linkId)
    {
        $ret = false;
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (!is_null($calibreThing)) {
            try {
                $link = $calibreThing->ownLinkList[$linkId];
            } catch (Exception $e) {
                $link = null;
            }
            /** @var ?Model_Link $link */
            if (!is_null($link)) {
                $calibreThing->deleteLink($link->id);
                R::trash($link);
                $calibreThing->refctr -= 1;
                if ($calibreThing->refctr == 0) {
                    R::trash($calibreThing);
                } else {
                    R::store($calibreThing);
                }
                $ret = true;
            }
        }
        return $ret;
    }

    /**
     * Get the note text fro an author.
     * @param int 	$authorId 	Calibre ID of the author
     * @return 		?Model_Note 		note text or null
     */
    public function authorNote($authorId)
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            return null;
        } else {
            return $calibreThing->getAuthorNote();
        }
    }

    /**
     * Set the note text for an author.
     * @param int 		$authorId 	Calibre ID for author
     * @param string 	$authorName 	Calibre name for author
     * @param string 	$mime 		mime type for the note's content
     * @param string 	$noteText	note content
     * @return Model_Note 	created/edited note
     */
    public function editAuthorNote($authorId, $authorName, $mime, $noteText)
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            $calibreThing = $this->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId, $authorName);
        }
        $note = $calibreThing->getAuthorNote();
        if (is_null($note)) {
            /** @var Model_Note $note */
            $note = Model_Note::cast(R::dispense('note'));
            $note->ntype = DataConstants::AUTHOR_NOTE;
            $note->mime = $mime;
            $note->ntext = $noteText;
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
     * Delete the note for an author
     * @param int 	$authorId 	Calibre ID for author
     * @return boolean 			true if the note was deleted, else false
     */
    public function deleteAuthorNote($authorId)
    {
        $ret = false;
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (!is_null($calibreThing)) {
            $note = $calibreThing->getAuthorNote();
            if (!is_null($note)) {
                $calibreThing->deleteNote($note->id);
                $calibreThing->refctr -= 1;
                R::trash($note);
                if ($calibreThing->refctr == 0) {
                    R::trash($calibreThing);
                } else {
                    R::store($calibreThing);
                }
            }
            $ret = true;
        }
        return $ret;
    }


    ############################### internal stuff ##############################################

    /**
     * Create a square thumbnail by clipping the largest possible square from the cover
     * @param  string 	$cover      path to input image
     * @param  bool 	$png      	true if the input is a PNG file, false = JPEG
     * @param  int 	 	$newwidth   required thumbnail width
     * @param  int 		$newheight  required thumbnail height
     * @param  string 	$thumb_path path for thumbnail storage
     * @return bool             	true = thumbnail created, else false
     */
    private function thumbnailClipped($cover, $png, $newwidth, $newheight, $thumb_path)
    {
        [$width, $height] = getimagesize($cover);
        $thumb = imagecreatetruecolor($newwidth, $newheight);
        if ($png) {
            $source = imagecreatefrompng($cover);
        } else {
            $source = imagecreatefromjpeg($cover);
        }
        $minwh = min([$width, $height]);
        $newx = ($width / 2) - ($minwh / 2);
        $newy = ($height / 2) - ($minwh / 2);
        $inbetween = imagecreatetruecolor($minwh, $minwh);
        imagecopy($inbetween, $source, 0, 0, $newx, $newy, $minwh, $minwh);
        imagecopyresized($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $minwh, $minwh);
        $created = imagepng($thumb, $thumb_path);
        return $created;
    }

    /**
     * Create a square thumbnail by stuffing the cover at the edges
     * @param  string 	$cover      path to input image
     * @param  bool 	$png      	true if the input is a PNG file, false = JPEG
     * @param  int 	 	$newwidth   required thumbnail width
     * @param  int 		$newheight  required thumbnail height
     * @param  string 	$thumb_path path for thumbnail storage
     * @return bool             	true = thumbnail created, else false
     */
    private function thumbnailStuffed($cover, $png, $newwidth, $newheight, $thumb_path)
    {
        [$width, $height] = getimagesize($cover);
        $thumb = $this->transparentImage($newwidth, $newheight);
        if ($png) {
            $source = imagecreatefrompng($cover);
        } else {
            $source = imagecreatefromjpeg($cover);
        }
        $dstx = 0;
        $dsty = 0;
        $maxwh = max([$width, $height]);
        if ($height > $width) {
            $diff = $maxwh - $width;
            $dstx = (int) $diff / 2;
        } else {
            $diff = $maxwh - $height;
            $dsty = (int) $diff / 2;
        }
        $inbetween = $this->transparentImage($maxwh, $maxwh);
        imagecopy($inbetween, $source, $dstx, $dsty, 0, 0, $width, $height);
        imagecopyresampled($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $maxwh, $maxwh);
        $created = imagepng($thumb, $thumb_path);
        imagedestroy($thumb);
        imagedestroy($inbetween);
        imagedestroy($source);
        return $created;
    }

    /**
     * Create an image with transparent background.
     *
     * see http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php#279310
     *
     * @param  int 	$width
     * @param  int 	$height
     * @return object|resource image
     */
    private function transparentImage($width, $height)
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $backgr = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $backgr);
        return $img;
    }
}
