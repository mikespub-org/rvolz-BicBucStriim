<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\Actions\ActionRegistry;
use BicBucStriim\Framework\RendererInterface;

/**
 * Summary of LinkGenerator
 */
class LinkGenerator
{
    # dir for bbs db and thumbnails (relative to root)
    public string $dataUri = 'data';
    public int $version = 1;

    public function __construct(private readonly ActionRegistry $registry) {}

    public function setDataUri(string $dataUri): void
    {
        $this->dataUri = $dataUri;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function addFunctions(RendererInterface $renderer, int $version = 0): void
    {
        if (!empty($version)) {
            $this->setVersion($version);
        }
        $renderer->addFunction('bbs_path', $this->getPath(...));
        $renderer->addFunction('bbs_file', $this->getDataLink(...));
        $renderer->addFunction('bbs_kindle', $this->getDataKindle(...));
        $renderer->addFunction('bbs_title', $this->getBookLink(...));
        $renderer->addFunction('bbs_cover', $this->getBookCover(...));
        $renderer->addFunction('bbs_thumb', $this->getBookThumb(...));
    }

    /**
     * Summary of getPath
     * @param array<string, mixed> $params
     */
    public function getPath(string $routeName, array $params = []): string
    {
        return RouteUtil::getPathFromRouteMap($this->registry->getRouteMap(), $routeName, $params);
    }

    public function getDataLink(\BicBucStriim\Calibre\Data $data): string
    {
        if ($this->version == 2) {
            return $this->getPath('main-book-v2', ['id' => $data->book, 'format' => strtolower($data->format), 'fname' => $data->name]);
        }
        return $this->getPath('main-book', ['id' => $data->book, 'file' => $data->name . '.' . strtolower($data->format)]);
    }

    public function getDataKindle(\BicBucStriim\Calibre\Data $data): string
    {
        return $this->getPath('main-kindle', ['id' => $data->book, 'file' => $data->name . '.' . strtolower($data->format)]);
    }

    public function getBookLink(\BicBucStriim\Calibre\Book $book): string
    {
        return $this->getPath('main-title', ['id' => $book->id]);
    }

    public function getBookCover(\BicBucStriim\Calibre\Book $book): string
    {
        if ($this->version == 2) {
            return $this->getPath('main-cover-v2', ['id' => $book->id]);
        }
        return $this->getPath('main-cover-v1', ['id' => $book->id]);
    }

    public function getBookThumb(\BicBucStriim\Calibre\Book $book): string
    {
        if (!empty($book->thumbnail)) {
            return "/{$this->dataUri}/titles/thumb_{$book->id}.png";
        }
        if ($this->version == 2) {
            return $this->getPath('main-thumbnail-v2', ['id' => $book->id]);
        }
        return $this->getPath('main-thumbnail-v1', ['id' => $book->id]);
    }
}
