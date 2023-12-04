<?php

class R extends RedBean_Facade
{
    public static function graph($array, $filterEmpty = false)
    {
        $c = new RedBean_Plugin_Cooker();
        $c->setToolbox(self::$toolbox);
        return $c->graph($array, $filterEmpty);
    }
}
