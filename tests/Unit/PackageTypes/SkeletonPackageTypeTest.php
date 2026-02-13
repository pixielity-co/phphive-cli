<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\PackageTypes;

use PhpHive\Cli\PackageTypes\SkeletonPackageType;
use PhpHive\Cli\Support\Composer;
use PhpHive\Cli\Tests\TestCase;

/**
 * Unit tests for SkeletonPackageType class.
 *
 * Tests Skeleton-specific package type functionality:
 * - Type identification
 * - Display name and description
 * - No special file naming rules
 */
class SkeletonPackageTypeTest extends TestCase
{
    private SkeletonPackageType $packageType;

    protected function setUp(): void
    {
        parent::setUp();

        $composer = Composer::make();
        $this->packageType = new SkeletonPackageType($composer);
    }

    /**
     * Test getType returns correct identifier.
     */
    public function test_get_type_returns_correct_identifier(): void
    {
        $this->assertSame('skeleton', $this->packageType->getType());
    }

    /**
     * Test getDisplayName returns Skeleton.
     */
    public function test_get_display_name_returns_skeleton(): void
    {
        $this->assertSame('Skeleton', $this->packageType->getDisplayName());
    }

    /**
     * Test getDescription returns meaningful description.
     */
    public function test_get_description_returns_meaningful_description(): void
    {
        $description = $this->packageType->getDescription();

        $this->assertIsString($description);
        $this->assertStringContainsString('Skeleton', $description);
        $this->assertStringContainsString('Minimal', $description);
    }

    /**
     * Test getFileNamingRules returns empty array.
     */
    public function test_get_file_naming_rules_returns_empty_array(): void
    {
        $rules = $this->packageType->getFileNamingRules();

        $this->assertSame([], $rules);
    }

    /**
     * Test prepareVariables includes all required variables.
     */
    public function test_prepare_variables_includes_all_required_variables(): void
    {
        $variables = $this->packageType->prepareVariables('test-library', 'Test library');

        $this->assertArrayHasKey('package_name', $variables);
        $this->assertArrayHasKey('package_namespace', $variables);
        $this->assertArrayHasKey('composer_package_name', $variables);
        $this->assertArrayHasKey('description', $variables);
        $this->assertArrayHasKey('namespace', $variables);
    }

    /**
     * Test prepareVariables converts package name correctly.
     */
    public function test_prepare_variables_converts_package_name_correctly(): void
    {
        $variables = $this->packageType->prepareVariables('string-helpers', 'String helper utilities');

        $this->assertSame('StringHelpers', $variables['package_namespace']);
        $this->assertSame('PhpHive\\StringHelpers', $variables['namespace']);
        $this->assertSame('phphive/string-helpers', $variables['composer_package_name']);
    }

    /**
     * Test getStubPath returns Skeleton package stub path.
     */
    public function test_get_stub_path_returns_skeleton_package_stub_path(): void
    {
        $basePath = '/path/to/stubs';

        $stubPath = $this->packageType->getStubPath($basePath);

        $this->assertSame('/path/to/stubs/packages/skeleton', $stubPath);
    }
}
