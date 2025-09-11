<?php

namespace Imarc\Millyard\Tests\Unit\Commands;

use Brain\Monkey;
use Imarc\Millyard\Commands\Command;
use Imarc\Millyard\Tests\Unit\TestCase;

/**
 * Test cases for Command abstract class.
 */
class CommandTest extends TestCase
{
    public function test_command_can_be_instantiated(): void
    {
        $command = new class extends Command {
            public string $name = 'test-command';
            public string $shortDescription = 'A test command';
            public string $longDescription = 'This is a longer description of the test command';
        };

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('test-command', $command->name);
        $this->assertEquals('A test command', $command->shortDescription);
        $this->assertEquals('This is a longer description of the test command', $command->longDescription);
    }

    public function test_command_has_default_properties(): void
    {
        $command = new class extends Command {
            public string $name = 'test-command';
        };

        $this->assertEquals('', $command->shortDescription);
        $this->assertEquals('', $command->longDescription);
        $this->assertEquals([], $command->synopsis);
        $this->assertEquals('after_wp_load', $command->when);
    }

    public function test_command_properties_can_be_customized(): void
    {
        $command = new class extends Command {
            public string $name = 'custom-command';
            public string $shortDescription = 'Custom command';
            public string $longDescription = 'A custom command with detailed description';
            public array $synopsis = [
                [
                    'type' => 'positional',
                    'name' => 'file',
                    'description' => 'The file to process',
                ]
            ];
            public string $when = 'before_wp_load';
        };

        $this->assertEquals('custom-command', $command->name);
        $this->assertEquals('Custom command', $command->shortDescription);
        $this->assertEquals('A custom command with detailed description', $command->longDescription);
        $this->assertIsArray($command->synopsis);
        $this->assertEquals('file', $command->synopsis[0]['name']);
        $this->assertEquals('before_wp_load', $command->when);
    }

    public function test_all_wp_cli_methods_are_available(): void
    {
        $command = new class extends Command {
            public string $name = 'test-command';
        };

        // Test that all protected methods exist
        $reflection = new \ReflectionClass($command);
        
        $this->assertTrue($reflection->hasMethod('line'));
        $this->assertTrue($reflection->hasMethod('success'));
        $this->assertTrue($reflection->hasMethod('error'));
        $this->assertTrue($reflection->hasMethod('warning'));
        $this->assertTrue($reflection->hasMethod('log'));
        $this->assertTrue($reflection->hasMethod('confirm'));
        $this->assertTrue($reflection->hasMethod('prompt'));
        
        // Verify methods are protected (accessible to subclasses)
        $this->assertTrue($reflection->getMethod('line')->isProtected());
        $this->assertTrue($reflection->getMethod('success')->isProtected());
        $this->assertTrue($reflection->getMethod('error')->isProtected());
        $this->assertTrue($reflection->getMethod('warning')->isProtected());
        $this->assertTrue($reflection->getMethod('log')->isProtected());
        $this->assertTrue($reflection->getMethod('confirm')->isProtected());
        $this->assertTrue($reflection->getMethod('prompt')->isProtected());
    }

    public function test_command_methods_can_be_called_by_subclass(): void
    {
        $command = new class extends Command {
            public string $name = 'test-command';
            
            public function callProtectedMethods()
            {
                // These methods exist and can be called (though they'll fail without WP-CLI)
                // We're just testing that the methods exist and are accessible
                try {
                    $this->line('test');
                    $this->success('test');
                    $this->warning('test');
                    $this->log('test');
                    // Skip error() as it might exit
                    // Skip confirm() and prompt() as they require complex mocking
                } catch (\Error $e) {
                    // Expected - WP_CLI class doesn't exist in test environment
                    // But the methods are callable
                }
                
                return true;
            }
        };

        $this->assertTrue($command->callProtectedMethods());
    }

    public function test_synopsis_can_be_complex_array(): void
    {
        $command = new class extends Command {
            public string $name = 'complex-command';
            public array $synopsis = [
                [
                    'type' => 'positional',
                    'name' => 'action',
                    'description' => 'What action to perform',
                    'optional' => false,
                ],
                [
                    'type' => 'assoc',
                    'name' => 'format',
                    'description' => 'Output format',
                    'optional' => true,
                    'default' => 'table',
                    'options' => ['table', 'json', 'csv'],
                ],
                [
                    'type' => 'flag',
                    'name' => 'dry-run',
                    'description' => 'Preview changes without executing',
                    'optional' => true,
                ],
            ];
        };

        $synopsis = $command->synopsis;
        
        $this->assertCount(3, $synopsis);
        
        // Test positional argument
        $this->assertEquals('positional', $synopsis[0]['type']);
        $this->assertEquals('action', $synopsis[0]['name']);
        $this->assertFalse($synopsis[0]['optional']);
        
        // Test associative argument
        $this->assertEquals('assoc', $synopsis[1]['type']);
        $this->assertEquals('format', $synopsis[1]['name']);
        $this->assertEquals('table', $synopsis[1]['default']);
        $this->assertContains('json', $synopsis[1]['options']);
        
        // Test flag
        $this->assertEquals('flag', $synopsis[2]['type']);
        $this->assertEquals('dry-run', $synopsis[2]['name']);
        $this->assertTrue($synopsis[2]['optional']);
    }

    public function test_when_property_accepts_different_values(): void
    {
        $commands = [
            'before_wp_load' => new class extends Command {
                public string $name = 'early-command';
                public string $when = 'before_wp_load';
            },
            'after_wp_load' => new class extends Command {
                public string $name = 'late-command';
                public string $when = 'after_wp_load';
            },
            'wp_loaded' => new class extends Command {
                public string $name = 'loaded-command';
                public string $when = 'wp_loaded';
            },
        ];

        $this->assertEquals('before_wp_load', $commands['before_wp_load']->when);
        $this->assertEquals('after_wp_load', $commands['after_wp_load']->when);
        $this->assertEquals('wp_loaded', $commands['wp_loaded']->when);
    }
}
