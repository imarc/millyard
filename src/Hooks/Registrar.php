<?php

namespace Imarc\Millyard\Hooks;

use Imarc\Millyard\Services\Container;

class Registrar
{
    public function __construct(
        private Container $container
    ) {

    }

    public function register(string $hooksClass): void
    {
        $hooks = $this->container->get($hooksClass);

        if (! method_exists($hooks, 'initialize')) {
            throw new \RuntimeException(sprintf('Could not initialize class %s. initialize() does not exist', $hooksClass));
        }

        $hooks->initialize();
    }
}
