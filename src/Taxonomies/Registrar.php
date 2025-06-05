<?php

namespace Imarc\Millyard\Taxonomies;

use Imarc\Millyard\Attributes\RegistersTaxonomy;
use Imarc\Millyard\Concerns\DiscoversClasses;

class Registrar
{
    use DiscoversClasses;

    public function registerTaxonomies(string $path = 'Taxonomies'): void
    {
        $classes = $this->discoverClassesForAttribute(RegistersTaxonomy::class, $path);

        foreach ($classes as $taxonomyClass) {
            $this->registerTaxonomy($taxonomyClass);
        }
    }

    public function registerTaxonomy(string $taxonomyClass): void
    {
        $taxonomy = new $taxonomyClass();

        if (! method_exists($taxonomy, 'register')) {
            throw new \RuntimeException(sprintf('Could not register class %s. register() does not exist', $taxonomyClass));
        }

        $taxonomy->register();

        do_action('millyard_taxonomy_registered', $taxonomyClass);
    }
}