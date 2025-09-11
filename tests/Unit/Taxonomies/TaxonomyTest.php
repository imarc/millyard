<?php

namespace Imarc\Millyard\Tests\Unit\Taxonomies;

use Brain\Monkey;
use Imarc\Millyard\Taxonomies\Taxonomy;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for Taxonomy abstract class.
 */
class TaxonomyTest extends TestCase
{
    public function test_taxonomy_can_be_instantiated(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Category';
            public string $pluralLabel = 'Test Categories';
        };

        $this->assertInstanceOf(Taxonomy::class, $taxonomy);
        $this->assertEquals('test_taxonomy', $taxonomy::SLUG);
    }

    public function test_get_labels_returns_proper_structure(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Category';
            public string $pluralLabel = 'Test Categories';
            
            // Expose protected method for testing
            public function getLabelsPublic(): array
            {
                return $this->getLabels();
            }
        };

        $labels = $taxonomy->getLabelsPublic();

        $this->assertIsArray($labels);
        $this->assertEquals('Test Categories', $labels['name']);
        $this->assertEquals('Test Category', $labels['singular_name']);
        $this->assertEquals('Add New Test Category', $labels['add_new_item']);
        $this->assertEquals('Edit Test Category', $labels['edit_item']);
        $this->assertEquals('Update Test Category', $labels['update_item']);
        $this->assertEquals('New Test Category Name', $labels['new_item_name']);
        $this->assertEquals('All test categories', $labels['all_items']);
        $this->assertEquals('Search test categories', $labels['search_items']);
    }

    public function test_get_default_args_returns_proper_structure(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Category';
            public string $pluralLabel = 'Test Categories';
            
            // Expose protected method for testing
            public function getDefaultArgsPublic(): array
            {
                return $this->getDefaultArgs();
            }
        };

        $args = $taxonomy->getDefaultArgsPublic();

        $this->assertIsArray($args);
        $this->assertTrue($args['public']);
        $this->assertTrue($args['show_ui']);
        $this->assertTrue($args['show_in_menu']);
        $this->assertTrue($args['show_in_rest']);
        $this->assertTrue($args['hierarchical']);
        $this->assertEquals('test_taxonomy', $args['rest_base']);
        $this->assertIsArray($args['labels']);
        $this->assertIsArray($args['rewrite']);
        $this->assertEquals('test_taxonomy', $args['rewrite']['slug']);
    }

    public function test_register_calls_register_taxonomy(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Category';
            public string $pluralLabel = 'Test Categories';
        };

        // Mock WordPress register_taxonomy function
        Monkey\Functions\expect('register_taxonomy')
            ->once()
            ->with('test_taxonomy', [], \Mockery::type('array'))
            ->andReturn(true);

        $taxonomy->register();
    }

    public function test_register_with_post_types(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Category';
            public string $pluralLabel = 'Test Categories';
            protected array $postTypes = ['post', 'page'];
        };

        // Mock WordPress register_taxonomy function
        Monkey\Functions\expect('register_taxonomy')
            ->once()
            ->with('test_taxonomy', ['post', 'page'], \Mockery::type('array'))
            ->andReturn(true);

        $taxonomy->register();
    }

    public function test_custom_args_are_merged_with_defaults(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Category';
            public string $pluralLabel = 'Test Categories';
            protected array $args = [
                'public' => false,
                'custom_field' => 'custom_value'
            ];
            
            // Expose protected method for testing
            public function getArgsPublic(): array
            {
                return $this->getArgs();
            }
        };

        $args = $taxonomy->getArgsPublic();

        // Custom args should override defaults
        $this->assertFalse($args['public']);
        $this->assertEquals('custom_value', $args['custom_field']);
        
        // Default args should still be present
        $this->assertTrue($args['show_ui']);
        $this->assertIsArray($args['labels']);
    }

    public function test_custom_labels_are_merged_with_defaults(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Category';
            public string $pluralLabel = 'Test Categories';
            protected array $labels = [
                'name' => 'Custom Categories',
                'custom_label' => 'Custom Value'
            ];
            
            // Expose protected method for testing
            public function getLabelsPublic(): array
            {
                return $this->getLabels();
            }
        };

        $labels = $taxonomy->getLabelsPublic();

        // Custom labels should override defaults
        $this->assertEquals('Custom Categories', $labels['name']);
        $this->assertEquals('Custom Value', $labels['custom_label']);
        
        // Default labels should still be present
        $this->assertEquals('Test Category', $labels['singular_name']);
    }

    public function test_non_hierarchical_taxonomy(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Tag';
            public string $pluralLabel = 'Test Tags';
            protected bool $isHierarchical = false;
            
            // Expose protected method for testing
            public function getDefaultArgsPublic(): array
            {
                return $this->getDefaultArgs();
            }
        };

        $args = $taxonomy->getDefaultArgsPublic();

        $this->assertFalse($args['hierarchical']);
        $this->assertEquals('post_tags_meta_box', $args['meta_box_cb']);
    }

    public function test_hierarchical_taxonomy(): void
    {
        $taxonomy = new class extends Taxonomy {
            public const SLUG = 'test_taxonomy';
            public string $singularLabel = 'Test Category';
            public string $pluralLabel = 'Test Categories';
            protected bool $isHierarchical = true;
            
            // Expose protected method for testing
            public function getDefaultArgsPublic(): array
            {
                return $this->getDefaultArgs();
            }
        };

        $args = $taxonomy->getDefaultArgsPublic();

        $this->assertTrue($args['hierarchical']);
        $this->assertEquals('post_categories_meta_box', $args['meta_box_cb']);
    }
}
