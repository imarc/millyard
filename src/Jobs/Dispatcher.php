<?php

namespace Imarc\Millyard\Jobs;

use Imarc\Millyard\Services\Container;

/**
 * Dispatches jobs to the queue.
 */
class Dispatcher
{
    private array $args = [];
    private int $timestamp;
    private string $jobName;
    private Job $job;

    public function __construct(
        private Container $container
    ) {
    }

    /**
     * Dispatch a job.
     *
     * @param string $jobClass The class name of the job to dispatch.
     * @return static
     */
    public function dispatch(string $jobClass): static
    {
        $this->job = $this->container->get($jobClass);
        $this->jobName = $this->job->getName();

        return $this;
    }

    /**
     * Dispatch a job immediately.
     *
     * @return static
     */
    public function now(): static
    {
        $this->timestamp = time();

        return $this;
    }

    /**
     * Dispatch a job at a specific time.
     *
     * @param string|int $time The time to dispatch the job at.
     * @return static
     */
    public function at(string|int $time): static
    {
        $this->timestamp = is_string($time) ? strtotime($time) : $time;

        return $this;
    }

    /**
     * Execute the job.
     *
     * @param bool $useQueue Whether to use the queue.
     */
    public function execute(bool $useQueue = true): void
    {
        if ($useQueue) {
            wp_schedule_single_event($this->timestamp, $this->jobName, $this->args);
        } else {
            do_action($this->jobName, ...$this->args);
        }
    }

    /**
     * Set the arguments for the job.
     *
     * @param array $args The arguments to set for the job.
     * @return static
     */
    public function args(array $args): static
    {
        $this->args = $args;

        return $this;
    }
}
