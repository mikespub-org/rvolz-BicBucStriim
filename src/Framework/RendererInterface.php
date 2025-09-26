<?php

namespace BicBucStriim\Framework;

/**
 * Defines the contract for a template rendering service.
 */
interface RendererInterface
{
    /**
     * Renders a template and returns the content as a string.
     *
     * @param string $template The template name.
     * @param array<string, mixed> $data The data to pass to the template.
     */
    public function render(string $template, array $data = []): string;
}
