<?php

namespace Imarc\Millyard\Tests\Unit\Views;

use Imarc\Millyard\Tests\Unit\TestCase;
use Imarc\Millyard\Views\Composer;

/**
 * Test cases for Composer abstract class.
 */
class ComposerTest extends TestCase
{
    public function test_composer_can_be_instantiated(): void
    {
        $composer = new class () extends Composer {
            public array $views = ['home.twig', 'about.twig'];

            public function withContext(): array
            {
                return [
                    'site_title' => 'Test Site',
                    'year' => 2024,
                ];
            }
        };

        $this->assertInstanceOf(Composer::class, $composer);
        $this->assertEquals(['home.twig', 'about.twig'], $composer->views);
    }

    public function test_composer_has_required_abstract_method(): void
    {
        $reflection = new \ReflectionClass(Composer::class);

        $this->assertTrue($reflection->isAbstract(), 'Composer class should be abstract');

        // Verify withContext is abstract
        $withContextMethod = $reflection->getMethod('withContext');
        $this->assertTrue($withContextMethod->isAbstract(), 'withContext method should be abstract');
        $this->assertTrue($withContextMethod->isPublic(), 'withContext method should be public');
        $this->assertEquals('array', $withContextMethod->getReturnType()->getName());
    }

    public function test_get_context_data_returns_context_data(): void
    {
        $composer = new class () extends Composer {
            public array $views = ['test.twig'];

            public function withContext(): array
            {
                return ['test' => 'data'];
            }
        };

        // Initially should be empty
        $this->assertEquals([], $composer->getContextData());

        // After setting data
        $composer->setContextData(['existing' => 'data', 'user' => 'John']);
        $this->assertEquals(['existing' => 'data', 'user' => 'John'], $composer->getContextData());
    }

    public function test_set_context_data_updates_context_data(): void
    {
        $composer = new class () extends Composer {
            public array $views = ['test.twig'];

            public function withContext(): array
            {
                return ['test' => 'data'];
            }
        };

        $testData = [
            'posts' => ['post1', 'post2'],
            'user' => ['name' => 'Jane', 'role' => 'admin'],
            'settings' => ['theme' => 'dark'],
        ];

        $composer->setContextData($testData);

        $this->assertEquals($testData, $composer->getContextData());
    }

    public function test_with_context_can_access_context_data(): void
    {
        $composer = new class () extends Composer {
            public array $views = ['user-profile.twig'];

            public function withContext(): array
            {
                $contextData = $this->getContextData();

                return [
                    'greeting' => 'Hello ' . ($contextData['user']['name'] ?? 'Guest'),
                    'is_admin' => ($contextData['user']['role'] ?? '') === 'admin',
                    'post_count' => count($contextData['posts'] ?? []),
                ];
            }
        };

        // Set some context data
        $composer->setContextData([
            'user' => ['name' => 'Alice', 'role' => 'admin'],
            'posts' => ['post1', 'post2', 'post3'],
        ]);

        $result = $composer->withContext();

        $this->assertEquals('Hello Alice', $result['greeting']);
        $this->assertTrue($result['is_admin']);
        $this->assertEquals(3, $result['post_count']);
    }

    public function test_with_context_handles_empty_context_data(): void
    {
        $composer = new class () extends Composer {
            public array $views = ['test.twig'];

            public function withContext(): array
            {
                $contextData = $this->getContextData();

                return [
                    'has_data' => ! empty($contextData),
                    'data_count' => count($contextData),
                ];
            }
        };

        // Don't set any context data (should be empty by default)
        $result = $composer->withContext();

        $this->assertFalse($result['has_data']);
        $this->assertEquals(0, $result['data_count']);
    }

    public function test_views_property_can_contain_multiple_templates(): void
    {
        $composer = new class () extends Composer {
            public array $views = [
                'layouts/header.twig',
                'layouts/footer.twig',
                'partials/navigation.twig',
                'pages/home.twig',
            ];

            public function withContext(): array
            {
                return ['navigation_items' => ['Home', 'About', 'Contact']];
            }
        };

        $this->assertCount(4, $composer->views);
        $this->assertContains('layouts/header.twig', $composer->views);
        $this->assertContains('partials/navigation.twig', $composer->views);
    }

    public function test_views_property_can_be_empty(): void
    {
        $composer = new class () extends Composer {
            public array $views = [];

            public function withContext(): array
            {
                return ['global' => 'data'];
            }
        };

        $this->assertEmpty($composer->views);
        $this->assertIsArray($composer->views);
    }

    public function test_context_data_property_is_protected(): void
    {
        $reflection = new \ReflectionClass(Composer::class);
        $contextDataProperty = $reflection->getProperty('contextData');

        $this->assertTrue($contextDataProperty->isProtected(), 'contextData property should be protected');
        $this->assertEquals('array', $contextDataProperty->getType()->getName());
    }

    public function test_views_property_is_public(): void
    {
        $reflection = new \ReflectionClass(Composer::class);
        $viewsProperty = $reflection->getProperty('views');

        $this->assertTrue($viewsProperty->isPublic(), 'views property should be public');
        $this->assertEquals('array', $viewsProperty->getType()->getName());
    }

    public function test_composer_method_signatures(): void
    {
        $methodSignatures = [
            'getContextData' => ['parameters' => 0, 'return' => 'array'],
            'setContextData' => ['parameters' => 1, 'return' => 'void'],
            'withContext' => ['parameters' => 0, 'return' => 'array'],
        ];

        foreach ($methodSignatures as $method => $expected) {
            $reflection = new \ReflectionMethod(Composer::class, $method);

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
