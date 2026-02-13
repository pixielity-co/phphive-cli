<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Support;

use PhpHive\Cli\Application;
use PhpHive\Cli\Support\App;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Tests\TestCase;
use ReflectionClass;

/**
 * Unit tests for App facade.
 *
 * Tests the application facade functionality:
 * - Getting application instance
 * - Accessing container
 * - Resolving services
 * - Checking bindings
 * - Registering services
 */
class AppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the application instance for each test
        $app = Application::make();
        App::setInstance($app);
    }

    /**
     * Test can get application instance.
     */
    public function test_can_get_application_instance(): void
    {
        $app = App::getInstance();

        $this->assertInstanceOf(Application::class, $app);
    }

    /**
     * Test can set application instance.
     */
    public function test_can_set_application_instance(): void
    {
        $app = new Application();
        App::setInstance($app);

        $retrieved = App::getInstance();

        $this->assertSame($app, $retrieved);
    }

    /**
     * Test can get container.
     */
    public function test_can_get_container(): void
    {
        $container = App::container();

        $this->assertInstanceOf(Container::class, $container);
    }

    /**
     * Test can make service from container.
     */
    public function test_can_make_service_from_container(): void
    {
        $filesystem = App::make(Filesystem::class);

        $this->assertInstanceOf(Filesystem::class, $filesystem);
    }

    /**
     * Test can check if service is bound.
     */
    public function test_can_check_if_service_is_bound(): void
    {
        // Filesystem should be bound by default
        $this->assertTrue(App::bound(Filesystem::class));

        // Random class should not be bound
        $this->assertFalse(App::bound('NonExistentClass'));
    }

    /**
     * Test can register singleton.
     */
    public function test_can_register_singleton(): void
    {
        $testClass = new class()
        {
            public string $value = 'test';
        };

        App::singleton('test.service', fn () => $testClass);

        $this->assertTrue(App::bound('test.service'));

        $resolved1 = App::make('test.service');
        $resolved2 = App::make('test.service');

        $this->assertSame($resolved1, $resolved2);
    }

    /**
     * Test can register binding.
     */
    public function test_can_register_binding(): void
    {
        $testClass = new class()
        {
            public string $value = 'test';
        };

        App::bind('test.binding', fn () => clone $testClass);

        $this->assertTrue(App::bound('test.binding'));

        $resolved1 = App::make('test.binding');
        $resolved2 = App::make('test.binding');

        $this->assertNotSame($resolved1, $resolved2);
    }

    /**
     * Test can register instance.
     */
    public function test_can_register_instance(): void
    {
        $instance = new class()
        {
            public string $value = 'test';
        };

        App::instance('test.instance', $instance);

        $this->assertTrue(App::bound('test.instance'));

        $resolved = App::make('test.instance');

        $this->assertSame($instance, $resolved);
    }

    /**
     * Test can get application version.
     */
    public function test_can_get_application_version(): void
    {
        $version = App::version();

        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
    }

    /**
     * Test can get application name.
     */
    public function test_can_get_application_name(): void
    {
        $name = App::name();

        $this->assertIsString($name);
        $this->assertSame('PhpHive CLI', $name);
    }

    /**
     * Test getInstance creates application if not set.
     */
    public function test_get_instance_creates_application_if_not_set(): void
    {
        // Create a new App facade without setting instance
        $reflection = new ReflectionClass(App::class);
        $property = $reflection->getProperty('application');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $app = App::getInstance();

        $this->assertInstanceOf(Application::class, $app);
    }

    /**
     * Test make can accept parameters.
     */
    public function test_make_can_accept_parameters(): void
    {
        $testClass = new class()
        {
            public function __construct(public string $param = 'default') {}
        };

        App::bind('test.params', fn ($container, $params) => new $testClass($params['value'] ?? 'default'));

        $instance = App::make('test.params', ['value' => 'custom']);

        $this->assertSame('custom', $instance->param);
    }

    /**
     * Test dynamic method calls are forwarded to container.
     */
    public function test_dynamic_method_calls_forwarded_to_container(): void
    {
        // Test a container method that's not explicitly defined in App facade
        $testClass = new class()
        {
            public string $value = 'test';
        };

        App::instance('test.dynamic', $testClass);

        // Use a container method via __callStatic
        $resolved = App::resolved('test.dynamic');

        $this->assertTrue($resolved);
    }
}
