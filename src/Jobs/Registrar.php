<?php

namespace Imarc\Millyard\Jobs;

use Imarc\Millyard\Attributes\RegistersJob;
use Imarc\Millyard\Concerns\DiscoversClasses;
use Imarc\Millyard\Concerns\RegistersHooks;
use Imarc\Millyard\Services\Container;

class Registrar
{
    use DiscoversClasses, RegistersHooks;

    public function __construct(private Container $container)
    {
    }

    public function registerJobs(string $path = 'Jobs'): void
    {
        $jobClasses = $this->discoverClassesForAttribute(RegistersJob::class, $path);

        foreach ($jobClasses as $jobClass) {
            $this->registerJob($jobClass);
        }
    }

    public function registerJob(string $jobClass): void
    {
        $job = $this->container->get($jobClass);
        $this->addAction($job->getName(), [$job, 'handle'], 10, 3);
        
        do_action('millyard_job_registered', $jobClass);
    }
}