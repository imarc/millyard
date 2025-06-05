<?php

namespace Imarc\Millyard\Providers;

use Imarc\Millyard\Routing\Router;
use League\Container\ServiceProvider\AbstractServiceProvider;

class RouteServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === Router::class;
    }

    public function register(): void
    {
        $this->getContainer()->add(Router::class, function () {
            return Router::getInstance();
        });
    }
}
