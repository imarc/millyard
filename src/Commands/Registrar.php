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

        if (! method_exists($command, 'register')) {
            throw new \RuntimeException(sprintf('Could not register class %s. register() does not exist', $commandClass));
        }

        $command->register();
        do_action('millyard_command_registered', $commandClass);
    }
}