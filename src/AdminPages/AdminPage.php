<?php

namespace Imarc\Millyard\AdminPages;

use Timber\Timber;

abstract class AdminPage
{
    protected string $slug = '';

    protected string $title = '';

    protected string $capability = 'manage_options';

    protected int $menuPosition = 10;

    protected string $icon = '';

    protected string $parentSlug = '';

    protected ?string $template = null;

    public function register(): void
    {
        if ($this->parentSlug) {
            add_submenu_page(
                $this->parentSlug,
                $this->title,
                $this->title,
                $this->capability,
                $this->slug,
                [$this, 'render'],
                $this->menuPosition,
            );
        } else {
            add_menu_page(
                $this->title,
                $this->title,
                $this->capability,
                $this->slug,
                [$this, 'render'],
                $this->icon,
                $this->menuPosition
            );
        }
    }

    public function render(): void
    {
        if ($this->template) {
            $this->renderTwigTemplate();
        }
    }

    public function withContext(): array
    {
        return [];
    }

    protected function renderTwigTemplate(): void
    {
        $context = Timber::context();
        $context['title'] = $this->title;
        $context = array_merge($context, $this->withContext());

        Timber::render($this->template, $context);
    }
}
