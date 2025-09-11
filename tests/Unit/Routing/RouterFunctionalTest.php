<?php

namespace Imarc\Millyard\Tests\Unit\Routing;

use Imarc\Millyard\Routing\Router;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Functional tests for Router class.
 *
 * These tests verify Router functionality through public interface.
 */
class RouterFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the singleton instance before each test
        $reflection = new \ReflectionClass(Router::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    public function test_router_can_register_simple_routes(): void
    {
        $router = Router::getInstance();

        $getAction = function () {
            return 'GET response';
        };

        $postAction = function () {
            return 'POST response';
        };

        // Register routes
        $router->get('/test', $getAction);
        $router->post('/test', $postAction);

        $routes = $router->getRoutes();

        // Verify GET route
        $this->assertArrayHasKey('GET', $routes);
        $this->assertArrayHasKey('/test', $routes['GET']);
        $this->assertEquals($getAction, $routes['GET']['/test']['action']);

        // Verify POST route
        $this->assertArrayHasKey('POST', $routes);
        $this->assertArrayHasKey('/test', $routes['POST']);
        $this->assertEquals($postAction, $routes['POST']['/test']['action']);
    }

    public function test_router_can_register_routes_with_parameters(): void
    {
        $router = Router::getInstance();

        $action = function ($id, $slug) {
            return "User $id, Post $slug";
        };

        $router->get('/users/{id}/posts/{slug}', $action);

        $routes = $router->getRoutes();

        $this->assertArrayHasKey('GET', $routes);
        $this->assertArrayHasKey('/users/{id}/posts/{slug}', $routes['GET']);
        $this->assertEquals($action, $routes['GET']['/users/{id}/posts/{slug}']['action']);
    }

    public function test_router_normalizes_paths(): void
    {
        $router = Router::getInstance();

        $action = function () {
            return 'response';
        };

        // Test trailing slash removal
        $router->get('/test-path/', $action);
        $router->get('/another-path///', $action); // Multiple slashes

        $routes = $router->getRoutes();

        // Should be normalized
        $this->assertArrayHasKey('/test-path', $routes['GET']);
        $this->assertArrayNotHasKey('/test-path/', $routes['GET']);

        // Root path should keep its slash
        $router->get('/', $action);
        $routes = $router->getRoutes(); // Get fresh routes after adding root
        $this->assertArrayHasKey('/', $routes['GET']);
    }

    public function test_router_supports_method_chaining(): void
    {
        $router = Router::getInstance();

        $action = function () {
            return 'response';
        };

        // Test fluent interface
        $result = $router
            ->get('/test1', $action)
            ->post('/test2', $action)
            ->put('/test3', $action)
            ->setDefaultMiddleware(['TestMiddleware']);

        $this->assertSame($router, $result);

        $routes = $router->getRoutes();
        $this->assertArrayHasKey('/test1', $routes['GET']);
        $this->assertArrayHasKey('/test2', $routes['POST']);
        $this->assertArrayHasKey('/test3', $routes['PUT']);
    }

    public function test_router_handles_all_http_methods(): void
    {
        $router = Router::getInstance();

        $action = function () {
            return 'response';
        };

        // Register routes for all HTTP methods
        $router->get('/resource', $action);
        $router->post('/resource', $action);
        $router->put('/resource', $action);
        $router->delete('/resource', $action);
        $router->patch('/resource', $action);

        $routes = $router->getRoutes();

        $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        foreach ($httpMethods as $method) {
            $this->assertArrayHasKey($method, $routes, "Routes should contain {$method} method");
            $this->assertArrayHasKey('/resource', $routes[$method], "{$method} should have /resource route");
        }
    }

    public function test_multiple_routes_per_method(): void
    {
        $router = Router::getInstance();

        $action1 = function () {
            return 'response1';
        };

        $action2 = function () {
            return 'response2';
        };

        // Register multiple GET routes
        $router->get('/route1', $action1);
        $router->get('/route2', $action2);

        $routes = $router->getRoutes();

        $this->assertCount(2, $routes['GET']);
        $this->assertEquals($action1, $routes['GET']['/route1']['action']);
        $this->assertEquals($action2, $routes['GET']['/route2']['action']);
    }

    public function test_routes_with_complex_patterns(): void
    {
        $router = Router::getInstance();

        $action = function () {
            return 'response';
        };

        // Register routes with various parameter patterns
        $complexRoutes = [
            '/api/v1/users/{id}',
            '/blog/{year}/{month}/{day}/{slug}',
            '/files/{category}/{subcategory}/{filename}',
            '/admin/users/{userId}/roles/{roleId}',
        ];

        foreach ($complexRoutes as $route) {
            $router->get($route, $action);
        }

        $routes = $router->getRoutes();

        foreach ($complexRoutes as $route) {
            $this->assertArrayHasKey($route, $routes['GET'], "Should have route: {$route}");
        }
    }
}
