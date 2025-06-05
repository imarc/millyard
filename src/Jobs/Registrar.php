<?php

namespace Imarc\Millyard\Jobs;

use Imarc\Millyard\Attributes\RegistersJob;
use Imarc\Millyard\Concerns\DiscoversClasses;

class Registrar
{
    use DiscoversClasses;

    public function registerJobs(string $path = 'Jobs'): void
    {
        $jobClasses = $this->discoverClassesForAttribute(RegistersJob::class, $path);

        foreach ($jobClasses as $jobClass) {
            $this->registerJob($jobClass);
        }
    }

    public function registerJob(string $jobClass): void
    {
        $job = new $jobClass();

        if (! method_exists($job, 'register')) {
            throw new \RuntimeException(sprintf('Could not register class %s. register() does not exist', $jobClass));
        }

        $job->register();
        do_action('millyard_job_registered', $jobClass);
    }
}