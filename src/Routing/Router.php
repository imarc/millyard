<?php

namespace Imarc\Millyard\Routing;

use Imarc\Millyard\Services\Container;
use League\Container\Container as BaseContainer;
use ReflectionFunction;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router
{
    private static ?Router $instance = null;
    private array $routes = [];
    private BaseContainer $container;
    private Request $request;
    private array $defaultMiddleware = [];
    private ?string $currentPath = null;

    private function __construct()
    {
        $this->container = Container::getInstance();
        $this->request = $this->container->get(Request::class);
    }

    /**
     * Get the singleton instance of the router.
     *
     * @return Router
     */
    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new Router();
        }
        return self::$instance;
    }

    /**
     * Add a GET route.
     *
     * @param string $path The path to add the route to.
     * @param mixed $action The action to add to the route.
     * @return static
     */
    public function get($path, $action): static
    {
        $path = $this->normalizePath($path);
        $this->routes['GET'][$path]['action'] = $action;
        $this->currentPath = $path;

        return $this;
    }

    /**
     * Add a POST route.
     *
     * @param string $path The path to add the route to.
     * @param mixed $action The action to add to the route.
     * @return static
     */
    public function post($path, $action): static
    {
        $path = $this->normalizePath($path);
        $this->routes['POST'][$path]['action'] = $action;
        $this->currentPath = $path;

        return $this;
    }

    /**
     * Add a PUT route.
     *
     * @param string $path The path to add the route to.
     * @param mixed $action The action to add to the route.
     * @return static
     */
    public function put($path, $action): static
    {
        $path = $this->normalizePath($path);
        $this->routes['PUT'][$path]['action'] = $action;
        $this->currentPath = $path;

        return $this;
    }

    /**
     * Add a DELETE route.
     *
     * @param string $path The path to add the route to.
     * @param mixed $action The action to add to the route.
     * @return static
     */
    public function delete($path, $action): static
    {
        $path = $this->normalizePath($path);
        $this->routes['DELETE'][$path]['action'] = $action;
        $this->currentPath = $path;

        return $this;
    }

    /**
     * Add a PATCH route.
     *
     * @param string $path The path to add the route to.
     * @param mixed $action The action to add to the route.
     * @return static
     */
    public function patch($path, $action): static
    {
        $path = $this->normalizePath($path);
        $this->routes['PATCH'][$path]['action'] = $action;
        $this->currentPath = $path;

        return $this;
    }

    /**
     * Add middleware to the current route.
     *
     * @param array|string $middleware The middleware to add.
     */
    public function middleware(array|string $middleware): void
    {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        if ($this->currentPath) {
            $this->routes[$this->request->getMethod()][$this->currentPath]['middleware'] = $middleware;
        }

        $this->currentPath = null;
    }

    /**
     * Normalize the path.
     *
     * @param string $path The path to normalize.
     * @return string The normalized path.
     */
    private function normalizePath(string $path): string
    {
        // Remove trailing slash unless it's the root path
        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * Set the default middleware for the router.
     *
     * @param array|string $middleware The middleware to set.
     * @return static
     */
    public function setDefaultMiddleware(array|string $middleware): static
    {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        $this->defaultMiddleware = $middleware;

        return $this;
    }

    /**
     * Handle a request.
     *
     * @param string $method The method of the request.
     * @param string $route The route to handle.
     */
    public function handleRequest(string $method, string $route): void
    {
        $route = $this->normalizePath($route);

        foreach ($this->routes[$method] ?? [] as $pattern => $config) {
            $action = $config['action'];
            $middleware = $config['middleware'] ?? [];

            if ($pattern === $route || ($params = $this->extractParameters($pattern, $route)) !== false) {
                if (!isset($params)) {
                    $params = [];
                }

                $resolvedAction = $this->resolveAction($action);
                $middlewareChain = $this->buildMiddlewarePipeline($resolvedAction, $middleware);
                $response = $middlewareChain($this->request, $params);
                $response->send();
                exit;
            }
        }
    }

    /**
     * Build a middleware pipeline.
     *
     * @param callable $action The action to build the pipeline for.
     * @param array $middleware The middleware to add to the pipeline.
     * @return callable The middleware pipeline.
     */
    private function buildMiddlewarePipeline(callable $action, array $middleware): callable
    {
        $middleware = array_merge($this->defaultMiddleware, $middleware);

        return array_reduce(
            array_reverse($middleware),
            function ($next, $middleware) {
                return function (Request $request, array $params) use ($middleware, $next) {
                    $middleware = $this->container->get($middleware);

                    return $middleware->handle($request, fn ($req) => $next($req, $params));
                };
            },
            function (Request $request, array $params) use ($action): Response {
                $result = call_user_func($action, $params, $request);

                if (!$result instanceof Response) {
                    return new Response((string) $result);
                }

                return $result;
            }
        );
    }

    /**
     * Extract parameters from the pattern.
     *
     * @param string $pattern The pattern to extract parameters from.
     * @param string $route The route to extract parameters from.
     * @return array|false The parameters.
     */
    private function extractParameters(string $pattern, string $route): array|false
    {
        // Extract parameter names from the pattern
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        $paramNames = $paramNames[1];

        // If no parameters in pattern, return false
        if (empty($paramNames)) {
            return false;
        }

        // Convert route pattern to regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = str_replace('/', '\/', $regex);
        $regex = '/^' . $regex . '\/?$/'; // Make trailing slash optional

        if (preg_match($regex, $route, $matches)) {
            // Remove the full match
            array_shift($matches);
            // Combine parameter names with their values
            return array_combine($paramNames, $matches);
        }

        return false;
    }

    /**
     * Resolve the action.
     *
     * @param mixed $action The action to resolve.
     * @return callable The resolved action.
     */
    private function resolveAction($action): callable
    {
        // If the action is a callable...
        if (is_callable($action)) {
            return $this->resolveCallable($action);
        }

        // If the action is a controller class name, let's hope
        // that it has an __invoke method!
        if (class_exists($action)) {
            return $this->resolveClassMethod($action, '__invoke');
        }

        // If the action is a class method...
        if (preg_match('/^(.*)@(\w+)$/', $action, $matches)) {
            $class = $matches[1];
            $method = $matches[2];

            return $this->resolveClassMethod($class, $method);
        }

        throw new \InvalidArgumentException("Action must be a callable or a valid controller class name.");
    }

    /**
     * Resolve a class method.
     *
     * @param string $class The class to resolve the method for.
     * @param string $method The method to resolve.
     * @return callable The resolved method.
     */
    private function resolveClassMethod(string $class, string $method): callable
    {
        $controller = new $class();

        // let's ensure the method exists
        if (! method_exists($controller, $method)) {
            throw new \InvalidArgumentException("Method $method does not exist in controller $class.");
        }

        return function ($routeParams) use ($controller, $method, $class) {
            $reflection = new \ReflectionMethod($controller, $method);
            $args = $this->resolveParameters($reflection->getParameters(), $routeParams, $class . '::' . $method);
            return $controller->$method(...$args);
        };
    }

    /**
     * Resolve a callable.
     *
     * @param callable $callable The callable to resolve.
     * @return callable The resolved callable.
     */
    private function resolveCallable(callable $callable): callable
    {
        $reflection = new ReflectionFunction($callable);
        return function ($routeParams) use ($callable, $reflection) {
            $args = $this->resolveParameters($reflection->getParameters(), $routeParams, 'closure');
            return $callable(...$args);
        };
    }

    /**
     * Resolve parameters. This is how we're supporting dependency injection for
     * route actions, whether they're closures or controllers.
     *
     * @param array $parameters The parameters to resolve.
     * @param array $routeParams The route parameters.
     * @param string $context The context of the parameters.
     * @return array The resolved parameters.
     */
    private function resolveParameters(array $parameters, array $routeParams, string $context): array
    {
        $args = [];

        foreach ($parameters as $index => $parameter) {
            $type = $parameter->getType();
            $paramName = $parameter->getName();

            // If we have a matching route parameter, use it
            if (isset($routeParams[$paramName])) {
                $value = $routeParams[$paramName];

                // If there's no type hint, pass as string
                if (!$type) {
                    $args[$index] = $value;
                    continue;
                }

                $typeName = $type->getName();

                // Cast the string value to the appropriate type if needed
                switch ($typeName) {
                    case 'int':
                        $value = (int) $value;
                        break;
                    case 'float':
                        $value = (float) $value;
                        break;
                    case 'bool':
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'array':
                        $value = explode(',', $value);
                        break;
                }

                $args[$index] = $value;
                continue;
            }

            // For non-primitive types, try to resolve from container
            if ($type) {
                try {
                    $args[$index] = $this->container->get($type->getName());
                } catch (\League\Container\Exception\NotFoundException $e) {
                    throw new \RuntimeException(
                        "Could not resolve dependency of type {$type->getName()} for parameter \${$paramName} in {$context}"
                    );
                }
            }
        }

        // Sort by index to ensure correct order
        ksort($args);

        return $args;
    }

    /**
     * Get the routes.
     *
     * @return array The routes.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
