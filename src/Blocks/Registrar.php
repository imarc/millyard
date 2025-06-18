<?php

namespace Imarc\Millyard\Blocks;

use Imarc\Millyard\Attributes\RegistersBlock;
use Imarc\Millyard\Concerns\DiscoversClasses;
use Imarc\Millyard\Services\Container;
use WP_Block_Type_Registry;

class Registrar
{
    use DiscoversClasses;

    public function __construct(private Container $container)
    {
    }

    public function registerBlocks(string $path = 'Blocks'): void
    {
        $blockClasses = $this->discoverClassesForAttribute(RegistersBlock::class, $path);

        foreach ($blockClasses as $blockClass) {
            $this->registerBlock($blockClass);
        }
    }

    public function registerBlock(string $blockClass): void
    {
        $block = $this->container->get($blockClass);

        if (! method_exists($block, 'register')) {
            throw new \RuntimeException(sprintf('Could not register class %s. register() does not exist', $blockClass));
        }

        $block->register();
        do_action('millyard_block_registered', $blockClass);
    }

    public function deregisterBlocks(array $blockSlugs): void
    {
        foreach ($blockSlugs as $blockSlug) {
            unregister_block_type($blockSlug);
        }
    }

    public function getRegisteredBlocks(): array
    {
        $registeredBlocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

        return array_keys($registeredBlocks ?: []);
    }
}