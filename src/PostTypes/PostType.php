<?php

namespace Imarc\Millyard\PostTypes;

use WP_Query;

abstract class PostType
{
    public const SLUG = '';

    public string $singularLabel = 'Post';

    public string $pluralLabel = 'Posts';

    public string $path = 'posts';

    protected array $args = [];

    protected array $labels = [];

    protected function getDefaultArgs(): array
    {
        return [
            'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'capability_type' => 'post',
            'rewrite' => ['slug' => $this->path],
            'menu_icon' => 'dashicons-admin-post',
            'show_in_rest' => true,
            'rest_base' => $this->path,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'labels' => $this->getLabels(),
            'taxonomies' => [],
        ];
    }

    protected function getDefaultLabels(): array
    {
        return [
            'name' => $this->pluralLabel,
            'singular_name' => $this->singularLabel,
            'add_new' => 'Add New',
            'add_new_item' => 'Add New ' . $this->singularLabel,
            'edit_item' => 'Edit ' . $this->singularLabel,
            'new_item' => 'New ' . $this->singularLabel,
            'view_item' => 'View ' . $this->singularLabel,
            'view_items' => 'View ' . $this->pluralLabel,
            'search_items' => 'Search ' . $this->pluralLabel,
            'not_found' => 'No ' . strtolower($this->pluralLabel) . ' found',
            'not_found_in_trash' => 'No ' . strtolower($this->pluralLabel) . ' found in Trash',
            'parent_item_colon' => 'Parent ' . $this->singularLabel . ':',
            'all_items' => 'All ' . $this->pluralLabel,
            'archives' => $this->singularLabel . ' Archives',
            'attributes' => $this->singularLabel . ' Attributes',
            'insert_into_item' => 'Insert into ' . strtolower($this->singularLabel),
            'uploaded_to_this_item' => 'Uploaded to this ' . strtolower($this->singularLabel),
            'featured_image' => 'Featured Image',
            'set_featured_image' => 'Set featured image',
            'remove_featured_image' => 'Remove featured image',
            'use_featured_image' => 'Use as featured image',
            'menu_name' => $this->pluralLabel,
            'filter_items_list' => 'Filter ' . strtolower($this->pluralLabel) . ' list',
            'filter_by_date' => 'Filter by date',
            'items_list_navigation' => $this->pluralLabel . ' list navigation',
            'items_list' => $this->pluralLabel . ' list',
            'item_published' => $this->singularLabel . ' published',
            'item_published_privately' => $this->singularLabel . ' published privately',
            'item_reverted_to_draft' => $this->singularLabel . ' reverted to draft',
            'item_trashed' => $this->singularLabel . ' trashed',
            'item_scheduled' => $this->singularLabel . ' scheduled',
            'item_updated' => $this->singularLabel . ' updated',
            'item_link' => $this->singularLabel . ' link',
            'item_link_description' => 'A link to a ' . strtolower($this->singularLabel),
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
        register_post_type(static::SLUG, $this->getArgs());
    }

    public static function query(array $args = []): WP_Query
    {
        return new WP_Query(array_merge([
            'post_type' => static::SLUG,
            'posts_per_page' => -1,
        ], $args));
    }
}
