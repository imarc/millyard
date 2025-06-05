<?php

use Imarc\Millyard\Services\Cache;
use Imarc\Millyard\Services\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check if the current environment is development and a .hot file exists in the theme folder.
 *
 * @return bool True if the environment is development and the .hot file exists, false otherwise.
 */
if (! function_exists('is_hmr')) {
    function is_hmr(): bool
    {
        return wp_get_environment_type() === 'development'
            && file_exists(get_theme_file_path('.hot'));
    }
}

/**
 * Send a response using the Symfony Response class.
 *
 * @param string $content The content of the response.
 * @param int $status The status code of the response.
 * @param array $headers The headers of the response.
 */
if (! function_exists('response')) {
    function response(string $content, int $status = 200, array $headers = []): Response
    {
        $response = new Response($content, $status, $headers);

        return $response->send();
    }
}

/**
 * Send a JSON response using the Symfony JsonResponse class.
 *
 * @param array $data The data to send in the response.
 * @param int $status The status code of the response.
 * @param array $headers The headers of the response.
 */
if (! function_exists('json_response')) {
    function json_response(array $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}

/**
 * Get the CSRF token key.
 *
 * @return string The CSRF token key.
 */
if (! function_exists('csrf_token_key')) {
    function csrf_token_key(): string
    {
        $key = 'ajax_nonce';

        if (config('sessions.enabled')) {
            $sessionId = session_id();
            $key .= '_' . $sessionId;
        }

        return $key;
    }
}

/**
 * Get the CSRF token.
 *
 * @return string The CSRF token.
 */
if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        $key = csrf_token_key();

        return wp_create_nonce($key);
    }
}

/**
 * Get a configuration value.
 *
 * @param string $key The key to get the configuration value for.
 * @param mixed $default The default value to return if the key is not found.
 * @return mixed The configuration value.
 */
if (! function_exists('config')) {

    function config(string $key, $default = null)
    {
        $key = explode('.', $key);
        $config = require get_theme_file_path('app/config.php');

        foreach ($key as $k) {
            $config = $config[$k] ?? $default;
        }

        return $config;
    }
}

/**
 * Get an environment variable.
 *
 * @param string $key The key to get the environment variable for.
 * @param mixed $default The default value to return if the key is not found.
 * @return mixed The environment variable value.
 */
if (! function_exists('env')) {

    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $default;

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if (is_numeric($value)) {
            if (str_contains($value, '.')) {
                return (float) $value;
            }

            return (int) $value;
        }

        return $value;
    }
}

/**
 * Log the execution time of a function.
 *
 * @param string $name The name of the function to log.
 * @param callable $callback The function to log the execution time of.
 * @return mixed The result of the function.
 */
if (! function_exists('function_timer')) {
    function function_timer(string $name, callable $callback)
    {
        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);

        error_log(sprintf('%s took %s seconds to execute', $name, $end - $start));

        return $result;
    }
}

/**
 * Get the cache service.
 *
 * @return Cache The cache service.
 */
if (! function_exists('cache')) {
    function cache(): Cache
    {
        return Container::getInstance()->get(Cache::class);
    }
}

/**
 * Remember a value in the cache.
 *
 * @param string $key The key to remember the value for.
 * @param mixed $value The value to cache.
 * @param int|null $ttl The time to live for the cache.
 * @return mixed The value from the cache.
 */
if (! function_exists('cache_remember')) {
    function cache_remember(string $key, mixed $value, ?int $ttl = null): mixed
    {
        return cache()->remember($key, $value, $ttl);
    }
}

/**
 * Forget a value in the cache.
 *
 * @param string $key The key to forget.
 */
if (! function_exists('cache_forget')) {
    function cache_forget(string $key): void
    {
        cache()->forget($key);
    }
}

/**
 * Flush the cache.
 */
if (! function_exists('cache_flush')) {
    function cache_flush(): void
    {
        cache()->flush();
    }
}

/**
 * Get a value from the cache.
 *
 * @param string $key The key to get the value for.
 * @return mixed The value from the cache.
 */
if (! function_exists('cache_get')) {
    function cache_get(string $key): mixed
    {
        return cache()->get($key);
    }
}

/**
 * Set a value in the cache.
 *
 * @param string $key The key to set the value for.
 * @param mixed $value The value to set.
 * @param int|null $ttl The time to live for the cache.
 */
if (! function_exists('cache_set')) {
    function cache_set(string $key, mixed $value, ?int $ttl = null): void
    {
        cache()->set($key, $value, $ttl);
    }
}
