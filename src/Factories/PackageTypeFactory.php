<?php

declare(strict_types=1);

namespace PhpHive\Cli\Factories;

use InvalidArgumentException;
use PhpHive\Cli\Contracts\PackageTypeInterface;
use PhpHive\Cli\Enums\PackageType;
use PhpHive\Cli\Support\Composer;

/**
 * Package Type Factory.
 *
 * Creates package type instances based on type identifier.
 * Centralizes package type instantiation and validation.
 *
 * Example usage:
 * ```php
 * $factory = new PackageTypeFactory($composer);
 * $packageType = $factory->create('laravel');
 * ```
 */
final readonly class PackageTypeFactory
{
    /**
     * Create a new package type factory.
     *
     * @param Composer $composer Composer service for dependency management
     */
    public function __construct(
        private Composer $composer
    ) {}

    /**
     * Create a package type instance.
     *
     * @param  string               $type Package type identifier
     * @return PackageTypeInterface Package type instance
     *
     * @throws InvalidArgumentException If package type is invalid
     */
    public function create(string $type): PackageTypeInterface
    {
        if (! $this->isValidType($type)) {
            throw new InvalidArgumentException(
                "Invalid package type '{$type}'. Valid types: " . implode(', ', PackageType::values())
            );
        }

        // Get the enum case and its class name
        $packageType = PackageType::from($type);

        /** @var class-string<PackageTypeInterface> $className */
        $className = $packageType->getClassName();

        // Instantiate and return the package type
        // All PackageType implementations extend AbstractPackageType which accepts Composer in constructor
        // @phpstan-ignore-next-line - Static analyzer doesn't understand we're instantiating concrete classes, not the interface
        return new $className($this->composer);
    }

    /**
     * Check if a package type is valid.
     *
     * @param  string $type Package type identifier
     * @return bool   True if valid, false otherwise
     */
    public function isValidType(string $type): bool
    {
        return PackageType::tryFrom($type) !== null;
    }

    /**
     * Get all valid package types.
     *
     * @return array<int, string> List of valid package types
     */
    public function getValidTypes(): array
    {
        return PackageType::values();
    }

    /**
     * Get package type options for CLI prompts.
     *
     * @return array<string, string> Map of type => description
     */
    public function getTypeOptions(): array
    {
        $options = [];

        foreach (PackageType::cases() as $case) {
            $options[$case->value] = $case->getDescription();
        }

        return $options;
    }
}
