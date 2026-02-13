<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Factories;

use InvalidArgumentException;
use PhpHive\Cli\Contracts\AppTypeInterface;
use PhpHive\Cli\Factories\AppTypeFactory;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;

/**
 * Unit tests for AppTypeFactory class.
 *
 * Tests factory functionality for creating app type instances:
 * - Creating valid app types
 * - Validation of app type identifiers
 * - Retrieving available types
 * - Getting choices for prompts
 */
class AppTypeFactoryTest extends TestCase
{
    private AppTypeFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();
        $this->factory = new AppTypeFactory($container);
    }

    /**
     * Test factory can be instantiated.
     */
    public function test_can_instantiate_factory(): void
    {
        $this->assertInstanceOf(AppTypeFactory::class, $this->factory);
    }

    /**
     * Test factory returns available types.
     */
    public function test_get_available_types_returns_array(): void
    {
        $types = $this->factory->getAvailableTypes();

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);
    }

    /**
     * Test available types contain expected app types.
     */
    public function test_available_types_contain_expected_types(): void
    {
        $types = $this->factory->getAvailableTypes();

        $this->assertArrayHasKey('laravel', $types);
        $this->assertArrayHasKey('symfony', $types);
        $this->assertArrayHasKey('magento', $types);
        $this->assertArrayHasKey('skeleton', $types);
    }

    /**
     * Test factory can create Laravel app type.
     */
    public function test_can_create_laravel_app_type(): void
    {
        $appType = $this->factory->create('laravel');

        $this->assertInstanceOf(AppTypeInterface::class, $appType);
        $this->assertSame('Laravel', $appType->getName());
    }

    /**
     * Test factory can create Symfony app type.
     */
    public function test_can_create_symfony_app_type(): void
    {
        $appType = $this->factory->create('symfony');

        $this->assertInstanceOf(AppTypeInterface::class, $appType);
        $this->assertSame('Symfony', $appType->getName());
    }

    /**
     * Test factory can create Magento app type.
     */
    public function test_can_create_magento_app_type(): void
    {
        $appType = $this->factory->create('magento');

        $this->assertInstanceOf(AppTypeInterface::class, $appType);
        $this->assertSame('Magento', $appType->getName());
    }

    /**
     * Test factory can create Skeleton app type.
     */
    public function test_can_create_skeleton_app_type(): void
    {
        $appType = $this->factory->create('skeleton');

        $this->assertInstanceOf(AppTypeInterface::class, $appType);
        $this->assertSame('Skeleton', $appType->getName());
    }

    /**
     * Test factory throws exception for invalid app type.
     */
    public function test_throws_exception_for_invalid_app_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown app type: invalid');

        $this->factory->create('invalid');
    }

    /**
     * Test isValid returns true for valid types.
     */
    public function test_is_valid_returns_true_for_valid_types(): void
    {
        $this->assertTrue($this->factory->isValid('laravel'));
        $this->assertTrue($this->factory->isValid('symfony'));
        $this->assertTrue($this->factory->isValid('magento'));
        $this->assertTrue($this->factory->isValid('skeleton'));
    }

    /**
     * Test isValid returns false for invalid types.
     */
    public function test_is_valid_returns_false_for_invalid_types(): void
    {
        $this->assertFalse($this->factory->isValid('invalid'));
        $this->assertFalse($this->factory->isValid(''));
        $this->assertFalse($this->factory->isValid('unknown'));
    }

    /**
     * Test getIdentifiers returns all app type identifiers.
     */
    public function test_get_identifiers_returns_all_identifiers(): void
    {
        $identifiers = $this->factory->getIdentifiers();

        $this->assertIsArray($identifiers);
        $this->assertContains('laravel', $identifiers);
        $this->assertContains('symfony', $identifiers);
        $this->assertContains('magento', $identifiers);
        $this->assertContains('skeleton', $identifiers);
    }

    /**
     * Test choices returns formatted array for prompts.
     */
    public function test_choices_returns_formatted_array(): void
    {
        $choices = AppTypeFactory::choices();

        $this->assertIsArray($choices);
        $this->assertNotEmpty($choices);

        // Each choice should have a string key and value
        foreach ($choices as $key => $value) {
            $this->assertIsString($key);
            $this->assertIsString($value);
        }
    }

    /**
     * Test choices contain descriptions.
     */
    public function test_choices_contain_descriptions(): void
    {
        $choices = AppTypeFactory::choices();

        // Choices should be in format "Name (Description)" => "identifier"
        foreach ($choices as $label => $identifier) {
            $this->assertStringContainsString('(', $label);
            $this->assertStringContainsString(')', $label);
        }
    }

    /**
     * Test created app types have correct descriptions.
     */
    public function test_created_app_types_have_descriptions(): void
    {
        $types = ['laravel', 'symfony', 'magento', 'skeleton'];

        foreach ($types as $type) {
            $appType = $this->factory->create($type);
            $description = $appType->getDescription();

            $this->assertIsString($description);
            $this->assertNotEmpty($description);
        }
    }

    /**
     * Test factory creates different instances for each call.
     */
    public function test_factory_creates_new_instances_each_time(): void
    {
        $instance1 = $this->factory->create('laravel');
        $instance2 = $this->factory->create('laravel');

        $this->assertNotSame($instance1, $instance2);
    }
}
