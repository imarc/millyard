<?php

namespace Imarc\Millyard\Providers;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class HttpServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === Request::class;
    }

    public function register(): void
    {
        $this->getContainer()->add(Request::class, function () {
            return Request::createFromGlobals();
        });
    }
}
