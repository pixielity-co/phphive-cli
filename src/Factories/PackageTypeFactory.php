<?php

declare(strict_types=1);

namespace PhpHive\Cli\Factories;

use InvalidArgumentException;
use PhpHive\Cli\Contracts\PackageTypeInterface;
use PhpHive\Cli\PackageTypes\LaravelPackageType;
use PhpHive\Cli\PackageTypes\MagentoPackageType;
use PhpHive\Cli\PackageTypes\SkeletonPackageType;
use PhpHive\Cli\PackageTypes\SymfonyPackageType;
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
     * Valid package types.
     */
    private const array VALID_TYPES = ['laravel', 'magento', 'symfony', 'skeleton'];

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
                "Invalid package type '{$type}'. Valid types: " . implode(', ', self::VALID_TYPES)
            );
        }

        return match ($type) {
            'laravel' => new LaravelPackageType($this->composer),
            'magento' => new MagentoPackageType($this->composer),
            'symfony' => new SymfonyPackageType($this->composer),
            'skeleton' => new SkeletonPackageType($this->composer),
            default => throw new InvalidArgumentException("Unsupported package type: {$type}"),
        };
    }

    /**
     * Check if a package type is valid.
     *
     * @param  string $type Package type identifier
     * @return bool   True if valid, false otherwise
     */
    public function isValidType(string $type): bool
    {
        return in_array($type, self::VALID_TYPES, true);
    }

    /**
     * Get all valid package types.
     *
     * @return array<int, string> List of valid package types
     */
    public function getValidTypes(): array
    {
        return self::VALID_TYPES;
    }

    /**
     * Get package type options for CLI prompts.
     *
     * @return array<string, string> Map of type => description
     */
    public function getTypeOptions(): array
    {
        return [
            'laravel' => new LaravelPackageType($this->composer)->getDescription(),
            'magento' => new MagentoPackageType($this->composer)->getDescription(),
            'symfony' => new SymfonyPackageType($this->composer)->getDescription(),
            'skeleton' => new SkeletonPackageType($this->composer)->getDescription(),
        ];
    }
}
