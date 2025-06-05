<?php

namespace Imarc\Millyard\Services;

class Cache
{
    public static function getDefaultTtl(): int
    {
        return config('cache.ttl', 60 * 60 * 24);
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key The key to get the value for.
     * @return mixed The value from the cache.
     */
    public function get(string $key)
    {
        return wp_cache_get($key);
    }

    /**
     * Set a value in the cache.
     *
     * @param string $key The key to set the value for.
     * @param mixed $value The value to set.
     * @param int|null $ttl The time to live for the cache.
     * @return bool True if the value was set, false otherwise.
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return wp_cache_set($key, $value, '', $ttl ?: self::getDefaultTtl());
    }

    /**
     * Get a value from the cache or set it if it doesn't exist.
     *
     * @param string $key The key to get the value for.
     * @param mixed $value The value to set if it doesn't exist. This can be a callable, in which case the result of the callback will be cached.
     * @param int|null $ttl The time to live for the cache.
     * @return mixed The value from the cache.
     */
    public function remember(string $key, mixed $value, ?int $ttl = null)
    {
        $cachedValue = $this->get($key);

        if ($cachedValue) {
            return $cachedValue;
        }

        if (is_callable($value)) {
            $value = $value();
        }

        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Forget a value from the cache.
     *
     * @param string $key The key to forget.
     * @return bool True if the value was forgotten, false otherwise.
     */
    public function forget(string $key): bool
    {
        return wp_cache_delete($key);
    }

    /**
     * Flush the cache.
     *
     * @return bool True if the cache was flushed, false otherwise.
     */
    public function flush(): bool
    {
        return wp_cache_flush();
    }
}
