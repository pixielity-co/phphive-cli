<?php

declare(strict_types=1);

namespace PhpHive\Cli\Support;

use RuntimeException;

/**
 * Composer Operations.
 *
 * Provides a clean abstraction for Composer operations with common patterns
 * for dependency management. This class wraps Composer commands and provides
 * convenient methods for package installation, updates, and queries.
 *
 * All methods throw exceptions with descriptive messages on failure rather
 * than returning false, making error handling more explicit.
 *
 * Example usage:
 * ```php
 * $composer = Composer::make();
 * $composer->install('/path/to/project');
 * $packages = $composer->getInstalledPackages('/path/to/project');
 * ```
 */
final readonly class Composer
{
    /**
     * Create a new Composer instance.
     *
     * @param Process $process Process service for command execution
     */
    public function __construct(private Process $process) {}

    /**
     * Create a new Composer instance (static factory).
     */
    public static function make(): self
    {
        return new self(Process::make());
    }

    /**
     * Check if Composer is installed and available.
     *
     * @return bool True if Composer is available, false otherwise
     */
    public function isInstalled(): bool
    {
        return $this->process->commandExists('composer');
    }

    /**
     * Install Composer dependencies in a directory.
     *
     * Runs 'composer install' with common options for CI/production environments.
     *
     * @param  string             $directory    Directory containing composer.json
     * @param  bool               $dev          Install dev dependencies (default: true)
     * @param  bool               $optimize     Optimize autoloader (default: false)
     * @param  array<int, string> $extraOptions Additional Composer options
     * @return string             Command output
     *
     * @throws RuntimeException If Composer is not installed or command fails
     */
    public function install(
        string $directory,
        bool $dev = true,
        bool $optimize = false,
        array $extraOptions = []
    ): string {
        $this->ensureComposerInstalled();

        $command = ['composer', 'install', '--no-interaction'];

        if (! $dev) {
            $command[] = '--no-dev';
        }

        if ($optimize) {
            $command[] = '--optimize-autoloader';
        }

        $command = [...$command, ...$extraOptions];

        return $this->process->run($command, $directory);
    }

    /**
     * Update Composer dependencies in a directory.
     *
     * Runs 'composer update' to update packages to their latest versions
     * within the constraints defined in composer.json.
     *
     * @param  string             $directory    Directory containing composer.json
     * @param  array<int, string> $packages     Specific packages to update (empty = all)
     * @param  bool               $dev          Update dev dependencies (default: true)
     * @param  array<int, string> $extraOptions Additional Composer options
     * @return string             Command output
     *
     * @throws RuntimeException If Composer is not installed or command fails
     */
    public function update(
        string $directory,
        array $packages = [],
        bool $dev = true,
        array $extraOptions = []
    ): string {
        $this->ensureComposerInstalled();

        $command = ['composer', 'update', '--no-interaction'];

        if (! $dev) {
            $command[] = '--no-dev';
        }

        $command = [...$command, ...$packages, ...$extraOptions];

        return $this->process->run($command, $directory);
    }

    /**
     * Require a new package.
     *
     * Adds a package to composer.json and installs it.
     *
     * @param  string             $directory    Directory containing composer.json
     * @param  string             $package      Package name (e.g., 'vendor/package')
     * @param  string|null        $version      Version constraint (null = latest)
     * @param  bool               $dev          Add as dev dependency (default: false)
     * @param  array<int, string> $extraOptions Additional Composer options
     * @return string             Command output
     *
     * @throws RuntimeException If Composer is not installed or command fails
     */
    public function require(
        string $directory,
        string $package,
        ?string $version = null,
        bool $dev = false,
        array $extraOptions = []
    ): string {
        $this->ensureComposerInstalled();

        $packageWithVersion = $version !== null ? "{$package}:{$version}" : $package;

        $command = ['composer', 'require', $packageWithVersion, '--no-interaction'];

        if ($dev) {
            $command[] = '--dev';
        }

        $command = [...$command, ...$extraOptions];

        return $this->process->run($command, $directory);
    }

    /**
     * Remove a package.
     *
     * Removes a package from composer.json and uninstalls it.
     *
     * @param  string             $directory    Directory containing composer.json
     * @param  string             $package      Package name (e.g., 'vendor/package')
     * @param  bool               $dev          Remove from dev dependencies (default: false)
     * @param  array<int, string> $extraOptions Additional Composer options
     * @return string             Command output
     *
     * @throws RuntimeException If Composer is not installed or command fails
     */
    public function remove(
        string $directory,
        string $package,
        bool $dev = false,
        array $extraOptions = []
    ): string {
        $this->ensureComposerInstalled();

        $command = ['composer', 'remove', $package, '--no-interaction'];

        if ($dev) {
            $command[] = '--dev';
        }

        $command = [...$command, ...$extraOptions];

        return $this->process->run($command, $directory);
    }

    /**
     * Dump the autoloader.
     *
     * Regenerates the Composer autoloader files.
     *
     * @param  string $directory Directory containing composer.json
     * @param  bool   $optimize  Optimize autoloader (default: false)
     * @return string Command output
     *
     * @throws RuntimeException If Composer is not installed or command fails
     */
    public function dumpAutoload(string $directory, bool $optimize = false): string
    {
        $this->ensureComposerInstalled();

        $command = ['composer', 'dump-autoload', '--no-interaction'];

        if ($optimize) {
            $command[] = '--optimize';
        }

        return $this->process->run($command, $directory);
    }

    /**
     * Validate composer.json and composer.lock.
     *
     * Checks if composer.json is valid and if composer.lock is up to date.
     *
     * @param  string $directory Directory containing composer.json
     * @return bool   True if valid, false otherwise
     */
    public function validate(string $directory): bool
    {
        $this->ensureComposerInstalled();

        return $this->process->succeeds(
            ['composer', 'validate', '--no-check-all', '--no-check-publish'],
            $directory
        );
    }

    /**
     * Get Composer version.
     *
     * @return string Composer version string
     *
     * @throws RuntimeException If Composer is not installed
     */
    public function getVersion(): string
    {
        $this->ensureComposerInstalled();

        $output = $this->process->run(['composer', '--version', '--no-ansi']);

        // Extract version from output (e.g., "Composer version 2.6.5")
        if (preg_match('/Composer version (\S+)/', $output, $matches) !== false && isset($matches[1])) {
            return $matches[1];
        }

        return trim($output);
    }

    /**
     * Run a custom Composer command.
     *
     * Executes an arbitrary Composer command with the given arguments.
     *
     * @param  string             $directory Directory to run command in
     * @param  array<int, string> $arguments Command arguments (e.g., ['show', '--installed'])
     * @return string             Command output
     *
     * @throws RuntimeException If Composer is not installed or command fails
     */
    public function run(string $directory, array $arguments): string
    {
        $this->ensureComposerInstalled();

        $command = ['composer', ...$arguments];

        return $this->process->run($command, $directory);
    }

    /**
     * Ensure Composer is installed.
     *
     * @throws RuntimeException If Composer is not installed
     */
    private function ensureComposerInstalled(): void
    {
        if (! $this->isInstalled()) {
            throw new RuntimeException(
                'Composer is not installed. Please install Composer from https://getcomposer.org/'
            );
        }
    }
}
