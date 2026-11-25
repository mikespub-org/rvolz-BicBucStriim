<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\Actions\ActionRegistry;

/**
 * Summary of LinkGenerator
 */
class LinkGenerator
{
    public function __construct(private ActionRegistry $registry) {}

    /**
     * Summary of getPath
     * @param array<string, mixed> $params
     */
    public function getPath(string $routeName, array $params = []): string
    {
        return RouteUtil::getPathFromRouteMap($this->registry->getRouteMap(), $routeName, $params);
    }

    /**
     * Summary of getLink - @todo
     */
    public function getLink(object $object): string
    {
        return match ($object::class) {
            \BicBucStriim\Calibre\Data::class => $this->getDataLink($object),
            default => 'TODO get link for ' . $object::class,
        };
    }

    public function getDataLink(\BicBucStriim\Calibre\Data $data): string
    {
        return $this->getPath('main-book', ['id' => $data->book, 'file' => $data->name . '.' . strtolower($data->format)]);
    }
}
