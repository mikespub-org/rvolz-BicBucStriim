<?php

namespace App\Application\Actions\Authors;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\Author;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewAuthorsAction extends AuthorsAction
{
    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $index = 0;
        if ($this->hasQueryParam('index')) {
            $index = (int) $this->resolveQueryParam('index');
        }
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewAuthorsAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $search = '';
        if ($this->hasQueryParam('search')) {
            $search = $this->resolveQueryParam('search');
            $tl = $this->calibre->authorsSlice($index, $this->config[AppConstants::PAGE_SIZE], trim($search));
        } else {
            $tl = $this->calibre->authorsSlice($index, $this->config[AppConstants::PAGE_SIZE]);
        }

        foreach ($tl['entries'] as $author) {
            $author->thumbnail = $this->bbs->getAuthorThumbnail($author->id);
        }
        return $this->respondWithPage('authors.html', [
            'page' => $this->mkPage($this->getMessageString('authors'), 3, 1),
            'url' => 'authors',
            'authors' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search]);
    }
}
