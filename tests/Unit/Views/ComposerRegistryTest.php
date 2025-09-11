<?php

namespace Imarc\Millyard\Tests\Unit\Views;

use Imarc\Millyard\Tests\Unit\TestCase;
use Imarc\Millyard\Views\Composer;
use Imarc\Millyard\Views\ComposerRegistry;

/**
 * Test cases for ComposerRegistry class.
 */
class ComposerRegistryTest extends TestCase
{
    public function test_composer_registry_can_be_instantiated(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $registry = new ComposerRegistry($mockContainer);

        $this->assertInstanceOf(ComposerRegistry::class, $registry);
    }

    public function test_register_composer_adds_composer_for_each_view(): void
    {
        $testComposer = new class () extends Composer {
            public array $views = ['home.twig', 'about.twig'];

            public function withContext(): array
            {
                return ['site_title' => 'Test Site'];
            }
        };

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with(get_class($testComposer))
            ->andReturn($testComposer);

        $registry = new ComposerRegistry($mockContainer);
        $registry->registerComposer(get_class($testComposer));

        $composers = $registry->getComposers();

        $this->assertArrayHasKey('home.twig', $composers);
        $this->assertArrayHasKey('about.twig', $composers);
        $this->assertEquals(get_class($testComposer), $composers['home.twig']);
        $this->assertEquals(get_class($testComposer), $composers['about.twig']);
    }

    public function test_register_composer_throws_exception_for_invalid_composer(): void
    {
        $invalidClass = \stdClass::class;

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $registry = new ComposerRegistry($mockContainer);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Composer class stdClass must extend Imarc\Millyard\Views\Composer");

        $registry->registerComposer($invalidClass);
    }

    public function test_has_composers_returns_false_when_empty(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $registry = new ComposerRegistry($mockContainer);

        $this->assertFalse($registry->hasComposers());
    }

    public function test_has_composers_returns_true_when_composers_registered(): void
    {
        $testComposer = new class () extends Composer {
            public array $views = ['test.twig'];

            public function withContext(): array
            {
                return ['data' => 'value'];
            }
        };

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with(get_class($testComposer))
            ->andReturn($testComposer);

        $registry = new ComposerRegistry($mockContainer);
        $registry->registerComposer(get_class($testComposer));

        $this->assertTrue($registry->hasComposers());
    }

    public function test_get_composers_returns_empty_array_initially(): void
    {
        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $registry = new ComposerRegistry($mockContainer);

        $composers = $registry->getComposers();

        $this->assertIsArray($composers);
        $this->assertEmpty($composers);
    }

    public function test_filter_data_for_composers_merges_composer_context(): void
    {
        $testComposer = new class () extends Composer {
            public array $views = ['user-profile.twig'];

            public function withContext(): array
            {
                $contextData = $this->getContextData();

                return [
                    'full_name' => ($contextData['first_name'] ?? '') . ' ' . ($contextData['last_name'] ?? ''),
                    'is_premium' => ($contextData['user_type'] ?? '') === 'premium',
                    'composer_data' => 'added_by_composer',
                ];
            }
        };

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with(get_class($testComposer))
            ->twice() // Called once during registration, once during filtering
            ->andReturn($testComposer);

        $registry = new ComposerRegistry($mockContainer);
        $registry->registerComposer(get_class($testComposer));

        $originalData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_type' => 'premium',
            'existing_data' => 'preserved',
        ];

        $filteredData = $registry->filterDataForComposers($originalData, 'user-profile.twig');

        // Original data should be preserved
        $this->assertEquals('John', $filteredData['first_name']);
        $this->assertEquals('Doe', $filteredData['last_name']);
        $this->assertEquals('premium', $filteredData['user_type']);
        $this->assertEquals('preserved', $filteredData['existing_data']);

