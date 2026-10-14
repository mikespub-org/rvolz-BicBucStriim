<?php

declare(strict_types=1);

use BicBucStriim\Framework\LaravelRenderer;
use BicBucStriim\Framework\SymfonyRenderer;
use BicBucStriim\Framework\TwigRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the three Twig-based RendererInterface implementations.
 *
 * TwigRenderer, LaravelRenderer and the reworked SymfonyRenderer all take a
 * Twig\Environment via constructor injection, so they are testable without
 * booting Slim, Laravel or symfony/framework-bundle.
 */
#[CoversClass(TwigRenderer::class)]
#[CoversClass(LaravelRenderer::class)]
#[CoversClass(SymfonyRenderer::class)]
class RendererTest extends PHPUnit\Framework\TestCase
{
    /** @return array{string, \Twig\Environment} */
    private function makeTwig(array $templates): array
    {
        $dir = sys_get_temp_dir() . '/bbs-renderer-' . uniqid();
        mkdir($dir);
        foreach ($templates as $name => $content) {
            file_put_contents($dir . '/' . $name, $content);
        }
        $twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader($dir));
        return [$dir, $twig];
    }

    public function testRenderDelegatesToTwig(): void
    {
        foreach (['twig', 'laravel', 'symfony'] as $label) {
            [$dir, $twig] = $this->makeTwig(['hello.twig' => 'hi {{ name }}']);
            $renderer = $this->makeRenderer($label, $twig);
            $this->assertSame('hi bob', $renderer->render('hello.twig', ['name' => 'bob']), $label);
        }
    }

    public function testPrependPathTakesPrecedence(): void
    {
        $override = sys_get_temp_dir() . '/bbs-renderer-override-' . uniqid();
        mkdir($override);
        file_put_contents($override . '/hello.twig', 'override');
        foreach (['twig', 'laravel', 'symfony'] as $label) {
            [$dir, $twig] = $this->makeTwig(['hello.twig' => 'base']);
            $renderer = $this->makeRenderer($label, $twig);
            $renderer->prependPath($override);
            $this->assertSame('override', $renderer->render('hello.twig'), $label);
        }
    }

    public function testAddFunctionRegistersFilter(): void
    {
        // Twig freezes filters on first render, so each renderer gets a fresh
        // environment and registers its filter before rendering.
        foreach (['twig', 'laravel', 'symfony'] as $label) {
            [$dir, $twig] = $this->makeTwig(['hello.twig' => "{{ 'ab'|shout }}"]);
            $renderer = $this->makeRenderer($label, $twig);
            $renderer->addFunction('shout', fn(string $s): string => strtoupper($s) . '!');
            $this->assertSame('AB!', $renderer->render('hello.twig'), $label);
        }
    }

    private function makeRenderer(string $label, \Twig\Environment $twig): \BicBucStriim\Framework\RendererInterface
    {
        return match ($label) {
            'laravel' => new LaravelRenderer($twig),
            'symfony' => new SymfonyRenderer($twig),
            default => new TwigRenderer($twig),
        };
    }
}
