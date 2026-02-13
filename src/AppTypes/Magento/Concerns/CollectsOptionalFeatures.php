<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Magento\Concerns;

/**
 * Collects Magento optional features configuration (sample data).
 *
 * This trait handles the collection of optional Magento features that can be
 * enabled during installation. Currently focuses on sample data installation.
 *
 * Sample Data:
 * - Includes demo products, categories, CMS pages, and media
 * - Useful for development, testing, and demonstrations
 * - Not recommended for production installations
 * - Deployed via `bin/magento sampledata:deploy` command
 *
 * The sample data provides:
 * - Product catalog with various product types
 * - Category structure
 * - CMS pages and blocks
 * - Customer data
 * - Order history
 * - Media files (product images)
 *
 * Command-line option:
 * - --sample-data: Enable/disable sample data installation
 */
trait CollectsOptionalFeatures
{
    /**
     * Collect optional features configuration.
     *
     * Prompts for or retrieves optional feature selections from command options.
     * Currently handles sample data installation preference.
     *
     * Sample data is useful for:
     * - Development environments
     * - Testing and QA
     * - Demonstrations and training
     * - Learning Magento features
     *
     * @return array<string, mixed> Configuration array with optional feature flags
     */
    protected function collectOptionalFeaturesConfig(): array
    {
        $config = [];

        // Sample data - Demo products, categories, and content
        // Check --sample-data option first, then prompt if not provided
        // Defaults to false as sample data is typically not needed for production
        $sampleDataOption = $this->input->getOption('sample-data');
        $config['install_sample_data'] = $sampleDataOption !== null ? (bool) $sampleDataOption : $this->confirm(
            label: 'Install sample data (demo products and content)?',
            default: false
        );

        return $config;
    }
}
