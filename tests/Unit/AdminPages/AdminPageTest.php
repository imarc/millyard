<?php

namespace Imarc\Millyard\Tests\Unit\AdminPages;

use Brain\Monkey;
use Imarc\Millyard\AdminPages\AdminPage;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for AdminPage abstract class.
 */
class AdminPageTest extends TestCase
{
    public function test_admin_page_can_be_instantiated(): void
    {
        $adminPage = new class extends AdminPage {
            protected string $slug = 'test-page';
            protected string $title = 'Test Page';
        };

        $this->assertInstanceOf(AdminPage::class, $adminPage);
    }

    public function test_register_calls_add_menu_page_for_top_level_page(): void
    {
        $adminPage = new class extends AdminPage {
            protected string $slug = 'test-page';
            protected string $title = 'Test Page';
            protected string $capability = 'manage_options';
            protected int $menuPosition = 10;
            protected string $icon = 'dashicons-admin-generic';
        };

        // Mock WordPress add_menu_page function
        Monkey\Functions\expect('add_menu_page')
            ->once()
            ->with(
                'Test Page',
                'Test Page',
                'manage_options',
                'test-page',
                [$adminPage, 'render'],
                'dashicons-admin-generic',
                10
            )
            ->andReturn('hook_suffix');

        $adminPage->register();
    }

    public function test_register_calls_add_submenu_page_when_parent_slug_set(): void
    {
        $adminPage = new class extends AdminPage {
            protected string $slug = 'test-subpage';
            protected string $title = 'Test Subpage';
            protected string $capability = 'manage_options';
            protected int $menuPosition = 5;
            protected string $parentSlug = 'parent-page';
        };

        // Mock WordPress add_submenu_page function
        Monkey\Functions\expect('add_submenu_page')
            ->once()
            ->with(
                'parent-page',
                'Test Subpage',
                'Test Subpage',
                'manage_options',
                'test-subpage',
                [$adminPage, 'render'],
                5
            )
            ->andReturn('hook_suffix');

        $adminPage->register();
    }

    public function test_render_does_nothing_when_no_template_set(): void
    {
        $adminPage = new class extends AdminPage {
            protected string $slug = 'test-page';
            protected string $title = 'Test Page';
        };

        // Should not call any Timber functions
        $this->expectNotToPerformAssertions();
        $adminPage->render();
    }

    public function test_with_context_returns_empty_array_by_default(): void
    {
        $adminPage = new class extends AdminPage {
            protected string $slug = 'test-page';
            protected string $title = 'Test Page';
        };

        $context = $adminPage->withContext();
        
        $this->assertIsArray($context);
        $this->assertEmpty($context);
    }
}
