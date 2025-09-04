<?php

namespace Imarc\Millyard\Hooks;

use Imarc\Millyard\Concerns\RegistersHooks;
use Imarc\Millyard\Contracts\HooksInterface;
use Imarc\Millyard\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractRouteHooks implements HooksInterface
{
    use RegistersHooks;

    private static $capturedHeaders = [];
    
    private static $headersCaptured = false;
    
    // Default headers that commonly get lost during server rewrites
    protected const HEADERS_TO_PRESERVE = [
        'Authorization',
        'X-API-Key',
        'X-Auth-Token', 
        'X-Access-Token',
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'X-CSRF-Token',
        'X-XSRF-Token'
    ];

    public function __construct(protected Router $router, protected Request $request)
    {
        $this->loadRoutes();
        $this->registerRewriteRules();
        $this->registerQueryVars();
        $this->registerThemeHooks();
    }

    /**
     * Abstract method that projects must implement to load their routes
     */
    abstract protected function loadRoutes(): void;

    /**
     * Projects can override this to customize which headers to preserve
     */
    protected function getHeadersToPreserve(): array
    {
        return static::HEADERS_TO_PRESERVE;
    }

    /**
     * Projects can override this to customize rewrite rule registration
     */
    protected function registerRewriteRules(): void
    {
        $this->addAction('init', function () {
            $routes = $this->router->getRoutes();

            foreach ($routes as $method => $paths) {
                foreach ($paths as $path => $callback) {
                    // Convert route pattern to regex for WordPress rewrite rules
                    $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
                    $regex = ltrim($regex, '/');
                    $regex = str_replace('/', '\/', $regex);
                    // Just pass a flag to indicate this is a custom route
                    // handleCustomRoutes() will take it from there.
                    add_rewrite_rule("^$regex/?$", "index.php?custom_route=1", 'top');
                }
            }
        }, $this->getRewriteRulesPriority());
    }

    /**
     * Projects can override this to customize query var registration
     */
    protected function registerQueryVars(): void
    {
        $this->addFilter('query_vars', function ($vars) {
            $vars[] = 'custom_route';
            return $vars;
        });
    }

    /**
     * Projects can override this to add additional theme hooks
     */
    protected function registerThemeHooks(): void
    {
        // Flush rewrite rules when theme is activated
        $this->addAction('after_switch_theme', [$this, 'flushRewriteRules']);
    }

    /**
     * Projects can override this to change when rewrite rules are registered
     */
    protected function getRewriteRulesPriority(): int
    {
        return 2;
    }

    /**
     * Projects can override this to change when headers are captured
     */
    protected function getHeaderCapturePriority(): int
    {
        return 1;
    }

    public function initialize(): void
    {
        // Capture headers early in the WordPress lifecycle
        $this->addAction('init', [$this, 'captureHeaders'], $this->getHeaderCapturePriority());
        $this->addAction('template_redirect', [$this, 'handleCustomRoutes']);
    }

    public function handleCustomRoutes(): void
    {
        $custom_route = get_query_var('custom_route');
        if (!$custom_route) {
            return;
        }

        $request_method = $_SERVER['REQUEST_METHOD'];
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        // Parse the URL path, ensuring we get a clean path
        $path = parse_url($request_uri, PHP_URL_PATH);
        if ($path === null) {
            return;
        }

        // Ensure the path starts with a forward slash
        $path = '/' . ltrim($path, '/');

        try {
            // Restore captured headers before handling the request
            $this->restoreHeadersToRequest();
            $this->router->handleRequest($request_method, $path);
        } catch (\Exception $e) {
            $this->handleRouteException($e, $request_method, $path);
        }
    }

    /**
     * Projects can override this to customize error handling
     */
    protected function handleRouteException(\Exception $e, string $method, string $path): void
    {
        error_log("Route handling error for {$method} {$path}: " . $e->getMessage());
    }

    public function captureHeaders(): void
    {
        // Only capture once per request
        if (self::$headersCaptured) {
            return;
        }
        self::$headersCaptured = true;
        
        // Try multiple sources for headers
        $this->captureFromServerVars();
        $this->captureFromRequest();
        $this->captureFromGetAllHeaders();
    }
    
    private function captureFromServerVars(): void
    {
        foreach ($this->getHeadersToPreserve() as $header) {
            if (!empty(self::$capturedHeaders[$header])) {
                continue; // Already captured
            }
            
            // Convert header name to $_SERVER format (e.g., Authorization -> HTTP_AUTHORIZATION)
            $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
            $redirectKey = 'REDIRECT_' . $serverKey;
            
            if (isset($_SERVER[$serverKey]) && !empty($_SERVER[$serverKey])) {
                self::$capturedHeaders[$header] = $_SERVER[$serverKey];
            } elseif (isset($_SERVER[$redirectKey]) && !empty($_SERVER[$redirectKey])) {
                self::$capturedHeaders[$header] = $_SERVER[$redirectKey];
            }
        }
        
        // Special cases for Authorization header
        if (empty(self::$capturedHeaders['Authorization'])) {
            $authSources = ['PHP_AUTH_USER', 'PHP_AUTH_PW', 'REMOTE_USER', 'AUTH_TYPE'];
            foreach ($authSources as $source) {
                if (isset($_SERVER[$source]) && !empty($_SERVER[$source])) {
                    self::$capturedHeaders['Authorization'] = $_SERVER[$source];
                    break;
                }
            }
        }
    }
    
    private function captureFromRequest(): void
    {
        foreach ($this->getHeadersToPreserve() as $header) {
            if (empty(self::$capturedHeaders[$header]) && $this->request->headers->has($header)) {
                self::$capturedHeaders[$header] = $this->request->headers->get($header);
            }
        }
    }
    
    private function captureFromGetAllHeaders(): void
    {
        if (!function_exists('getallheaders')) {
            return;
        }
        
        $headers = getallheaders();
        foreach ($this->getHeadersToPreserve() as $header) {
            if (!empty(self::$capturedHeaders[$header])) {
                continue; // Already captured
            }
            
            // Check both exact case and lowercase
            if (isset($headers[$header])) {
                self::$capturedHeaders[$header] = $headers[$header];
            } elseif (isset($headers[strtolower($header)])) {
                self::$capturedHeaders[$header] = $headers[strtolower($header)];
            }
        }
    }

    public static function getCapturedHeader(string $headerName): ?string
    {
        return self::$capturedHeaders[$headerName] ?? null;
    }
    
    public static function getAllCapturedHeaders(): array
    {
        return self::$capturedHeaders;
    }

    private function restoreHeadersToRequest(): void
    {
        foreach ($this->getHeadersToPreserve() as $header) {
            $value = self::getCapturedHeader($header);
            if ($value) {
                // Write to $_SERVER so new Request instances will pick it up
                $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
                $_SERVER[$serverKey] = $value;
                
                // Also update the current Request instance
                $this->request->headers->set($header, $value);
            }
        }
    }

    public function flushRewriteRules(): void
    {
        flush_rewrite_rules();
    }

    /**
     * Reset captured headers
     */
    public static function resetCapturedHeaders(): void
    {
        self::$capturedHeaders = [];
        self::$headersCaptured = false;
    }
}