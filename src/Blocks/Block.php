<?php

namespace Imarc\Millyard\Blocks;

use Timber\Timber;

abstract class Block
{
    public const NAME = '';
    public const TITLE = '';
    public const CATEGORY = '';
    public const ICON = '';
    public const POST_TYPES = [];
    public const KEYWORDS = [];

    protected array $context = [];

    protected function getConfig(): array
    {
        return [];
    }

    public function register(): void
    {
        if (function_exists('acf_register_block_type')) {
            acf_register_block_type($this->getMergedConfig());
        }
    }

    public function render($block, $content = '', $isPreview = false, $postId = 0)
    {
        $this->context = Timber::context();

        $this->context['is_preview'] = $isPreview;
        $this->context['block'] = get_fields();
        $this->context = array_merge($this->context, $this->withContext());

        Timber::render($this->getTemplatePath(), $this->context);
    }

    public function withContext(): array
    {
        return [];
    }

    private function getTemplatePath(): string
    {
        return 'blocks/' . static::NAME . '.twig';
    }

    private function getMergedConfig(): array
    {
        return wp_parse_args($this->getConfig(), [
            'name' => static::NAME,
            'title' => static::TITLE,
            'category' => static::CATEGORY,
            'keywords' => static::KEYWORDS,
            'icon' => static::ICON,
            'supports' => [
                'align' => false,
                'anchor' => true,
            ],
            'post_types' => static::POST_TYPES,
            'render_callback' => [$this, 'render'],
        ]);
    }
}
