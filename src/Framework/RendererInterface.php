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

    /**
     * Prepends a path for custom templates.
     *
     * @param string $path The directory path to prepend.
     */
    public function prependPath(string $path): void;

    /**
     * Adds a filter to the template engine.
     *
     * @param string $name The name of the filter.
     * @param callable $callback The callable that implements the filter.
     */
    public function addFilter(string $name, callable $callback): void;

    /**
     * Adds a function to the template engine.
     *
     * @param string $name The name of the function.
     * @param callable $callback The callable that implements the function.
     */
    public function addFunction(string $name, callable $callback): void;
}
