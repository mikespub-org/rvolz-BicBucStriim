<?php

namespace BicBucStriim\AppData;

class R extends \RedBeanPHP\Facade
{
    /**
     * graph() was removed in RedBeanPHP version 4 and is now a legacy plugin
     * Source code available from https://github.com/gabordemooij/RB4Plugins
    public static function graph($array, $filterEmpty = false)
    {
        $c = new \RedBeanPHP\Plugin\Cooker();
        $c->setToolbox(self::$toolbox);
        return $c->graph($array, $filterEmpty);
    }
     */
}
