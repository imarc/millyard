<?php

namespace Imarc\Millyard\Tests\Unit\Routing;

use Imarc\Millyard\Routing\Router;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for Router class - focusing on public interface.
 */
class RouterTest extends TestCase
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

    public function test_router_is_singleton(): void
    {
        $router1 = Router::getInstance();
        $router2 = Router::getInstance();

        $this->assertSame($router1, $router2);
        $this->assertInstanceOf(Router::class, $router1);
    }

    public function test_get_route_registration(): void
    {
        // We'll test this without the complex container dependencies
        $this->assertTrue(method_exists(Router::class, 'get'));
        $this->assertTrue(method_exists(Router::class, 'getRoutes'));
        
        // Test that the method signature is correct
        $reflection = new \ReflectionMethod(Router::class, 'get');
        $this->assertEquals(2, $reflection->getNumberOfRequiredParameters());
        $this->assertTrue($reflection->hasReturnType());
        $this->assertEquals('static', $reflection->getReturnType()->getName());
    }

    public function test_all_http_methods_are_available(): void
    {
        $httpMethods = ['get', 'post', 'put', 'delete', 'patch'];
        
        foreach ($httpMethods as $method) {
            $this->assertTrue(method_exists(Router::class, $method), "Router should have {$method} method");
            
            $reflection = new \ReflectionMethod(Router::class, $method);
            $this->assertEquals(2, $reflection->getNumberOfRequiredParameters(), "{$method} should require 2 parameters");
            $this->assertEquals('static', $reflection->getReturnType()->getName(), "{$method} should return static for chaining");
        }
    }

    public function test_middleware_method_exists(): void
    {
        $this->assertTrue(method_exists(Router::class, 'middleware'));
        
        $reflection = new \ReflectionMethod(Router::class, 'middleware');
        $this->assertEquals(1, $reflection->getNumberOfRequiredParameters());
        $this->assertEquals('void', $reflection->getReturnType()->getName());
    }

    public function test_set_default_middleware_method_exists(): void
    {
        $this->assertTrue(method_exists(Router::class, 'setDefaultMiddleware'));
        
        $reflection = new \ReflectionMethod(Router::class, 'setDefaultMiddleware');
        $this->assertEquals(1, $reflection->getNumberOfRequiredParameters());
        $this->assertEquals('static', $reflection->getReturnType()->getName());
    }

    public function test_handle_request_method_exists(): void
    {
        $this->assertTrue(method_exists(Router::class, 'handleRequest'));
        
        $reflection = new \ReflectionMethod(Router::class, 'handleRequest');
        $this->assertEquals(2, $reflection->getNumberOfRequiredParameters());
        $this->assertEquals('void', $reflection->getReturnType()->getName());
    }

    public function test_router_has_expected_public_methods(): void
    {
        $expectedMethods = [
            'getInstance',
            'get',
            'post', 
            'put',
            'delete',
            'patch',
            'middleware',
            'setDefaultMiddleware',
            'handleRequest',
            'getRoutes',
        ];
        
        foreach ($expectedMethods as $method) {
            $this->assertTrue(method_exists(Router::class, $method), "Router should have {$method} method");
        }
    }

    public function test_router_method_signatures(): void
    {
        $methodSignatures = [
            'get' => ['parameters' => 2, 'return' => 'static'],
            'post' => ['parameters' => 2, 'return' => 'static'],
            'put' => ['parameters' => 2, 'return' => 'static'],
            'delete' => ['parameters' => 2, 'return' => 'static'],
            'patch' => ['parameters' => 2, 'return' => 'static'],
            'setDefaultMiddleware' => ['parameters' => 1, 'return' => 'static'],
            'middleware' => ['parameters' => 1, 'return' => 'void'],
            'handleRequest' => ['parameters' => 2, 'return' => 'void'],
            'getRoutes' => ['parameters' => 0, 'return' => 'array'],
        ];
        
        foreach ($methodSignatures as $method => $expected) {
            $reflection = new \ReflectionMethod(Router::class, $method);
            
            $this->assertEquals(
                $expected['parameters'], 
                $reflection->getNumberOfRequiredParameters(),
                "{$method} should require {$expected['parameters']} parameters"
            );
            
            if ($expected['return'] !== 'void') {
                $this->assertEquals(
                    $expected['return'], 
                    $reflection->getReturnType()->getName(),
                    "{$method} should return {$expected['return']}"
                );
            }
        }
    }

    public function test_router_properties_are_properly_encapsulated(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        
        // Test that important properties are private
        $privateProperties = ['instance', 'routes', 'container', 'request', 'defaultMiddleware', 'currentPath'];
        
        foreach ($privateProperties as $property) {
            $this->assertTrue($reflection->hasProperty($property), "Router should have {$property} property");
            $prop = $reflection->getProperty($property);
            $this->assertTrue($prop->isPrivate(), "{$property} should be private");
        }
    }

    public function test_constructor_is_private(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertTrue($constructor->isPrivate(), 'Constructor should be private for singleton pattern');
    }
}
