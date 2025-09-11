<?php

namespace Imarc\Millyard\Tests\Unit;

use Brain\Monkey;

/**
 * Test cases for helper functions.
 */
class HelpersTest extends TestCase
{
    public function test_is_hmr_returns_false_when_not_development(): void
    {
        // Create a real temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'hot_test_');

        Monkey\Functions\when('wp_get_environment_type')->justReturn('production');
        Monkey\Functions\when('get_theme_file_path')->alias(function ($path) use ($tempFile) {
            return $path === '.hot' ? $tempFile : '/tmp/theme/' . ltrim($path, '/');
        });

        $result = is_hmr();

        // Clean up
        unlink($tempFile);

        $this->assertFalse($result);
    }

    public function test_is_hmr_returns_false_when_hot_file_does_not_exist(): void
    {
        $nonExistentFile = '/tmp/definitely_does_not_exist_' . uniqid();

        Monkey\Functions\when('wp_get_environment_type')->justReturn('development');
        Monkey\Functions\when('get_theme_file_path')->alias(function ($path) use ($nonExistentFile) {
            return $path === '.hot' ? $nonExistentFile : '/tmp/theme/' . ltrim($path, '/');
        });

        $this->assertFalse(is_hmr());
    }

    public function test_is_hmr_returns_true_when_development_and_hot_file_exists(): void
    {
        // Create a real temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'hot_test_');

        Monkey\Functions\when('wp_get_environment_type')->justReturn('development');
        Monkey\Functions\when('get_theme_file_path')->alias(function ($path) use ($tempFile) {
            return $path === '.hot' ? $tempFile : '/tmp/theme/' . ltrim($path, '/');
        });

        $result = is_hmr();

        // Clean up
        unlink($tempFile);

        $this->assertTrue($result);
    }

    public function test_csrf_token_key_without_sessions(): void
    {
        $this->mockConfigFile(['sessions' => ['enabled' => false]]);

        $key = csrf_token_key();

        $this->assertEquals('ajax_nonce', $key);
    }

    public function test_csrf_token_key_with_sessions(): void
    {
        $this->mockConfigFile(['sessions' => ['enabled' => true]]);

        // Since session_id() returns empty string by default in CLI,
        // the key will be 'ajax_nonce_' (with empty session id)
        $key = csrf_token_key();

        $this->assertEquals('ajax_nonce_', $key);
    }

    public function test_csrf_token_creates_nonce(): void
    {
        $this->mockConfigFile(['sessions' => ['enabled' => false]]);
        Monkey\Functions\expect('wp_create_nonce')
            ->once()
            ->with('ajax_nonce')
            ->andReturn('test_nonce_value');

        $token = csrf_token();

        $this->assertEquals('test_nonce_value', $token);
    }

    public function test_config_returns_default_when_key_not_found(): void
    {
        $this->mockConfigFile(['test' => ['value' => 'found']]);

        $result = config('nonexistent.key', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function test_config_returns_nested_value(): void
    {
        $this->mockConfigFile([
            'database' => [
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                    ],
                ],
            ],
        ]);

        $result = config('database.connections.mysql.host');

        $this->assertEquals('localhost', $result);
    }

    public function test_env_returns_default_when_not_set(): void
    {
        $result = env('NONEXISTENT_VAR', 'default');

        $this->assertEquals('default', $result);
    }

    public function test_env_converts_string_true_to_boolean(): void
    {
        $this->mockEnvironmentVariables(['TEST_BOOL' => 'true']);

        $result = env('TEST_BOOL');

        $this->assertTrue($result);
        $this->cleanupEnvironmentVariables(['TEST_BOOL']);
    }

    public function test_env_converts_string_false_to_boolean(): void
    {
        $this->mockEnvironmentVariables(['TEST_BOOL' => 'false']);

        $result = env('TEST_BOOL');

        $this->assertFalse($result);
        $this->cleanupEnvironmentVariables(['TEST_BOOL']);
    }

    public function test_env_converts_numeric_strings_to_numbers(): void
    {
        $this->mockEnvironmentVariables([
            'TEST_INT' => '42',
            'TEST_FLOAT' => '3.14',
        ]);

        $this->assertSame(42, env('TEST_INT'));
        $this->assertSame(3.14, env('TEST_FLOAT'));

        $this->cleanupEnvironmentVariables(['TEST_INT', 'TEST_FLOAT']);
    }

    public function test_function_timer_executes_callback_and_returns_result(): void
    {
        $callback = function () {
            return 'test_result';
        };

        $result = function_timer('test_function', $callback);

        $this->assertEquals('test_result', $result);
    }
}
