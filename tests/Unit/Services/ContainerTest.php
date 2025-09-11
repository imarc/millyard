<?php

namespace Imarc\Millyard\Tests\Unit\Services;

use Imarc\Millyard\Services\Container;
use Imarc\Millyard\Tests\Unit\TestCase;
use League\Container\Container as BaseContainer;

/**
 * Test cases for the Container service.
 */
class ContainerTest extends TestCase
{
    public function test_get_instance_returns_base_container(): void
    {
        $container = Container::getInstance();
        
        $this->assertInstanceOf(BaseContainer::class, $container);
    }

    public function test_get_instance_returns_same_instance(): void
    {
        $container1 = Container::getInstance();
        $container2 = Container::getInstance();
        
        $this->assertSame($container1, $container2);
    }

    public function test_container_can_resolve_dependencies(): void
    {
        $container = Container::getInstance();
        
        // Test that we can get a simple class from the container
        $testClass = $container->get(\stdClass::class);
        
        $this->assertInstanceOf(\stdClass::class, $testClass);
    }

    public function test_magic_call_delegates_to_instance(): void
    {
        $containerProxy = new Container();
        
        // Test that calling methods on the proxy delegates to the singleton instance
        $result = $containerProxy->has(\stdClass::class);
        
        $this->assertIsBool($result);
    }
}
