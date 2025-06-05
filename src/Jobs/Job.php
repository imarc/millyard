<?php

namespace Imarc\Millyard\Jobs;

use Imarc\Millyard\Services\Container;

abstract class Job
{
    public static function dispatch(...$args): Dispatcher
    {
        $container = Container::getInstance();
        $dispatcher = $container->get(Dispatcher::class);

        return $dispatcher->args($args)
            ->dispatch(static::class);
    }

    public function getName(): string
    {
        return $this->jobName ?? $this->generateName();
    }

    private function generateName(): string
    {
        $name = str_replace('App\\Jobs\\', '', static::class);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
