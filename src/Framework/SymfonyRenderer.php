<?php

namespace BicBucStriim\Framework;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SymfonyRenderer extends AbstractController implements RendererInterface
{
    public function render(string $template, array $data = []): string
    {
        // The render() method in Symfony's AbstractController returns a Response,
        // but we can get the content from it.
        return parent::render($template, $data)->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function prependPath(string $path): void
    {
        if ($this->container->has('twig')) {
            /** @var \Twig\Environment $twig */
            $twig = $this->container->get('twig');
            $twig->getLoader()->prependPath($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFunction(string $name, callable $callback): void
    {
        if ($this->container->has('twig')) {
            /** @var \Twig\Environment $twig */
            $twig = $this->container->get('twig');
            $filter = new \Twig\TwigFilter($name, $callback);
            $twig->addFilter($filter);
        }
    }
}
