<?php

namespace Imarc\Millyard\Commands;

use Imarc\Millyard\Attributes\RegistersCommand;
use Imarc\Millyard\Concerns\DiscoversClasses;

class Registrar
{
    use DiscoversClasses;

    public function registerCommands(string $path = 'Commands'): void
    {
        $commandClasses = $this->discoverClassesForAttribute(RegistersCommand::class, $path);

        foreach ($commandClasses as $commandClass) {
            $this->registerCommand($commandClass);
        }
    }

    public function registerCommand(string $commandClass): void
    {
        $command = new $commandClass();

        if (! (defined('WP_CLI') && constant('WP_CLI'))) {
            return;
        }

        \WP_CLI::add_command($command->name, $command, [
            'shortdesc' => $command->shortDescription,
            'longdesc' => $command->longDescription,
            'when' => $command->when,
        ]);

        do_action('millyard_command_registered', $commandClass);
    }
}