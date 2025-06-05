<?php

namespace Imarc\Millyard\Commands;

abstract class Command
{
    public string $name;

    public string $shortDescription = '';

    public string $longDescription = '';

    public array $synopsis = [];

    public string $when = 'after_wp_load';

    protected function line($message = '')
    {
        \WP_CLI::line($message);
    }

    protected function success($message = '')
    {
        \WP_CLI::success($message);
    }

    protected function error($message, $exit = true)
    {
        \WP_CLI::error($message, $exit);
    }

    protected function warning($message = '')
    {
        \WP_CLI::warning($message);
    }

    protected function log($message = '')
    {
        \WP_CLI::log($message);
    }

    protected function confirm($question, $assoc_args)
    {
        return \WP_CLI::confirm($question, $assoc_args);
    }
}
