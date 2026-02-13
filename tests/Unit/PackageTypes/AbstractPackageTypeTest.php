<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\PackageTypes;

use PhpHive\Cli\PackageTypes\AbstractPackageType;
use PhpHive\Cli\Support\Composer;
use PhpHive\Cli\Tests\TestCase;

/**
 * Unit tests for AbstractPackageType class.
 *
 * Tests base package type functionality:
 * - Stub path generation
 * - Variable preparation
 * - Namespace conversion
 * - Composer package name generation
 */
class AbstractPackageTypeTest extends TestCase
{
    private AbstractPackageType $packageType;

    protected function setUp(): void
    {
        parent::setUp();

        $composer = Composer::make();

        // Create concrete implementation for testing
        $this->packageType = new class($composer) extends AbstractPackageType
        {
            public function getType(): string
            {
                return 'test';
            }

            public function getDisplayName(): string
            {
                return 'Test';
            }

            public function getDescription(): string
            {
                return 'Test package type';
            }

            // Expose protected methods for testing
            public function publicConvertToNamespace(string $name): string
            {
                return $this->convertToNamespace($name);
            }

            public function publicGenerateComposerPackageName(string $name): string
            {
                return $this->generateComposerPackageName($name);
            }
        };
    }

    /**
     * Test getStubPath returns correct path.
     */
    public function test_get_stub_path_returns_correct_path(): void
    {
        $basePath = '/path/to/stubs';

        $stubPath = $this->packageType->getStubPath($basePath);

        $this->assertSame('/path/to/stubs/packages/test', $stubPath);
    }

    /**
     * Test prepareVariables returns required variables.
     */
    public function test_prepare_variables_returns_required_variables(): void
    {
        $variables = $this->packageType->prepareVariables('test-package', 'Test description');

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
        $variables = $this->packageType->prepareVariables('test-package', 'Test description');

        $this->assertSame('test-package', $variables['package_name']);
        $this->assertSame('TestPackage', $variables['package_namespace']);
        $this->assertSame('phphive/test-package', $variables['composer_package_name']);
    }

    /**
     * Test prepareVariables includes description.
     */
    public function test_prepare_variables_includes_description(): void
    {
        $variables = $this->packageType->prepareVariables('test-package', 'Custom description');

        $this->assertSame('Custom description', $variables['description']);
    }

    /**
     * Test prepareVariables includes author information.
     */
    public function test_prepare_variables_includes_author_information(): void
    {
        $variables = $this->packageType->prepareVariables('test-package', 'Test description');

        $this->assertArrayHasKey('author_name', $variables);
        $this->assertArrayHasKey('author_email', $variables);
        $this->assertSame('PhpHive Team', $variables['author_name']);
        $this->assertSame('team@phphive.com', $variables['author_email']);
    }

    /**
     * Test prepareVariables generates full namespace.
     */
    public function test_prepare_variables_generates_full_namespace(): void
    {
        $variables = $this->packageType->prepareVariables('test-package', 'Test description');

        $this->assertSame('PhpHive\\TestPackage', $variables['namespace']);
    }

    /**
     * Test convertToNamespace converts kebab-case to PascalCase.
     */
    public function test_convert_to_namespace_converts_kebab_case_to_pascal_case(): void
    {
        $namespace = $this->packageType->publicConvertToNamespace('test-package');

        $this->assertSame('TestPackage', $namespace);
    }

    /**
     * Test convertToNamespace converts snake_case to PascalCase.
     */
    public function test_convert_to_namespace_converts_snake_case_to_pascal_case(): void
    {
        $namespace = $this->packageType->publicConvertToNamespace('test_package');

        $this->assertSame('TestPackage', $namespace);
    }

    /**
     * Test convertToNamespace handles mixed separators.
     */
    public function test_convert_to_namespace_handles_mixed_separators(): void
    {
        $namespace = $this->packageType->publicConvertToNamespace('test-package_name');

        $this->assertSame('TestPackageName', $namespace);
    }

    /**
     * Test convertToNamespace handles single word.
     */
    public function test_convert_to_namespace_handles_single_word(): void
    {
        $namespace = $this->packageType->publicConvertToNamespace('package');

        $this->assertSame('Package', $namespace);
    }

    /**
     * Test generateComposerPackageName adds vendor prefix.
     */
    public function test_generate_composer_package_name_adds_vendor_prefix(): void
    {
        $packageName = $this->packageType->publicGenerateComposerPackageName('test-package');

        $this->assertSame('phphive/test-package', $packageName);
    }

    /**
     * Test generateComposerPackageName converts to lowercase.
     */
    public function test_generate_composer_package_name_converts_to_lowercase(): void
    {
        $packageName = $this->packageType->publicGenerateComposerPackageName('TestPackage');

        $this->assertSame('phphive/testpackage', $packageName);
    }

    /**
     * Test getFileNamingRules returns empty array by default.
     */
    public function test_get_file_naming_rules_returns_empty_array_by_default(): void
    {
        $rules = $this->packageType->getFileNamingRules();

        $this->assertSame([], $rules);
    }

    /**
     * Test prepareVariables with complex package name.
     */
    public function test_prepare_variables_with_complex_package_name(): void
    {
        $variables = $this->packageType->prepareVariables('my-awesome-api-client', 'API client');

        $this->assertSame('MyAwesomeApiClient', $variables['package_namespace']);
        $this->assertSame('phphive/my-awesome-api-client', $variables['composer_package_name']);
        $this->assertSame('PhpHive\\MyAwesomeApiClient', $variables['namespace']);
    }
}
