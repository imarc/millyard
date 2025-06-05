<?php

namespace Imarc\Millyard\Views;

use Imarc\Millyard\Services\Container;

/**
 * Registry for view composers.
 *
 * This class manages the registration and application of view composers, which are classes
 * that add data to the context of specific Twig templates. View composers are registered
 * with specific template names and are automatically applied when those templates are rendered.
 *
 * The registry works in conjunction with the `timber/render/data` filter to inject
 * composer data into the template context. It also supports the custom `{% render_partial %}`
 * Twig tag which ensures composers are properly applied to partial templates.
 *
 * @see \Imarc\Millyard\ViewComposers\ViewComposer
 * @see \Imarc\Millyard\Twig\RenderPartialTokenParser
 * @see \Imarc\Millyard\Twig\RenderPartialNode
 */
class ComposerRegistry
{
    private array $composers = [];

    public function __construct(private Container $container)
    {
    }

    public function registerComposer(string $composerClass): void
    {
        if (! is_subclass_of($composerClass, Composer::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Composer class %s must extend %s',
                $composerClass,
                Composer::class
            ));
        }

        $composer = $this->container->get($composerClass);

        foreach ($composer->views as $view) {
            $this->composers[$view] = $composerClass;
        }
    }

    public function getComposers(): array
    {
        return $this->composers;
    }

    public function hasComposers(): bool
    {
        return ! empty($this->composers);
    }

    /**
     * Filter the data for composers. This is registered as a
     * timber/render/data filter.
     */
    public function filterDataForComposers(array $data, string $template): array
    {
        foreach ($this->composers as $view => $composerClass) {
            if ($view === $template) {
                $composer = $this->container->get($composerClass);
                $composer->setContextData($data);
                $data = [...$data, ...$composer->withContext()];
            }
        }

        return $data;
    }
}
