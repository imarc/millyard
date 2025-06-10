<?php

namespace Imarc\Millyard\Taxonomies;

use Imarc\Millyard\Concerns\RegistersHooks;

/**
 * This is an abstract class for creating taxonomies. Each taxonomy
 * should extend this class and use the RegistersTaxonomy attribute.
 */
abstract class Taxonomy
{
    use RegistersHooks;

    public const SLUG = '';

    public string $singularLabel;

    public string $pluralLabel;

    protected array $args = [];

    protected array $labels = [];

    protected array $postTypes = [];

    protected bool $registersTopLevelMenuItem = false;

    protected ?string $menuItemName = null;

    protected ?string $menuItemIcon = null;

    protected ?int $menuItemPosition = null;

    protected bool $isHierarchical = true;

    protected function getDefaultArgs(): array
    {
        return [
            'labels' => $this->getLabels(),
            'description' => '',
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => $this->isHierarchical,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'rest_base' => static::SLUG,
            'rest_namespace' => 'wp/v2',
            'rest_controller_class' => 'WP_REST_Terms_Controller',
            'show_tagcloud' => false,
            'show_in_quick_edit' => false,
            'show_admin_column' => false,
            'meta_box_cb' => $this->isHierarchical ? 'post_categories_meta_box' : 'post_tags_meta_box',
            'meta_box_sanitize_cb' => null,
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
            'rewrite' => [
                'slug' => static::SLUG,
            ],
            'query_var' => static::SLUG,
            'default_term' => [],
            'sort' => false,
            'args' => [],
        ];
    }

    protected function getDefaultLabels(): array
    {
        $lowercasePluralLabel = strtolower($this->pluralLabel);
        $lowercaseSingularLabel = strtolower($this->singularLabel);

        return [
            'name' => $this->pluralLabel,
            'singular_name' => $this->singularLabel,
            'search_items' => 'Search ' . $lowercasePluralLabel,
            'popular_items' => 'Popular ' . $lowercasePluralLabel,
            'all_items' => 'All ' . $lowercasePluralLabel,
            'parent_item' => 'Parent ' . $this->singularLabel,
            'parent_item_colon' => 'Parent ' . $this->singularLabel . ':',
            'name_field_description' => '',
            'slug_field_description' => '',
            'parent_field_description' => '',
            'edit_item' => 'Edit ' . $this->singularLabel,
            'update_item' => 'Update ' . $this->singularLabel,
            'add_new_item' => 'Add New ' . $this->singularLabel,
            'new_item_name' => 'New ' . $this->singularLabel . ' Name',
            'template_name' => $this->singularLabel . ' Template',
            'separate_items_with_commas' => 'Separate ' . $lowercasePluralLabel . ' with commas',
            'add_or_remove_items' => 'Add or remove ' . $lowercasePluralLabel,
            'choose_from_most_used' => 'Choose from the most used ' . $lowercasePluralLabel,
            'not_found' => 'No ' . $lowercasePluralLabel . ' found',
            'no_terms' => 'No ' . $lowercasePluralLabel . ' assigned yet',
            'filter_by_item' => 'Filter by ' . $lowercaseSingularLabel,
            'items_list_navigation' => $this->pluralLabel . ' list navigation',
            'items_list' => $this->pluralLabel . ' list',
            'most_used' => 'Most used ' . $lowercasePluralLabel,
            'back_to_items' => 'Back to ' . $lowercasePluralLabel,
            'item_link' => 'Link to ' . $this->singularLabel,
            'item_link_description' => 'A link to a ' . $this->singularLabel,
        ];
    }

    protected function getLabels(): array
    {
        return array_merge($this->getDefaultLabels(), $this->labels);
    }

    protected function getArgs(): array
    {
        return array_merge($this->getDefaultArgs(), $this->args);
    }

    public function register(): void
    {
        register_taxonomy(static::SLUG, $this->postTypes, $this->getArgs());

        if ($this->registersTopLevelMenuItem) {
            $this->registerTopLevelMenuItem();
        }
    }

    public function registerTopLevelMenuItem(): void
    {
        $this->addFilter('admin_menu', [$this, 'addMenuItem'], 10, 2);
    }

    public function addMenuItem()
    {
        add_menu_page(
            $this->menuItemName,
            $this->menuItemName,
            'manage_categories',
            'edit-tags.php?taxonomy=' . static::SLUG,
            null,
            $this->menuItemIcon,
            $this->menuItemPosition,
        );

        $this->addAction('parent_file', function ($parent_file) {
            global $current_screen;
            if ($current_screen->taxonomy === static::SLUG) {
                return 'edit-tags.php?taxonomy='.static::SLUG;
            }
            return $parent_file;
        });
    }
}
