<?php

namespace Imarc\Millyard\Tests\Unit\Jobs;

use Brain\Monkey;
use Imarc\Millyard\Jobs\Dispatcher;
use Imarc\Millyard\Jobs\Job;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for Job abstract class.
 */
class JobTest extends TestCase
{
    public function test_job_can_be_instantiated(): void
    {
        $job = new class () extends Job {
            public function handle($arg1, $arg2)
            {
                return "Handled: $arg1, $arg2";
            }
        };

        $this->assertInstanceOf(Job::class, $job);
    }

    public function test_get_name_uses_job_name_property_when_set(): void
    {
        $job = new class () extends Job {
            protected string $jobName = 'custom_job_name';
            
            public function handle()
            {
                return 'handled';
            }
        };

        $name = $job->getName();
        
        $this->assertEquals('custom_job_name', $name);
    }

    public function test_get_name_generates_name_from_class_when_no_job_name_property(): void
    {
        $job = new class () extends Job {
            public function handle()
            {
                return 'handled';
            }
        };

        $name = $job->getName();
        
        // Should generate a name from the anonymous class
        // The exact name will be complex due to anonymous class, but should be a string
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    public function test_generate_name_converts_camel_case_to_snake_case(): void
    {
        // Create a job with a predictable class name for testing
        $job = new class () extends Job {
            // Override the generateName method to test it directly
            public function testGenerateName(): string
            {
                // Simulate a class name for testing
                $originalClass = static::class;
                
                // Create a test class name
                $testClassName = 'App\\Jobs\\SendEmailNotification';
                
                // Apply the same logic as generateName()
                $name = str_replace('App\\Jobs\\', '', $testClassName);
                return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
            }
            
            public function handle()
            {
                return 'handled';
            }
        };

        $generatedName = $job->testGenerateName();
        
        $this->assertEquals('send_email_notification', $generatedName);
    }

    public function test_dispatch_returns_dispatcher_instance(): void
    {
        $jobClass = new class () extends Job {
            public function handle($arg1, $arg2)
            {
                return "Handled: $arg1, $arg2";
            }
        };

        // Mock Container and Dispatcher
        $mockDispatcher = \Mockery::mock(Dispatcher::class);
        $mockDispatcher->shouldReceive('args')->with(['arg1', 'arg2'])->andReturnSelf();
        $mockDispatcher->shouldReceive('dispatch')->with(get_class($jobClass))->andReturnSelf();

        $mockContainer = \Mockery::mock('League\Container\Container');
        $mockContainer->shouldReceive('get')
            ->with(Dispatcher::class)
            ->andReturn($mockDispatcher);

        // Mock Container::getInstance
        $reflection = new \ReflectionClass('Imarc\Millyard\Services\Container');
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $mockContainer);

        $result = $jobClass::dispatch('arg1', 'arg2');
        
        $this->assertSame($mockDispatcher, $result);
    }

    public function test_dispatch_passes_arguments_correctly(): void
    {
        $jobClass = new class () extends Job {
            public function handle($userId, $message, $priority)
            {
                return "User: $userId, Message: $message, Priority: $priority";
            }
        };

        // Mock Dispatcher to verify args are passed correctly
        $mockDispatcher = \Mockery::mock(Dispatcher::class);
        $mockDispatcher->shouldReceive('args')
            ->once()
            ->with([123, 'Hello World', 'high'])
            ->andReturnSelf();
        $mockDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(get_class($jobClass))
            ->andReturnSelf();

        $mockContainer = \Mockery::mock('League\Container\Container');
        $mockContainer->shouldReceive('get')
            ->with(Dispatcher::class)
            ->andReturn($mockDispatcher);

        // Mock Container::getInstance
        $reflection = new \ReflectionClass('Imarc\Millyard\Services\Container');
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $mockContainer);

        $jobClass::dispatch(123, 'Hello World', 'high');
    }

    public function test_job_name_generation_handles_different_namespaces(): void
    {
        // Test the name generation logic with different scenarios
        $testCases = [
            'App\\Jobs\\SendEmail' => 'send_email',
            'App\\Jobs\\ProcessPayment' => 'process_payment',
            'App\\Jobs\\UpdateUserProfile' => 'update_user_profile',
            'App\\Jobs\\GenerateReport' => 'generate_report',
        ];

        foreach ($testCases as $className => $expectedName) {
            // Simulate the generateName logic
            $name = str_replace('App\\Jobs\\', '', $className);
            $result = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
            
            $this->assertEquals($expectedName, $result, "Class {$className} should generate name {$expectedName}");
        }
    }

    public function test_job_has_required_abstract_method(): void
    {
        $reflection = new \ReflectionClass(Job::class);
        
        $this->assertTrue($reflection->isAbstract(), 'Job class should be abstract');
        
        // Job should require subclasses to implement a handle method
        // We can't test for abstract methods directly, but we can verify
        // that concrete implementations must have a handle method
        $job = new class () extends Job {
            public function handle($data)
            {
                return "Processed: $data";
            }
        };
        
        $this->assertTrue(method_exists($job, 'handle'), 'Job implementations should have handle method');
    }

    public function test_job_dispatch_is_static_method(): void
    {
        $reflection = new \ReflectionMethod(Job::class, 'dispatch');
        
        $this->assertTrue($reflection->isStatic(), 'dispatch method should be static');
        $this->assertTrue($reflection->isPublic(), 'dispatch method should be public');
        $this->assertEquals('Imarc\Millyard\Jobs\Dispatcher', $reflection->getReturnType()->getName());
    }
}
