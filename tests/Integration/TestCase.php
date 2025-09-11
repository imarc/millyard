<?php

namespace Imarc\Millyard\Tests\Integration;

use Yoast\WPTestUtils\WPIntegration\TestCase as YoastTestCase;

/**
 * Base test case for integration tests.
 * 
 * This class extends Yoast's WP Test Utils TestCase to provide
 * a WordPress environment for integration testing.
 */
abstract class TestCase extends YoastTestCase
{
    /**
     * Set up the test environment before each test.
     */
    public function set_up(): void
    {
        parent::set_up();
        
        // Additional setup for Millyard-specific tests
        $this->setUpMillyardEnvironment();
    }

    /**
     * Clean up after each test.
     */
    public function tear_down(): void
    {
        parent::tear_down();
    }

    /**
     * Set up Millyard-specific test environment.
     */
    protected function setUpMillyardEnvironment(): void
    {
        // Mock theme directory structure
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/wordpress/');
        }
        
        // Set up any Millyard-specific WordPress hooks or filters
        add_filter('wp_get_environment_type', function() {
            return 'testing';
        });
    }

    /**
     * Helper method to create a test post.
     * 
     * @param array $args Post arguments
     * @return int Post ID
     */
    protected function createTestPost(array $args = []): int
    {
        $defaults = [
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_type' => 'post',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return $this->factory()->post->create($args);
    }

    /**
     * Helper method to create a test taxonomy term.
     * 
     * @param string $taxonomy Taxonomy name
     * @param array $args Term arguments
     * @return int Term ID
     */
    protected function createTestTerm(string $taxonomy, array $args = []): int
    {
        $defaults = [
            'name' => 'Test Term',
            'slug' => 'test-term',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return $this->factory()->term->create(array_merge($args, ['taxonomy' => $taxonomy]));
    }

    /**
     * Helper method to create a test user.
     * 
     * @param array $args User arguments
     * @return int User ID
     */
    protected function createTestUser(array $args = []): int
    {
        $defaults = [
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'role' => 'subscriber',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return $this->factory()->user->create($args);
    }
}
