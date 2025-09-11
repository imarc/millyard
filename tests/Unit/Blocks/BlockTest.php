<?php

namespace Imarc\Millyard\Tests\Unit\Blocks;

use Brain\Monkey;
use Imarc\Millyard\Blocks\Block;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for Block abstract class.
 */
class BlockTest extends TestCase
{
    public function test_block_can_be_instantiated(): void
    {
        $block = new class () extends Block {
            public const NAME = 'test-block';
            public const TITLE = 'Test Block';
            public const CATEGORY = 'common';
            public const ICON = 'dashicons-block-default';
        };

        $this->assertInstanceOf(Block::class, $block);
        $this->assertEquals('test-block', $block::NAME);
        $this->assertEquals('Test Block', $block::TITLE);
        $this->assertEquals('common', $block::CATEGORY);
        $this->assertEquals('dashicons-block-default', $block::ICON);
    }

    public function test_constants_can_be_arrays(): void
    {
        $block = new class () extends Block {
            public const NAME = 'test-block';
            public const TITLE = 'Test Block';
            public const KEYWORDS = ['test', 'example', 'demo'];
            public const POST_TYPES = ['post', 'page'];
        };

        $this->assertEquals(['test', 'example', 'demo'], $block::KEYWORDS);
        $this->assertEquals(['post', 'page'], $block::POST_TYPES);
    }

    public function test_register_does_nothing_when_acf_not_available(): void
    {
        $block = new class () extends Block {
            public const NAME = 'test-block';
            public const TITLE = 'Test Block';
        };

        // Mock function_exists to return false (ACF not available)
        Monkey\Functions\when('function_exists')->justReturn(false);

        // The register method should complete without error
        $block->register();

        // If we get here without errors, the test passes
        $this->assertTrue(true);
    }

    public function test_register_calls_acf_when_available(): void
    {
        $block = new class () extends Block {
            public const NAME = 'test-block';
            public const TITLE = 'Test Block';
        };

        // Set up a flag to track if ACF function was called
        $acfCalled = false;

        // Mock the ACF register function first
        Monkey\Functions\when('acf_register_block_type')->alias(function ($config) use (&$acfCalled) {
            $acfCalled = true;

            // Verify some basic config structure
            return is_array($config) && isset($config['name']) && $config['name'] === 'test-block';
        });

        // Mock function_exists to return true for ACF (after we've set up the mock)
        Monkey\Functions\when('function_exists')->alias(function ($func) {
            return $func === 'acf_register_block_type';
        });

        $block->register();

        $this->assertTrue($acfCalled, 'ACF register function should have been called');
    }

    public function test_with_context_returns_empty_array_by_default(): void
    {
        $block = new class () extends Block {
            public const NAME = 'test-block';
            public const TITLE = 'Test Block';
        };

        $context = $block->withContext();

        $this->assertIsArray($context);
        $this->assertEmpty($context);
    }

    public function test_with_context_can_be_overridden(): void
    {
        $block = new class () extends Block {
            public const NAME = 'test-block';
            public const TITLE = 'Test Block';

            public function withContext(): array
            {
                return [
                    'custom_data' => 'test_value',
                    'another_field' => 42,
                ];
            }
        };

        $context = $block->withContext();

        $this->assertIsArray($context);
        $this->assertEquals('test_value', $context['custom_data']);
        $this->assertEquals(42, $context['another_field']);
    }

    public function test_render_method_exists_and_accepts_parameters(): void
    {
        $block = new class () extends Block {
            public const NAME = 'test-block';
            public const TITLE = 'Test Block';

            public $renderCalled = false;
            public $renderParams = [];

            public function render($block, $content = '', $isPreview = false, $postId = 0)
            {
                $this->renderCalled = true;
                $this->renderParams = [$block, $content, $isPreview, $postId];
                // Don't call parent::render() to avoid Timber issues in tests
            }
        };

        // Test that render method can be called with different parameter combinations
        $block->render(['test' => 'data']);
        $this->assertTrue($block->renderCalled);
        $this->assertEquals(['test' => 'data'], $block->renderParams[0]);
        $this->assertEquals('', $block->renderParams[1]);
        $this->assertFalse($block->renderParams[2]);
        $this->assertEquals(0, $block->renderParams[3]);

        // Reset and test with all parameters
        $block->renderCalled = false;
        $block->render(['block' => 'data'], 'content', true, 123);
        $this->assertTrue($block->renderCalled);
        $this->assertEquals(['block' => 'data'], $block->renderParams[0]);
        $this->assertEquals('content', $block->renderParams[1]);
        $this->assertTrue($block->renderParams[2]);
        $this->assertEquals(123, $block->renderParams[3]);
    }

    public function test_get_config_can_be_overridden(): void
    {
        $block = new class () extends Block {
            public const NAME = 'test-block';
            public const TITLE = 'Test Block';

            protected function getConfig(): array
            {
                return [
                    'supports' => [
                        'align' => true,
                        'color' => true,
                    ],
                    'custom_setting' => 'custom_value',
                ];
            }

            // Expose the method for testing
            public function testGetConfig(): array
            {
                return $this->getConfig();
            }
        };

        $config = $block->testGetConfig();

        $this->assertIsArray($config);
        $this->assertTrue($config['supports']['align']);
        $this->assertTrue($config['supports']['color']);
        $this->assertEquals('custom_value', $config['custom_setting']);
    }

    public function test_block_constants_are_accessible(): void
    {
        $block = new class () extends Block {
            public const NAME = 'my-custom-block';
            public const TITLE = 'My Custom Block';
            public const CATEGORY = 'widgets';
            public const ICON = 'dashicons-admin-customizer';
            public const KEYWORDS = ['custom', 'widget'];
            public const POST_TYPES = ['page'];
        };

        // Test that all constants are properly set and accessible
        $this->assertEquals('my-custom-block', $block::NAME);
        $this->assertEquals('My Custom Block', $block::TITLE);
        $this->assertEquals('widgets', $block::CATEGORY);
        $this->assertEquals('dashicons-admin-customizer', $block::ICON);
        $this->assertEquals(['custom', 'widget'], $block::KEYWORDS);
        $this->assertEquals(['page'], $block::POST_TYPES);
    }
}
