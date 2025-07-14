<?php

namespace Imarc\Millyard\Commands;

use Imarc\Millyard\Attributes\RegistersCommand;

#[RegistersCommand]
class MillyardCommand extends Command
{
    public string $name = 'millyard';

    public string $shortDescription = 'Millyard commands';

    /**
     * @subcommand flush-rewrite-rules
     */
    public function flushRewriteRules($args, $assoc_args)
    {
        $this->line('Flushing rewrite rules...');

        flush_rewrite_rules();

        $this->line('Rewrite rules flushed!');
    }
}
