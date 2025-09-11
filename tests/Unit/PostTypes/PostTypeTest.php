<?php

namespace Imarc\Millyard\Tests\Unit\PostTypes;

use Brain\Monkey;
use Imarc\Millyard\PostTypes\PostType;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for PostType abstract class.
 */
class PostTypeTest extends TestCase
{
    public function test_post_type_can_be_instantiated(): void
    {
        $postType = new class extends PostType {
            public const SLUG = 'test_post';
            public string $singularLabel = 'Test Post';
            public string $pluralLabel = 'Test Posts';
        };

        $this->assertInstanceOf(PostType::class, $postType);
        $this->assertEquals('test_post', $postType::SLUG);
    }

    public function test_get_labels_returns_proper_structure(): void
    {
        $postType = new class extends PostType {
            public const SLUG = 'test_post';
            public string $singularLabel = 'Test Post';
            public string $pluralLabel = 'Test Posts';
            
            // Expose protected method for testing
            public function getLabelsPublic(): array
            {
                return $this->getLabels();
            }
        };

        $labels = $postType->getLabelsPublic();

        $this->assertIsArray($labels);
        $this->assertEquals('Test Posts', $labels['name']);
        $this->assertEquals('Test Post', $labels['singular_name']);
        $this->assertEquals('Add New Test Post', $labels['add_new_item']);
        $this->assertEquals('Edit Test Post', $labels['edit_item']);
        $this->assertEquals('New Test Post', $labels['new_item']);
        $this->assertEquals('View Test Post', $labels['view_item']);
        $this->assertEquals('Search Test Posts', $labels['search_items']);
        $this->assertEquals('No test posts found', $labels['not_found']);
        $this->assertEquals('No test posts found in Trash', $labels['not_found_in_trash']);
    }

    public function test_get_default_args_returns_proper_structure(): void
    {
        $postType = new class extends PostType {
            public const SLUG = 'test_post';
            public string $singularLabel = 'Test Post';
            public string $pluralLabel = 'Test Posts';
            
            // Expose protected method for testing
            public function getDefaultArgsPublic(): array
            {
                return $this->getDefaultArgs();
            }
        };

        $args = $postType->getDefaultArgsPublic();

        $this->assertIsArray($args);
        $this->assertTrue($args['public']);
        $this->assertTrue($args['show_ui']);
        $this->assertTrue($args['show_in_menu']);
        $this->assertTrue($args['show_in_rest']);
        $this->assertEquals('posts', $args['rest_base']); // Uses $path property, defaults to 'posts'
        $this->assertIsArray($args['labels']);
        $this->assertIsArray($args['supports']);
        $this->assertIsArray($args['rewrite']);
        $this->assertEquals('posts', $args['rewrite']['slug']); // Uses $path property
    }

    public function test_register_calls_register_post_type(): void
    {
        $postType = new class extends PostType {
            public const SLUG = 'test_post';
            public string $singularLabel = 'Test Post';
            public string $pluralLabel = 'Test Posts';
        };

        // Mock WordPress register_post_type function
        Monkey\Functions\expect('register_post_type')
            ->once()
            ->with('test_post', \Mockery::type('array'))
            ->andReturn(true);

        $postType->register();
    }

    public function test_custom_args_are_merged_with_defaults(): void
    {
        $postType = new class extends PostType {
            public const SLUG = 'test_post';
            public string $singularLabel = 'Test Post';
            public string $pluralLabel = 'Test Posts';
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

        $args = $postType->getArgsPublic();

        // Custom args should override defaults
        $this->assertFalse($args['public']);
        $this->assertEquals('custom_value', $args['custom_field']);
        
        // Default args should still be present
        $this->assertTrue($args['show_ui']);
        $this->assertIsArray($args['labels']);
    }

    public function test_custom_labels_are_merged_with_defaults(): void
    {
        $postType = new class extends PostType {
            public const SLUG = 'test_post';
            public string $singularLabel = 'Test Post';
            public string $pluralLabel = 'Test Posts';
            protected array $labels = [
                'name' => 'Custom Posts',
                'custom_label' => 'Custom Value'
            ];
            
            // Expose protected method for testing
            public function getLabelsPublic(): array
            {
                return $this->getLabels();
            }
        };

        $labels = $postType->getLabelsPublic();

        // Custom labels should override defaults
        $this->assertEquals('Custom Posts', $labels['name']);
        $this->assertEquals('Custom Value', $labels['custom_label']);
        
        // Default labels should still be present
        $this->assertEquals('Test Post', $labels['singular_name']);
    }

    public function test_supports_can_be_customized(): void
    {
        $postType = new class extends PostType {
            public const SLUG = 'test_post';
            public string $singularLabel = 'Test Post';
            public string $pluralLabel = 'Test Posts';
            protected array $args = [
                'supports' => ['title', 'custom-fields']
            ];
            
            // Expose protected method for testing
            public function getArgsPublic(): array
            {
                return $this->getArgs();
            }
        };

        $args = $postType->getArgsPublic();

        $this->assertEquals(['title', 'custom-fields'], $args['supports']);
    }

    public function test_custom_path_affects_rewrite_and_rest_base(): void
    {
        $postType = new class extends PostType {
            public const SLUG = 'test_post';
            public string $singularLabel = 'Test Post';
            public string $pluralLabel = 'Test Posts';
            public string $path = 'custom-posts';
            
            // Expose protected method for testing
            public function getDefaultArgsPublic(): array
            {
                return $this->getDefaultArgs();
            }
        };

        $args = $postType->getDefaultArgsPublic();

        $this->assertEquals('custom-posts', $args['rest_base']);
        $this->assertEquals('custom-posts', $args['rewrite']['slug']);
    }
}
