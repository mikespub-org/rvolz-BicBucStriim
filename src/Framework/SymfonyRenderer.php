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
}
