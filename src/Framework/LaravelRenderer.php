<?php

declare(strict_types=1);

namespace BicBucStriim\Framework;

use Twig\Environment as TwigEnvironment;

/**
 * An implementation of RendererInterface that uses Twig within a Laravel application.
 */
class LaravelRenderer implements RendererInterface
{
    private TwigEnvironment $twig;

    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prependPath(string $path): void
    {
        $this->twig->getLoader()->prependPath($path);
    }
}
