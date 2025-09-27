<?php

namespace BicBucStriim\Framework;

use Twig\Environment;

/**
 * An implementation of RendererInterface that uses Twig.
 */
class TwigRenderer implements RendererInterface
{
    private Environment $twig;

    public function __construct(Environment $twig)
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

    /**
     * {@inheritdoc}
     */
    public function addFunction(string $name, callable $callback): void
    {
        $filter = new \Twig\TwigFilter($name, $callback);
        $this->twig->addFilter($filter);
    }
}
