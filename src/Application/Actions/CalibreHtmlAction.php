<?php

namespace App\Application\Actions;

use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\BicBucStriim\L10n;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Calibre\CalibreRepository;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

abstract class CalibreHtmlAction extends RenderHtmlAction
{
    /**
     * @var CalibreRepository
     */
    protected CalibreRepository $calibre;

    /**
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     * @param CalibreRepository $calibre
     * @param Configuration $config
     * @param Twig $twig
     * @param L10n $l10n
     */
    public function __construct(
        LoggerInterface $logger,
        BicBucStriimRepository $bbs,
        CalibreRepository $calibre,
        Configuration $config,
        Twig $twig,
        L10n $l10n
    ) {
        parent::__construct($logger, $bbs, $config, $twig, $l10n);
        $this->calibre = $calibre;
    }

    /**
     * Create a Calibre Filter according to the current user's
     * language and tag settings.
     * @return CalibreFilter
     */
    protected function getFilter(): CalibreFilter
    {
        $lang = null;
        $tag = null;
        $user = $this->user;
        if (!empty($user->getLanguages())) {
            $lang = $this->calibre->getLanguageId($user->getLanguages());
        }
        if (!empty($user->getTags())) {
            $tag = $this->calibre->getTagId($user->getTags());
        }
        return new CalibreFilter($lang, $tag);
    }
}
