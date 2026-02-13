<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Support;

use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use stdClass;

/**
 * Unit tests for Container class.
 *
 * Tests dependency injection container functionality:
 * - Singleton pattern
 * - Service binding and resolution
 * - Singleton services
 * - Instance registration
 */
class ContainerTest extends TestCase
{
    /**
     * Test container can be instantiated.
     */
    public function test_can_instantiate_container(): void
    {
        $container = new Container();

        $this->assertInstanceOf(Container::class, $container);
    }

    /**
     * Test getInstance returns singleton instance.
     */
    public function test_get_instance_returns_singleton_instance(): void
    {
        $instance1 = Container::getInstance();
        $instance2 = Container::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test can bind and resolve service.
     */
    public function test_can_bind_and_resolve_service(): void
    {
        $container = new Container();

        $testClass = new class()
        {
            public string $value = 'test';
        };

        $container->bind('test.service', fn () => clone $testClass);

        $resolved = $container->make('test.service');

        $this->assertSame('test', $resolved->value);
    }

    /**
     * Test can register singleton.
     */
    public function test_can_register_singleton(): void
    {
        $container = new Container();

        $testClass = new class()
        {
            public string $value = 'test';
        };

        $container->singleton('test.singleton', fn () => $testClass);

        $resolved1 = $container->make('test.singleton');
        $resolved2 = $container->make('test.singleton');

        $this->assertSame($resolved1, $resolved2);
    }

    /**
     * Test can register instance.
     */
    public function test_can_register_instance(): void
    {
        $container = new Container();

        $instance = new class()
        {
            public string $value = 'test';
        };

        $container->instance('test.instance', $instance);

        $resolved = $container->make('test.instance');

        $this->assertSame($instance, $resolved);
    }

    /**
     * Test bound returns true for registered services.
     */
    public function test_bound_returns_true_for_registered_services(): void
    {
        $container = new Container();

        $container->bind('test.service', fn () => new stdClass());

        $this->assertTrue($container->bound('test.service'));
        $this->assertFalse($container->bound('nonexistent.service'));
    }

    /**
     * Test can resolve class with dependencies.
     */
    public function test_can_resolve_class_with_dependencies(): void
    {
        $container = new Container();

        $dependency = new class()
        {
            public string $value = 'dependency';
        };

        $container->instance('dependency', $dependency);

        $service = new class($dependency)
        {
            public function __construct(public object $dep) {}
        };

        $container->bind('service', fn ($c) => new $service($c->make('dependency')));

        $resolved = $container->make('service');

        $this->assertSame('dependency', $resolved->dep->value);
    }

    /**
     * Test make can accept parameters.
     */
    public function test_make_can_accept_parameters(): void
    {
        $container = new Container();

        $testClass = new class()
        {
            public function __construct(public string $param = 'default') {}
        };

        $container->bind('test.params', fn ($c, $params) => new $testClass($params['value'] ?? 'default'));

        $instance = $container->make('test.params', ['value' => 'custom']);

        $this->assertSame('custom', $instance->param);
    }

    /**
     * Test binding creates new instance each time.
     */
    public function test_binding_creates_new_instance_each_time(): void
    {
        $container = new Container();

        $testClass = new class()
        {
            public string $value = 'test';
        };

        $container->bind('test.binding', fn () => clone $testClass);

        $instance1 = $container->make('test.binding');
        $instance2 = $container->make('test.binding');

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test can resolve concrete class without binding.
     */
    public function test_can_resolve_concrete_class_without_binding(): void
    {
        $container = new Container();

        $instance = $container->make(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $instance);
    }

    /**
     * Test resolved returns true after service is resolved.
     */
    public function test_resolved_returns_true_after_service_is_resolved(): void
    {
        $container = new Container();

        $container->singleton('test.service', fn () => new stdClass());

        $this->assertFalse($container->resolved('test.service'));

        $container->make('test.service');

        $this->assertTrue($container->resolved('test.service'));
    }
}
