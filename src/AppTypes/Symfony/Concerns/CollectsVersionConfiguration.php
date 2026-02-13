<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Symfony\Concerns;

use PhpHive\Cli\Contracts\AppTypeInterface;
use PhpHive\Cli\Enums\SymfonyVersion;

/**
 * Collects Symfony version configuration.
 *
 * This trait prompts the user to select which major version of Symfony to install.
 * Different versions have different PHP requirements, features, and support timelines.
 *
 * Symfony versions (as of 2026):
 * - 7.4: Current LTS (Long Term Support) version - RECOMMENDED
 * - 7.3: Latest stable version
 * - 7.2: Stable version
 * - 6.4: Previous LTS version with extended support
 *
 * LTS (Long Term Support) versions:
 * - Receive bug fixes for 3 years
 * - Receive security fixes for 4 years
 * - Recommended for long-term projects
 * - More stable and predictable
 * - Current LTS: 7.4 and 6.4
 *
 * Standard versions:
 * - Receive bug fixes for 8 months
 * - Receive security fixes for 14 months
 * - Get latest features faster
 * - Good for projects that can upgrade frequently
 *
 * The version selection determines:
 * - Which Symfony version is installed via composer create-project
 * - PHP version requirements (7.x requires PHP 8.2+, 6.4 requires PHP 8.1+)
 * - Available features and APIs
 * - Support timeline
 *
 * For production applications, LTS versions (7.4, 6.4) are recommended.
 *
 * Note: Versions are hardcoded for now. Future improvement: implement dynamic
 * version resolution via SymfonyVersionProvider that queries available versions
 * from Packagist to stay future-proof without CLI updates.
 */
trait CollectsVersionConfiguration
{
    /**
     * Collect Symfony version selection.
     *
     * Prompts the user to choose a Symfony version. Defaults to 7.4 as it's
     * the current LTS version with extended support and recommended for
     * production applications.
     *
     * The version is used in the composer create-project command:
     * composer create-project symfony/skeleton:X.Y.* .
     *
     * Version constraints use wildcard (X.Y.*) to get the latest patch version
     * within the selected minor version, ensuring security updates are included.
     *
     * @return array<string, mixed> Configuration array with CONFIG_SYMFONY_VERSION key
     */
    protected function collectVersionConfig(): array
    {
        $versionKey = $this->select(
            label: 'Symfony version',
            options: SymfonyVersion::choices(),
            default: SymfonyVersion::default()->value
        );

        $symfonyVersion = SymfonyVersion::from($versionKey);

        return [
            // Symfony version selection - determines which version to install
            // LTS versions (7.4, 6.4) are recommended for production applications
            // Standard versions get latest features but shorter support
            AppTypeInterface::CONFIG_SYMFONY_VERSION => $symfonyVersion->value,
        ];
    }
}
