<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\AppData;

/**
 * RedBeanPHP FUSE model for beans
 */
class Model_Type extends \RedBeanPHP\TypedModel implements \JsonSerializable
{
    /** @var string[] */
    protected $_filterProps = [];

    public function jsonSerialize(): mixed
    {
        if (empty($this->_filterProps)) {
            return $this->unbox()->getProperties();
        }
        $blacklist = array_flip($this->_filterProps);
        return array_diff_key($this->unbox()->getProperties(), $blacklist);
    }
}
