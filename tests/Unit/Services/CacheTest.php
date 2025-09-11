<?php

namespace Imarc\Millyard\Tests\Unit\Services;

use Brain\Monkey;
use Imarc\Millyard\Services\Cache;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for the Cache service.
 */
class CacheTest extends TestCase
{
    public function test_get_default_ttl_returns_configured_value(): void
    {
        $this->mockConfigFile(['cache' => ['ttl' => 7200]]);

        $ttl = Cache::getDefaultTtl();
        
        $this->assertEquals(7200, $ttl);
    }

    public function test_get_default_ttl_returns_default_when_not_configured(): void
    {
        // Don't mock any config file, so config() should return the default value
        // The Cache::getDefaultTtl() calls config('cache.ttl', 60 * 60 * 24)
        // Since cache.ttl is not set, it should return 60 * 60 * 24 = 86400
        
        // But our base TestCase sets up a default config with cache.ttl = 3600
        // So let's mock an empty config instead
        $this->mockConfigFile(['cache' => []]);

        $ttl = Cache::getDefaultTtl();
        
        $this->assertEquals(86400, $ttl); // 24 hours default
    }

    public function test_get_calls_wp_cache_get(): void
    {
        Monkey\Functions\expect('wp_cache_get')
            ->once()
            ->with('test_key')
            ->andReturn('test_value');

        $cache = new Cache();
        $result = $cache->get('test_key');
        
        $this->assertEquals('test_value', $result);
    }

    public function test_set_calls_wp_cache_set_with_default_ttl(): void
    {
        $this->mockConfigFile(['cache' => ['ttl' => 3600]]);
        
        Monkey\Functions\expect('wp_cache_set')
            ->once()
            ->with('test_key', 'test_value', '', 3600)
            ->andReturn(true);

        $cache = new Cache();
        $result = $cache->set('test_key', 'test_value');
        
        $this->assertTrue($result);
    }
}