        // Composer data should be added
        $this->assertEquals('John Doe', $filteredData['full_name']);
        $this->assertTrue($filteredData['is_premium']);
        $this->assertEquals('added_by_composer', $filteredData['composer_data']);
    }

    public function test_filter_data_for_composers_ignores_non_matching_templates(): void
    {
        $testComposer = new class () extends Composer {
            public array $views = ['specific-template.twig'];

            public function withContext(): array
            {
                return ['added_data' => 'should_not_appear'];
            }
        };

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with(get_class($testComposer))
            ->once() // Only called during registration
            ->andReturn($testComposer);

        $registry = new ComposerRegistry($mockContainer);
        $registry->registerComposer(get_class($testComposer));

        $originalData = ['original' => 'data'];
        $filteredData = $registry->filterDataForComposers($originalData, 'different-template.twig');

        // Data should be unchanged for non-matching template
        $this->assertEquals($originalData, $filteredData);
        $this->assertArrayNotHasKey('added_data', $filteredData);
    }

    public function test_filter_data_for_composers_handles_multiple_composers(): void
    {
        $composer1 = new class () extends Composer {
            public array $views = ['template1.twig'];

            public function withContext(): array
            {
                return ['composer1_data' => 'value1'];
            }
        };

        $composer2 = new class () extends Composer {
            public array $views = ['template2.twig'];

            public function withContext(): array
            {
                return ['composer2_data' => 'value2'];
            }
        };

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with(get_class($composer1))
            ->andReturn($composer1);
        $mockContainer->shouldReceive('get')
            ->with(get_class($composer2))
            ->andReturn($composer2);

        $registry = new ComposerRegistry($mockContainer);
        $registry->registerComposer(get_class($composer1));
        $registry->registerComposer(get_class($composer2));

        // Test filtering for template1
        $originalData = ['original' => 'data'];
        $filteredData1 = $registry->filterDataForComposers($originalData, 'template1.twig');

        $this->assertEquals('data', $filteredData1['original']);
        $this->assertEquals('value1', $filteredData1['composer1_data']);
        $this->assertArrayNotHasKey('composer2_data', $filteredData1);

        // Test filtering for template2
        $filteredData2 = $registry->filterDataForComposers($originalData, 'template2.twig');

        $this->assertEquals('data', $filteredData2['original']);
        $this->assertEquals('value2', $filteredData2['composer2_data']);
        $this->assertArrayNotHasKey('composer1_data', $filteredData2);
    }

    public function test_filter_data_for_composers_sets_context_data_on_composer(): void
    {
        $testComposer = new class () extends Composer {
            public array $views = ['test.twig'];
            public $receivedContextData = null;

            public function setContextData(array $data): void
            {
                $this->receivedContextData = $data;
                parent::setContextData($data);
            }

            public function withContext(): array
            {
                return ['processed' => true];
            }
        };

        $mockContainer = \Mockery::mock('Imarc\Millyard\Services\Container');
        $mockContainer->shouldReceive('get')
            ->with(get_class($testComposer))
            ->andReturn($testComposer);

        $registry = new ComposerRegistry($mockContainer);
        $registry->registerComposer(get_class($testComposer));

        $originalData = ['test' => 'data', 'user' => 'John'];
        $registry->filterDataForComposers($originalData, 'test.twig');

        // Verify the composer received the context data
        $this->assertEquals($originalData, $testComposer->receivedContextData);
    }

    public function test_composer_registry_constructor_requires_container(): void
    {
        $reflection = new \ReflectionMethod(ComposerRegistry::class, '__construct');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('container', $parameters[0]->getName());
        $this->assertEquals('Imarc\Millyard\Services\Container', $parameters[0]->getType()->getName());
    }

    public function test_composer_registry_method_signatures(): void
    {
        $methodSignatures = [
            'registerComposer' => ['parameters' => 1, 'return' => 'void'],
            'getComposers' => ['parameters' => 0, 'return' => 'array'],
            'hasComposers' => ['parameters' => 0, 'return' => 'bool'],
            'filterDataForComposers' => ['parameters' => 2, 'return' => 'array'],
        ];

        foreach ($methodSignatures as $method => $expected) {
            $reflection = new \ReflectionMethod(ComposerRegistry::class, $method);

            $this->assertEquals(
                $expected['parameters'],
                $reflection->getNumberOfRequiredParameters(),
                "{$method} should require {$expected['parameters']} parameters"
            );

            if ($expected['return'] !== 'void') {
                $this->assertEquals(
                    $expected['return'],
                    $reflection->getReturnType()->getName(),
                    "{$method} should return {$expected['return']}"
                );
            }
        }
    }
}
