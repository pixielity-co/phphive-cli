<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Skeleton\Concerns;

use PhpHive\Cli\Contracts\AppTypeInterface;

/**
 * Collects Skeleton quality tools configuration.
 *
 * This trait handles the collection of preferences for testing and code quality
 * tools in Skeleton applications. These tools help maintain code quality and
 * catch bugs early in development.
 *
 * Quality tools offered:
 * - PHPUnit: Unit testing framework for writing and running tests
 * - PHPStan: Static analysis tool for finding bugs without running code
 * - Pint: Opinionated code formatter (Laravel's code style tool)
 *
 * Benefits of including quality tools:
 * - PHPUnit: Catch bugs early, enable refactoring with confidence, document behavior
 * - PHPStan: Find type errors, null pointer exceptions, and logic errors statically
 * - Pint: Maintain consistent code style across the project automatically
 *
 * Both options default to true as they're considered best practices for
 * professional PHP development.
 */
trait CollectsQualityToolsConfiguration
{
    /**
     * Collect quality tools configuration.
     *
     * Prompts the user to decide whether to include testing and quality tools
     * in the Skeleton application. Both options are recommended for professional
     * development.
     *
     * If tests are included:
     * - PHPUnit will be added to composer.json (dev dependency)
     * - tests/ directory will be created
     * - phpunit.xml configuration will be generated
     * - composer test script will be added
     *
     * If quality tools are included:
     * - PHPStan will be added to composer.json (dev dependency)
     * - Pint will be added to composer.json (dev dependency)
     * - phpstan.neon configuration will be generated
     * - pint.json configuration will be generated
     * - composer scripts for analysis and formatting will be added
     *
     * @return array<string, mixed> Configuration array with CONFIG_INCLUDE_TESTS and CONFIG_INCLUDE_QUALITY_TOOLS keys
     */
    protected function collectQualityToolsConfig(): array
    {
        return [
            // PHPUnit for testing - enables writing and running unit tests
            // Defaults to true as testing is a fundamental best practice
            AppTypeInterface::CONFIG_INCLUDE_TESTS => $this->confirm(
                label: 'Include PHPUnit for testing?',
                default: true
            ),

            // Quality tools (PHPStan + Pint) - static analysis and code formatting
            // PHPStan finds bugs through static analysis without running code
            // Pint automatically formats code to match Laravel's style guide
            // Defaults to true as they significantly improve code quality
            AppTypeInterface::CONFIG_INCLUDE_QUALITY_TOOLS => $this->confirm(
                label: 'Include quality tools (PHPStan, Pint)?',
                default: true
            ),
        ];
    }
}
