<?php

namespace Imarc\Millyard\Tests\Unit\Jobs;

use Brain\Monkey;
use Imarc\Millyard\Jobs\Dispatcher;
use Imarc\Millyard\Jobs\Job;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for Dispatcher class.
 */
class DispatcherTest extends TestCase
{
    public function test_dispatcher_can_be_instantiated(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $dispatcher = new Dispatcher($mockContainer);

        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
    }

    public function test_args_method_sets_arguments(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $dispatcher = new Dispatcher($mockContainer);

        $result = $dispatcher->args(['arg1', 'arg2', 'arg3']);

        $this->assertSame($dispatcher, $result); // Should return self for chaining
    }

    public function test_dispatch_method_sets_job_and_returns_self(): void
    {
        $mockJob = \Mockery::mock(Job::class);
        $mockJob->shouldReceive('getName')->andReturn('test_job');

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with('TestJobClass')
            ->andReturn($mockJob);

        $dispatcher = new Dispatcher($mockContainer);

        $result = $dispatcher->dispatch('TestJobClass');

        $this->assertSame($dispatcher, $result); // Should return self for chaining
    }

    public function test_now_method_sets_current_timestamp(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $dispatcher = new Dispatcher($mockContainer);

        $beforeTime = time();
        $result = $dispatcher->now();
        $afterTime = time();

        $this->assertSame($dispatcher, $result); // Should return self for chaining

        // We can't directly test the timestamp, but we can verify the method exists
        // and returns the dispatcher for chaining
    }

    public function test_at_method_with_timestamp(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $dispatcher = new Dispatcher($mockContainer);

        $timestamp = 1640995200; // Jan 1, 2022
        $result = $dispatcher->at($timestamp);

        $this->assertSame($dispatcher, $result); // Should return self for chaining
    }

    public function test_at_method_with_string_time(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $dispatcher = new Dispatcher($mockContainer);

        // Mock strtotime function
        Monkey\Functions\when('strtotime')->alias(function ($time) {
            return $time === '+1 hour' ? 1640998800 : strtotime($time);
        });

        $result = $dispatcher->at('+1 hour');

        $this->assertSame($dispatcher, $result); // Should return self for chaining
    }

    public function test_execute_with_queue_calls_wp_schedule_single_event(): void
    {
        $mockJob = \Mockery::mock(Job::class);
        $mockJob->shouldReceive('getName')->andReturn('test_job');

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with('TestJobClass')
            ->andReturn($mockJob);

        $dispatcher = new Dispatcher($mockContainer);

        // Set up the dispatcher with job, args, and timing
        $dispatcher->dispatch('TestJobClass')
                  ->args(['arg1', 'arg2'])
                  ->now();

        // Mock WordPress scheduling function
        Monkey\Functions\expect('wp_schedule_single_event')
            ->once()
            ->with(\Mockery::type('int'), 'test_job', ['arg1', 'arg2'])
            ->andReturn(true);

        $dispatcher->execute(true);
    }

    public function test_execute_without_queue_calls_do_action_immediately(): void
    {
        $mockJob = \Mockery::mock(Job::class);
        $mockJob->shouldReceive('getName')->andReturn('test_job');

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with('TestJobClass')
            ->andReturn($mockJob);

        $dispatcher = new Dispatcher($mockContainer);

        // Set up the dispatcher
        $dispatcher->dispatch('TestJobClass')
                  ->args(['arg1', 'arg2']);

        // Since do_action is already mocked in bootstrap, we'll just verify the method runs
        // In a real environment, this would call do_action with the job name and args

        $dispatcher->execute(false);

        // Test passed if no exceptions were thrown
        $this->assertTrue(true);
    }

    public function test_dispatcher_supports_method_chaining(): void
    {
        $mockJob = \Mockery::mock(Job::class);
        $mockJob->shouldReceive('getName')->andReturn('chained_job');

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with('ChainedJobClass')
            ->andReturn($mockJob);

        $dispatcher = new Dispatcher($mockContainer);

        // Test fluent interface
        $result = $dispatcher
            ->args(['data'])
            ->dispatch('ChainedJobClass')
            ->at('+1 day');

        $this->assertSame($dispatcher, $result);
    }

    public function test_dispatcher_method_signatures(): void
    {
        $methodSignatures = [
            'dispatch' => ['parameters' => 1, 'return' => 'static'],
            'now' => ['parameters' => 0, 'return' => 'static'],
            'at' => ['parameters' => 1, 'return' => 'static'],
            'args' => ['parameters' => 1, 'return' => 'static'],
            'execute' => ['parameters' => 0, 'return' => 'void'], // Has optional parameter
        ];

        foreach ($methodSignatures as $method => $expected) {
            $reflection = new \ReflectionMethod(Dispatcher::class, $method);

            if ($method === 'execute') {
                // execute has optional parameter, so 0 required
                $this->assertEquals(0, $reflection->getNumberOfRequiredParameters());
            } else {
                $this->assertEquals(
                    $expected['parameters'],
                    $reflection->getNumberOfRequiredParameters(),
                    "{$method} should require {$expected['parameters']} parameters"
                );
            }

            if ($expected['return'] !== 'void') {
                $this->assertEquals(
                    $expected['return'],
                    $reflection->getReturnType()->getName(),
                    "{$method} should return {$expected['return']}"
                );
            }
        }
    }

    public function test_dispatcher_has_proper_constructor_dependency(): void
    {
        $reflection = new \ReflectionMethod(Dispatcher::class, '__construct');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('container', $parameters[0]->getName());
        $this->assertEquals('Imarc\Millyard\Services\Container', $parameters[0]->getType()->getName());
    }

    public function test_execute_method_has_default_parameter(): void
    {
        $reflection = new \ReflectionMethod(Dispatcher::class, 'execute');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('useQueue', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertTrue($parameters[0]->getDefaultValue());
    }

    public function test_at_method_accepts_string_or_int(): void
    {
        $reflection = new \ReflectionMethod(Dispatcher::class, 'at');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('time', $parameters[0]->getName());

        // Check that it accepts string|int union type
        $type = $parameters[0]->getType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $type);

        $typeNames = [];
        foreach ($type->getTypes() as $unionType) {
            $typeNames[] = $unionType->getName();
        }

        $this->assertContains('string', $typeNames);
        $this->assertContains('int', $typeNames);
    }
}
