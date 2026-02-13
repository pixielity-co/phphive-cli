<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\PackageTypes;

use PhpHive\Cli\PackageTypes\LaravelPackageType;
use PhpHive\Cli\Support\Composer;
use PhpHive\Cli\Tests\TestCase;

/**
 * Unit tests for LaravelPackageType class.
 *
 * Tests Laravel-specific package type functionality:
 * - Type identification
 * - Display name and description
 * - File naming rules for ServiceProvider
 */
class LaravelPackageTypeTest extends TestCase
{
    private LaravelPackageType $packageType;

    protected function setUp(): void
    {
        parent::setUp();

        $composer = Composer::make();
        $this->packageType = new LaravelPackageType($composer);
    }

    /**
     * Test getType returns correct identifier.
     */
    public function test_get_type_returns_correct_identifier(): void
    {
        $this->assertSame('laravel', $this->packageType->getType());
    }

    /**
     * Test getDisplayName returns Laravel.
     */
    public function test_get_display_name_returns_laravel(): void
    {
        $this->assertSame('Laravel', $this->packageType->getDisplayName());
    }

    /**
     * Test getDescription returns meaningful description.
     */
    public function test_get_description_returns_meaningful_description(): void
    {
        $description = $this->packageType->getDescription();

        $this->assertIsString($description);
        $this->assertStringContainsString('Laravel', $description);
        $this->assertStringContainsString('Service Provider', $description);
    }

    /**
     * Test getFileNamingRules includes ServiceProvider rule.
     */
    public function test_get_file_naming_rules_includes_service_provider_rule(): void
    {
        $rules = $this->packageType->getFileNamingRules();

        $this->assertArrayHasKey('/src/Providers/ServiceProvider.php', $rules);
    }

    /**
     * Test ServiceProvider naming rule uses package namespace placeholder.
     */
    public function test_service_provider_naming_rule_uses_package_namespace_placeholder(): void
    {
        $rules = $this->packageType->getFileNamingRules();

        $expectedPath = '/src/Providers/{{PACKAGE_NAMESPACE}}ServiceProvider.php';
        $this->assertSame($expectedPath, $rules['/src/Providers/ServiceProvider.php']);
    }

    /**
     * Test prepareVariables includes all required variables.
     */
    public function test_prepare_variables_includes_all_required_variables(): void
    {
        $variables = $this->packageType->prepareVariables('test-laravel', 'Test Laravel package');

        $this->assertArrayHasKey('package_name', $variables);
        $this->assertArrayHasKey('package_namespace', $variables);
        $this->assertArrayHasKey('composer_package_name', $variables);
        $this->assertArrayHasKey('description', $variables);
        $this->assertArrayHasKey('namespace', $variables);
    }

    /**
     * Test prepareVariables converts package name to namespace correctly.
     */
    public function test_prepare_variables_converts_package_name_to_namespace_correctly(): void
    {
        $variables = $this->packageType->prepareVariables('user-management', 'User management package');

        $this->assertSame('UserManagement', $variables['package_namespace']);
        $this->assertSame('PhpHive\\UserManagement', $variables['namespace']);
    }

    /**
     * Test getStubPath returns Laravel package stub path.
     */
    public function test_get_stub_path_returns_laravel_package_stub_path(): void
    {
        $basePath = '/path/to/stubs';

        $stubPath = $this->packageType->getStubPath($basePath);

        $this->assertSame('/path/to/stubs/packages/laravel', $stubPath);
    }
}
