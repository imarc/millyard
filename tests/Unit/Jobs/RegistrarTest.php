<?php

namespace Imarc\Millyard\Tests\Unit\Jobs;

use Brain\Monkey;
use Imarc\Millyard\Jobs\Job;
use Imarc\Millyard\Jobs\Registrar;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for Jobs Registrar class.
 */
class RegistrarTest extends TestCase
{
    public function test_registrar_can_be_instantiated(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $registrar = new Registrar($mockContainer);

        $this->assertInstanceOf(Registrar::class, $registrar);
    }

    public function test_registrar_uses_discovers_classes_trait(): void
    {
        $reflection = new \ReflectionClass(Registrar::class);
        $traits = $reflection->getTraitNames();
        
        $this->assertContains('Imarc\Millyard\Concerns\DiscoversClasses', $traits);
        $this->assertContains('Imarc\Millyard\Concerns\RegistersHooks', $traits);
    }

    public function test_register_jobs_method_exists_with_correct_signature(): void
    {
        $reflection = new \ReflectionMethod(Registrar::class, 'registerJobs');
        
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals(1, $reflection->getNumberOfParameters());
        $this->assertEquals('void', $reflection->getReturnType()->getName());
        
        $parameters = $reflection->getParameters();
        $this->assertEquals('path', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals('Jobs', $parameters[0]->getDefaultValue());
    }

    public function test_register_job_method_exists_with_correct_signature(): void
    {
        $reflection = new \ReflectionMethod(Registrar::class, 'registerJob');
        
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals(1, $reflection->getNumberOfRequiredParameters());
        $this->assertEquals('void', $reflection->getReturnType()->getName());
        
        $parameters = $reflection->getParameters();
        $this->assertEquals('jobClass', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    public function test_register_job_adds_action_and_fires_hook(): void
    {
        $mockJob = \Mockery::mock(Job::class);
        $mockJob->shouldReceive('getName')->andReturn('test_job');

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with('TestJobClass')
            ->andReturn($mockJob);

        $registrar = new class ($mockContainer) extends Registrar {
            public $addActionCalls = [];
            
            public function addAction($hook, $callback, $priority = 10, $acceptedArgs = 1): void
            {
                $this->addActionCalls[] = [
                    'hook' => $hook,
                    'callback' => $callback,
                    'priority' => $priority,
                    'acceptedArgs' => $acceptedArgs,
                ];
            }
        };

        // Since do_action is already mocked in bootstrap, we'll just verify the method runs
        // In a real environment, this would fire the millyard_job_registered action

        $registrar->registerJob('TestJobClass');

        // Verify addAction was called correctly
        $this->assertCount(1, $registrar->addActionCalls);
        $this->assertEquals('test_job', $registrar->addActionCalls[0]['hook']);
        $this->assertEquals([$mockJob, 'handle'], $registrar->addActionCalls[0]['callback']);
        $this->assertEquals(10, $registrar->addActionCalls[0]['priority']);
        $this->assertEquals(3, $registrar->addActionCalls[0]['acceptedArgs']);
    }

    public function test_registrar_constructor_requires_container(): void
    {
        $reflection = new \ReflectionMethod(Registrar::class, '__construct');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('container', $parameters[0]->getName());
        $this->assertEquals('Imarc\Millyard\Services\Container', $parameters[0]->getType()->getName());
    }

    public function test_register_jobs_method_exists_and_is_callable(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $registrar = new Registrar($mockContainer);
        
        // Test that the method exists and is callable
        $this->assertTrue(method_exists($registrar, 'registerJobs'));
        $this->assertTrue(is_callable([$registrar, 'registerJobs']));
        
        // Test method signature
        $reflection = new \ReflectionMethod(Registrar::class, 'registerJobs');
        $this->assertTrue($reflection->isPublic());
    }

    public function test_register_jobs_method_signature(): void
    {
        $reflection = new \ReflectionMethod(Registrar::class, 'registerJobs');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('path', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals('Jobs', $parameters[0]->getDefaultValue());
    }

    public function test_registrar_integrates_with_job_lifecycle(): void
    {
        // Create a concrete job for testing
        $testJob = new class () extends Job {
            protected string $jobName = 'integration_test_job';
            
            public function handle($data)
            {
                return "Processed: $data";
            }
        };

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with(get_class($testJob))
            ->andReturn($testJob);

        $registrar = new class ($mockContainer) extends Registrar {
            public $hookRegistrations = [];
            
            public function addAction($hook, $callback, $priority = 10, $acceptedArgs = 1): void
            {
                $this->hookRegistrations[] = [
                    'hook' => $hook,
                    'callback' => $callback,
                    'priority' => $priority,
                    'acceptedArgs' => $acceptedArgs,
                ];
            }
        };

        // Since do_action is already mocked in bootstrap, we'll just verify the method runs
        // In a real environment, this would fire the millyard_job_registered action

        $registrar->registerJob(get_class($testJob));

        // Verify the job was registered correctly
        $this->assertCount(1, $registrar->hookRegistrations);
        $this->assertEquals('integration_test_job', $registrar->hookRegistrations[0]['hook']);
        $this->assertEquals([$testJob, 'handle'], $registrar->hookRegistrations[0]['callback']);
    }
}
