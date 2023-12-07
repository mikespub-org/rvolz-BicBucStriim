<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Middleware;

class DefaultMiddleware extends \Slim\Middleware implements \BicBucStriim\Traits\AppInterface
{
    use \BicBucStriim\Traits\AppTrait;

    /** @var \BicBucStriim\App */
    protected $app;

    /**
     * Call next
     */
    public function call()
    {
        $this->next->call();
    }
}
