<?php

namespace Imarc\Millyard\Tests\Unit;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for unit tests.
 *
 * This class provides a foundation for unit testing Millyard components
 * without requiring a full WordPress environment.
 */
abstract class TestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Temporary config file for testing.
     */
    protected ?string $tempConfigFile = null;

    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Set up common WordPress function mocks
        $this->setUpWordPressMocks();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Clean up temporary config file
        if ($this->tempConfigFile && file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
            $this->tempConfigFile = null;
        }

        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Set up common WordPress function mocks.
     */
    protected function setUpWordPressMocks(): void
    {
        // Mock common WordPress functions
        Monkey\Functions\when('wp_get_environment_type')->justReturn('testing');
        Monkey\Functions\when('get_theme_file_path')->alias(function ($path = '') {
            return '/tmp/theme/' . ltrim($path, '/');
        });

        // Mock WordPress action/filter functions
        Monkey\Functions\when('do_action')->justReturn(null);
        Monkey\Functions\when('add_action')->justReturn(true);
        Monkey\Functions\when('add_filter')->justReturn(true);
        Monkey\Functions\when('apply_filters')->returnArg(1);

        // Mock WordPress parsing functions
        Monkey\Functions\when('wp_parse_args')->alias(function ($args, $defaults) {
            if (is_object($args)) {
                $args = get_object_vars($args);
            }

            if (! is_array($args)) {
                $args = [];
            }

            return array_merge($defaults, $args);
        });
    }

    /**
     * Create a mock config file for testing.
     *
     * @param array $config Configuration array
     */
    protected function mockConfigFile(array $config = []): void
    {
        $defaultConfig = [
            'sessions' => [
                'enabled' => false,
            ],
            'cache' => [
                'enabled' => true,
                'ttl' => 3600,
            ],
        ];

        $mockConfig = array_merge($defaultConfig, $config);

        // Create a temporary file with the config array
        $tempConfigFile = sys_get_temp_dir() . '/millyard_test_config_' . uniqid() . '.php';
        file_put_contents($tempConfigFile, '<?php return ' . var_export($mockConfig, true) . ';');

        Monkey\Functions\when('get_theme_file_path')->alias(function ($path) use ($tempConfigFile) {
            if ($path === 'app/config.php') {
                return $tempConfigFile;
            }

            return '/tmp/theme/' . ltrim($path, '/');
        });

        // Store temp file for cleanup
        $this->tempConfigFile = $tempConfigFile;
    }

    /**
     * Mock environment variables for testing.
     *
     * @param array $env Environment variables
     */
    protected function mockEnvironmentVariables(array $env): void
    {
        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
        }
    }

    /**
     * Clean up environment variables after test.
     *
     * @param array $keys Keys to clean up
     */
    protected function cleanupEnvironmentVariables(array $keys): void
    {
        foreach ($keys as $key) {
            unset($_ENV[$key]);
        }
    }
}
