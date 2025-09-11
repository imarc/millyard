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
        if (! is_dir($blockDirectory)) {
            mkdir($blockDirectory, 0o755, true);
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

    /**
     * @subcommand make-post-type
     */
    public function makePostType($args, $assoc_args)
    {
        $label = $this->prompt('Singular label');
        $pluralLabel = $this->prompt('Plural label', $label . 's');
        $slug = $this->prompt('Slug', Str::slug($label));
        $class = $this->prompt('Class name', Str::pascal($label));

        $postTypeDirectory = sprintf('%s/app/PostTypes', get_template_directory());
        if (! is_dir($postTypeDirectory)) {
            mkdir($postTypeDirectory, 0o755, true);
        }

        $postTypeFile = sprintf('%s/%s.php', $postTypeDirectory, $class);
        if (file_exists($postTypeFile)) {
            $this->error('Post type class file already exists!');

            return;
        }

        $postTypeStub = $this->getStub('PostType', [
            '{{ class }}' => $class,
            '{{ slug }}' => $slug,
            '{{ label }}' => $label,
            '{{ pluralLabel }}' => $pluralLabel,
        ]);

        file_put_contents($postTypeFile, $postTypeStub);

        $this->line('Post type class file created.');
    }

    /**
     * @subcommand make-taxonomy
     */
    public function makeTaxonomy($args, $assoc_args)
    {
        $label = $this->prompt('Singular label');
        $pluralLabel = $this->prompt('Plural label', $label . 's');
        $slug = $this->prompt('Slug', Str::slug($label));
        $class = $this->prompt('Class name', Str::pascal($label));

        $taxonomyDirectory = sprintf('%s/app/Taxonomies', get_template_directory());
        if (! is_dir($taxonomyDirectory)) {
            mkdir($taxonomyDirectory, 0o755, true);
        }

        $taxonomyFile = sprintf('%s/%s.php', $taxonomyDirectory, $class);
        if (file_exists($taxonomyFile)) {
            $this->error('Taxonomy class file already exists!');

            return;
        }

        $taxonomyStub = $this->getStub('Taxonomy', [
            '{{ class }}' => $class,
            '{{ slug }}' => $slug,
            '{{ label }}' => $label,
            '{{ pluralLabel }}' => $pluralLabel,
        ]);

        file_put_contents($taxonomyFile, $taxonomyStub);

        $this->line('Taxonomy class file created.');
    }

    protected function getStub(string $name, array $replacements = []): string
    {
        $stubFile = realpath(sprintf('%s/../stubs/%s.stub', __DIR__, $name));

        if (! $stubFile) {
            throw new \Exception(sprintf('Stub file %s not found', $name));
        }

        $stub = file_get_contents($stubFile);

        foreach ($replacements as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }
}
