<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\AppData;

use BicBucStriim\Models\R;
use BicBucStriim\Traits\HasAuthors;
use BicBucStriim\Traits\HasBooks;
use BicBucStriim\Traits\HasConfigs;
use BicBucStriim\Traits\HasIdTemplates;
use BicBucStriim\Traits\HasSeries;
use BicBucStriim\Traits\HasTags;
use BicBucStriim\Traits\HasUsers;
use PDO;

class BicBucStriim
{
    use HasConfigs;
    use HasUsers;
    use HasIdTemplates;
    use HasAuthors;
    use HasSeries;
    use HasBooks;
    use HasTags;

    # Name to the bbs db
    public const DBNAME = 'data.db';

    # bbs sqlite db
    public $mydb = null;
    # last sqlite error
    public $last_error = 0;
    # dir for bbs db
    public $dataDir = '';
    # dir for generated title thumbs
    public $titlesDir = '';
    # dir for generated author thumbs
    public $authorsDir = '';

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
        $this->dataDir = dirname($dataPath);
        $this->titlesDir = $this->dataDir . '/titles';
        if (!file_exists($this->titlesDir)) {
            mkdir($this->titlesDir);
        }
        $this->authorsDir = $this->dataDir . '/authors';
        if (!file_exists($this->authorsDir)) {
            mkdir($this->authorsDir);
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
        $schema = file_get_contents($this->dataDir . '/schema.sql');
        $this->mydb = new PDO('sqlite:' . $dataPath, null, null, []);
        //$this->mydb->setAttribute(1002, 'SET NAMES utf8');
        $this->mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->mydb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->mydb->exec($schema);
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
        return !is_null($this->mydb);
    }
}
