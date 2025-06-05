<?php

namespace Imarc\Millyard\PostTypes;

use Imarc\Millyard\Attributes\RegistersPostType;
use Imarc\Millyard\Concerns\DiscoversClasses;

class Registrar
{
    use DiscoversClasses;

    public function registerPostTypes(string $path = 'PostTypes'): void
    {
        $classes = $this->discoverClassesForAttribute(RegistersPostType::class, $path);

        foreach ($classes as $postTypeClass) {
            $this->registerPostType($postTypeClass);
        }
    }

    public function registerPostType(string $postTypeClass): void
    {
        $postType = new $postTypeClass();

        if (! method_exists($postType, 'register')) {
            throw new \RuntimeException(sprintf('Could not register class %s. register() does not exist', $postTypeClass));
        }

        $postType->register();

        do_action('millyard_post_type_registered', $postTypeClass);
    }
}