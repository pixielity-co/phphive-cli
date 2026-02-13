<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Skeleton\Concerns;

use PhpHive\Cli\Contracts\AppTypeInterface;
use PhpHive\Cli\Enums\PhpVersion;

/**
 * Collects basic Skeleton application configuration.
 *
 * This trait handles the collection of fundamental configuration for Skeleton
 * applications: name, description, and minimum PHP version.
 *
 * Configuration collected:
 * - Application name: Used for directory naming, package naming, and namespace
 * - Description: Used in composer.json and documentation
 * - PHP version: Minimum required PHP version for the application
 *
 * PHP version selection is important because it determines:
 * - Which PHP features and syntax can be used
 * - Compatibility with dependencies
 * - Deployment environment requirements
 *
 * Supported PHP versions:
 * - 8.5: Latest (if available)
 * - 8.4: Current stable
 * - 8.3: Widely supported (default)
 * - 8.2: Older stable
 *
 * All values are collected via interactive prompts (no command-line options).
 */
trait CollectsBasicConfiguration
{
    /**
     * Collect basic Skeleton application configuration.
     *
     * Prompts the user for application name, description, and minimum PHP version.
     * All values are required for proper application setup.
     *
     * The collected values are used for:
     * - Name: Directory naming, composer package name, PSR-4 namespace
     * - Description: composer.json description field, README content
     * - PHP version: composer.json require.php constraint (^X.Y format)
     *
     * @return array<string, mixed> Configuration array with CONFIG_NAME, CONFIG_DESCRIPTION, and CONFIG_PHP_VERSION keys
     */
    protected function collectBasicConfig(): array
    {
        // Get name from input argument if available (don't prompt if already provided)
        $name = $this->input->getArgument('name');
        if ($name === null || trim($name) === '') {
            $name = $this->text(
                label: 'Application name',
                placeholder: 'my-app',
                required: true
            );
        }

        // Get description from input option if available
        $description = $this->input->getOption('description');
        if ($description === null || trim($description) === '') {
            $description = $this->text(
                label: 'Application description',
                placeholder: 'A minimal PHP application',
                required: false
            );
        }

        return [
            AppTypeInterface::CONFIG_NAME => $name,
            AppTypeInterface::CONFIG_DESCRIPTION => $description,

            // Minimum PHP version - determines language features available
            // Used in composer.json as "require": {"php": "^X.Y"}
            // Defaults to 8.3 as it's widely supported and stable
            AppTypeInterface::CONFIG_PHP_VERSION => $this->select(
                label: 'Minimum PHP version',
                options: PhpVersion::choices(),
                default: PhpVersion::recommended()->value
            ),
        ];
    }
}
