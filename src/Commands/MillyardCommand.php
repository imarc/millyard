<?php

namespace Imarc\Millyard\Commands;

use Imarc\Millyard\Attributes\RegistersCommand;
use Imarc\Millyard\Support\Str;

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

    /**
     * @subcommand make-block
     */
    public function makeBlock($args, $assoc_args)
    {
        $title = $this->prompt('Descriptive name');
        $slug = $this->prompt('Slug', Str::slug($title));
        $class = $this->prompt('Class name', Str::pascal($title));
        $templateFile = Str::kebab($slug).'.twig';

        // make sure the directory exists
        $blockDirectory = sprintf('%s/app/Blocks', get_template_directory());
        if (!is_dir($blockDirectory)) {
            mkdir($blockDirectory, 0755, true);
        }

        // make sure the class file doesn't exist
        $blockFile = sprintf('%s/%s.php', $blockDirectory, $class);
        if (file_exists($blockFile)) {
            $this->error('Block class file already exists!');
            return;
        }

        // create the class file.
        $classStub = $this->getStub('Block', [
            '{{ class }}' => $class,
            '{{ slug }}' => $slug,
            '{{ title }}' => $title,
        ]);
        file_put_contents($blockFile, $classStub);

        // create the template file.
        $templateStub = $this->getStub('BlockTemplate');
        $templateFile = sprintf('%s/templates/blocks/%s', get_template_directory(), $templateFile);
        if (file_exists($templateFile)) {
            $this->error('Block template file already exists!');
            return;
        }

        file_put_contents($templateFile, $templateStub);

        $this->line('Block class and template file created.');
    }

    protected function getStub(string $name, array $replacements = []): string
    {
        $stubFile = realpath(sprintf('%s/../stubs/%s.stub', __DIR__, $name));

        if (!$stubFile) {
            throw new \Exception(sprintf('Stub file %s not found', $name));
        }

        $stub = file_get_contents($stubFile);

        foreach ($replacements as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }
}
