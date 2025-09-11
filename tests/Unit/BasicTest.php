<?php

namespace Imarc\Millyard\Tests\Unit;

/**
 * Basic test to verify our test setup is working.
 */
class BasicTest extends TestCase
{
    public function test_basic_assertion(): void
    {
        $this->assertTrue(true);
    }

    public function test_brain_monkey_is_working(): void
    {
        \Brain\Monkey\Functions\when('test_function')->justReturn('mocked_value');

        $result = test_function();

        $this->assertEquals('mocked_value', $result);
    }

    public function test_helper_functions_are_loaded(): void
    {
        $this->assertTrue(function_exists('is_hmr'));
        $this->assertTrue(function_exists('config'));
        $this->assertTrue(function_exists('env'));
        $this->assertTrue(function_exists('csrf_token'));
    }
}
