<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Magento\Concerns;

use PhpHive\Cli\Enums\MagentoEdition;

/**
 * Collects Magento authentication and edition configuration.
 *
 * This trait handles the collection of Magento Marketplace authentication keys
 * and edition selection. Authentication keys are required to download Magento
 * from the official repository at repo.magento.com.
 *
 * Magento Marketplace Keys:
 * - Public Key: Acts as username for composer authentication
 * - Private Key: Acts as password for composer authentication
 * - Obtain from: https://marketplace.magento.com/customer/accessKeys/
 *
 * Editions:
 * - Community Edition (Open Source): Free, open-source version
 * - Enterprise Edition (Commerce): Paid version with additional features
 *
 * The keys are used to generate COMPOSER_AUTH JSON for the installation command.
 *
 * @see InteractsWithMagentoMarketplace For key collection logic
 */
trait CollectsAuthenticationConfiguration
{
    /**
     * Collect Magento authentication keys and edition configuration.
     *
     * Retrieves Magento Marketplace authentication keys using the
     * InteractsWithMagentoMarketplace trait, then prompts for edition selection.
     *
     * The keys can be provided via:
     * - Environment variables (MAGENTO_PUBLIC_KEY, MAGENTO_PRIVATE_KEY)
     * - Interactive prompts
     * - Stored credentials from previous installations
     *
     * Edition can be provided via:
     * - --magento-edition option
     * - Interactive selection prompt
     *
     * @return array<string, mixed> Configuration array with authentication keys and edition
     */
    protected function collectAuthenticationConfig(): array
    {
        $config = [];

        // Get Magento authentication keys using the InteractsWithMagentoMarketplace trait
        // This handles checking environment variables, prompting, and validation
        $keys = $this->getMagentoAuthKeys(required: true);
        $config['magento_public_key'] = $keys['public_key'];
        $config['magento_private_key'] = $keys['private_key'];

        // Magento edition selection - determines which package to install
        // Community = magento/project-community-edition (free)
        // Enterprise = magento/project-enterprise-edition (requires license)
        $config['magento_edition'] = $this->input->getOption('magento-edition') ?? $this->select(
            label: 'Magento edition',
            options: MagentoEdition::choices(),
            default: MagentoEdition::default()->value
        );

        return $config;
    }
}
