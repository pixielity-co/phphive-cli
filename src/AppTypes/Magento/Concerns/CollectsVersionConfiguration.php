<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Magento\Concerns;

use PhpHive\Cli\Enums\MagentoVersion;

/**
 * Collects Magento version configuration.
 *
 * This trait prompts the user to select which version of Magento 2 to install.
 * Different versions have different PHP requirements, features, and support timelines.
 *
 * Magento 2.4.x versions:
 * - 2.4.7: Latest version with newest features (PHP 8.2-8.3)
 * - 2.4.6: Stable version (PHP 8.1-8.2)
 * - 2.4.5: Older stable version (PHP 8.1)
 *
 * Version considerations:
 * - Latest version: Best for new projects, includes latest features and security fixes
 * - Older versions: May be required for compatibility with specific extensions
 * - PHP requirements: Each version has specific PHP version requirements
 * - Support timeline: Check Magento's official support policy
 *
 * The version selection determines:
 * - Which Magento package version is installed via composer
 * - PHP version requirements
 * - Available features and APIs
 * - Compatible extensions and themes
 *
 * Command-line option:
 * - --magento-version: Specify Magento version (e.g., 2.4.7)
 */
trait CollectsVersionConfiguration
{
    /**
     * Collect Magento version configuration.
     *
     * Prompts for or retrieves Magento version from command option.
     * The version is used in the composer create-project command to install
     * the specified Magento version.
     *
     * Version format: X.Y.Z (e.g., 2.4.7)
     * - X: Major version (always 2 for Magento 2)
     * - Y: Minor version (4 for current generation)
     * - Z: Patch version (incremental updates)
     *
     * @return array<string, mixed> Configuration array with CONFIG_MAGENTO_VERSION key
     */
    protected function collectVersionConfig(): array
    {
        $versionKey = $this->input->getOption('magento-version') ?? $this->select(
            label: 'Magento version',
            options: MagentoVersion::choices(),
            default: MagentoVersion::default()->value
        );

        $magentoVersion = MagentoVersion::from($versionKey);

        return ['magento_version' => $magentoVersion->value];
    }
}
