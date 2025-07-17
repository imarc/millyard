<?php

namespace Imarc\Millyard\Services;

use Imarc\Millyard\Concerns\RegistersHooks;

class Cron
{
    use RegistersHooks;

    private ?array $schedules = null;

    /**
     * Schedule an event
     */
    public function schedule(string $hook, string $recurrence, callable $callback, ?int $timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        $cronEvent = $hook . '_' . $recurrence;
        $this->validateRecurrence($recurrence);

        if (! wp_next_scheduled($cronEvent)) {
            $this->validateRecurrence($recurrence);
            wp_schedule_event($timestamp, $recurrence, $cronEvent);
        }

        $this->addAction($cronEvent, $callback);
    }

    public function scheduleJob(string $jobClass, string $recurrence, ?int $timestamp = null, array $args = [])
    {
        $name = (new $jobClass())->getName();

        $this->schedule($name, $recurrence, function () use ($jobClass, $args) {
            $jobClass::dispatch(...$args)
                ->now()
                ->execute(false);
        }, $timestamp);
    }

    public function cancel(string $hook, ?string $recurrence = null)
    {
        $cronEvent = $recurrence ? $hook . '_' . $recurrence : $hook;
        wp_clear_scheduled_hook($cronEvent);
    }

    private function getSchedules(): array
    {
        $this->schedules = is_null($this->schedules) ? wp_get_schedules() : $this->schedules;

        return $this->schedules;
    }

    private function validateRecurrence($recurrence)
    {
        $schedules = $this->getSchedules();

        if (! isset($schedules[$recurrence])) {
            error_log('CronHooks: Invalid recurrence: ' . $recurrence);
        }
    }
}
