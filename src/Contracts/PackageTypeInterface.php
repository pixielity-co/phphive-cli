<?php

declare(strict_types=1);

namespace PhpHive\Cli\Contracts;

/**
 * Package Type Interface.
 *
 * Defines the contract for package type implementations. Each package type
 * (Laravel, Magento, Symfony, Skeleton) implements this interface to provide
 * type-specific behavior for package creation and configuration.
 *
 * Package types handle:
 * - Stub path resolution
 * - Variable preparation for template processing
 * - Post-creation hooks (e.g., composer install)
 * - Type-specific file naming (e.g., ServiceProvider, Bundle)
 */
interface PackageTypeInterface
{
    /**
     * Get the package type identifier.
     *
     * @return string Package type (e.g., 'laravel', 'magento', 'symfony', 'skeleton')
     */
    public function getType(): string;

    /**
     * Get the display name for the package type.
     *
     * @return string Human-readable name (e.g., 'Laravel Package', 'Magento Module')
     */
    public function getDisplayName(): string;

    /**
     * Get the description for the package type.
     *
     * @return string Description shown in CLI prompts
     */
    public function getDescription(): string;

    /**
     * Get the stub directory path for this package type.
     *
     * @param  string $stubsBasePath Base path to stubs directory
     * @return string Full path to package type stubs
     */
    public function getStubPath(string $stubsBasePath): string;

    /**
     * Prepare variables for stub template processing.
     *
     * @param  string                $name        Package name
     * @param  string                $description Package description
     * @return array<string, string> Variables for template replacement
     */
    public function prepareVariables(string $name, string $description): array;

    /**
     * Get special file naming rules for this package type.
     *
     * Returns an array of file path patterns and their replacement rules.
     * Used to rename files based on package namespace (e.g., ServiceProvider.php -> TestLaravelServiceProvider.php)
     *
     * @return array<string, string> Map of file patterns to replacement patterns
     */
    public function getFileNamingRules(): array;

    /**
     * Perform post-creation tasks.
     *
     * Called after package files are created. Can be used for:
     * - Running composer install
     * - Generating additional files
     * - Setting up configuration
     *
     * @param string $packagePath Full path to created package
     */
    public function postCreate(string $packagePath): void;
}
