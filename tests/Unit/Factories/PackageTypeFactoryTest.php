<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Factories;

use InvalidArgumentException;
use PhpHive\Cli\Contracts\PackageTypeInterface;
use PhpHive\Cli\Factories\PackageTypeFactory;
use PhpHive\Cli\Support\Composer;
use PhpHive\Cli\Tests\TestCase;

/**
 * Unit tests for PackageTypeFactory class.
 *
 * Tests factory functionality for creating package type instances:
 * - Creating valid package types
 * - Validation of package type identifiers
 * - Retrieving valid types
 * - Getting type options for prompts
 */
class PackageTypeFactoryTest extends TestCase
{
    private PackageTypeFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $composer = Composer::make();
        $this->factory = new PackageTypeFactory($composer);
    }

    /**
     * Test factory can be instantiated.
     */
    public function test_can_instantiate_factory(): void
    {
        $this->assertInstanceOf(PackageTypeFactory::class, $this->factory);
    }

    /**
     * Test factory can create Laravel package type.
     */
    public function test_can_create_laravel_package_type(): void
    {
        $packageType = $this->factory->create('laravel');

        $this->assertInstanceOf(PackageTypeInterface::class, $packageType);
        $this->assertSame('Laravel', $packageType->getDisplayName());
    }

    /**
     * Test factory can create Magento package type.
     */
    public function test_can_create_magento_package_type(): void
    {
        $packageType = $this->factory->create('magento');

        $this->assertInstanceOf(PackageTypeInterface::class, $packageType);
        $this->assertSame('Magento', $packageType->getDisplayName());
    }

    /**
     * Test factory can create Symfony package type.
     */
    public function test_can_create_symfony_package_type(): void
    {
        $packageType = $this->factory->create('symfony');

        $this->assertInstanceOf(PackageTypeInterface::class, $packageType);
        $this->assertSame('Symfony', $packageType->getDisplayName());
    }

    /**
     * Test factory can create Skeleton package type.
     */
    public function test_can_create_skeleton_package_type(): void
    {
        $packageType = $this->factory->create('skeleton');

        $this->assertInstanceOf(PackageTypeInterface::class, $packageType);
        $this->assertSame('Skeleton', $packageType->getDisplayName());
    }

    /**
     * Test factory throws exception for invalid package type.
     */
    public function test_throws_exception_for_invalid_package_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid package type');

        $this->factory->create('invalid');
    }

    /**
     * Test isValidType returns true for valid types.
     */
    public function test_is_valid_type_returns_true_for_valid_types(): void
    {
        $this->assertTrue($this->factory->isValidType('laravel'));
        $this->assertTrue($this->factory->isValidType('magento'));
        $this->assertTrue($this->factory->isValidType('symfony'));
        $this->assertTrue($this->factory->isValidType('skeleton'));
    }

    /**
     * Test isValidType returns false for invalid types.
     */
    public function test_is_valid_type_returns_false_for_invalid_types(): void
    {
        $this->assertFalse($this->factory->isValidType('invalid'));
        $this->assertFalse($this->factory->isValidType(''));
        $this->assertFalse($this->factory->isValidType('unknown'));
    }

    /**
     * Test getValidTypes returns all valid package types.
     */
    public function test_get_valid_types_returns_all_types(): void
    {
        $types = $this->factory->getValidTypes();

        $this->assertIsArray($types);
        $this->assertContains('laravel', $types);
        $this->assertContains('magento', $types);
        $this->assertContains('symfony', $types);
        $this->assertContains('skeleton', $types);
    }

    /**
     * Test getTypeOptions returns formatted options.
     */
    public function test_get_type_options_returns_formatted_options(): void
    {
        $options = $this->factory->getTypeOptions();

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);

        // Each option should have a string key and description
        foreach ($options as $key => $description) {
            $this->assertIsString($key);
            $this->assertIsString($description);
            $this->assertNotEmpty($description);
        }
    }

    /**
     * Test getTypeOptions contains all valid types.
     */
    public function test_get_type_options_contains_all_valid_types(): void
    {
        $options = $this->factory->getTypeOptions();

        $this->assertArrayHasKey('laravel', $options);
        $this->assertArrayHasKey('magento', $options);
        $this->assertArrayHasKey('symfony', $options);
        $this->assertArrayHasKey('skeleton', $options);
    }

    /**
     * Test created package types have correct type identifiers.
     */
    public function test_created_package_types_have_correct_identifiers(): void
    {
        $types = ['laravel', 'magento', 'symfony', 'skeleton'];

        foreach ($types as $type) {
            $packageType = $this->factory->create($type);

            $this->assertSame($type, $packageType->getType());
        }
    }

    /**
     * Test created package types have descriptions.
     */
    public function test_created_package_types_have_descriptions(): void
    {
        $types = ['laravel', 'magento', 'symfony', 'skeleton'];

        foreach ($types as $type) {
            $packageType = $this->factory->create($type);
            $description = $packageType->getDescription();

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

    /**
     * Test exception message includes valid types.
     */
    public function test_exception_message_includes_valid_types(): void
    {
        try {
            $this->factory->create('invalid');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();

            $this->assertStringContainsString('laravel', $message);
            $this->assertStringContainsString('magento', $message);
            $this->assertStringContainsString('symfony', $message);
            $this->assertStringContainsString('skeleton', $message);
        }
    }
}
