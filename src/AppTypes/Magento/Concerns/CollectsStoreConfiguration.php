<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Magento\Concerns;

/**
 * Collects Magento store configuration (URL, language, currency, timezone).
 *
 * This trait handles the collection of fundamental store settings that define
 * how the Magento store operates and presents itself to customers.
 *
 * Configuration collected:
 * - Base URL: The primary URL where the store will be accessible
 * - Language: Default store language/locale
 * - Currency: Default store currency
 * - Timezone: Store timezone for dates, times, and scheduled operations
 *
 * These settings are used in the bin/magento setup:install command and can be
 * changed later through the Magento Admin Panel.
 *
 * Base URL considerations:
 * - Development: http://localhost/ or http://localhost:8080/
 * - Production: https://www.example.com/
 * - Must include trailing slash
 *
 * Language codes follow ISO 639-1 (language) + ISO 3166-1 (country) format:
 * - en_US: English (United States)
 * - en_GB: English (United Kingdom)
 * - fr_FR: French (France)
 * - etc.
 *
 * Currency codes follow ISO 4217 standard:
 * - USD: US Dollar
 * - EUR: Euro
 * - GBP: British Pound
 * - etc.
 *
 * Timezone uses PHP timezone identifiers (e.g., America/New_York, Europe/London).
 *
 * Command-line options:
 * - --base-url: Store base URL
 * - --language: Default language
 * - --currency: Default currency
 * - --timezone: Store timezone
 */
trait CollectsStoreConfiguration
{
    /**
     * Collect store configuration.
     *
     * Prompts for or retrieves store settings from command options.
     * These settings define the store's basic operational parameters.
     *
     * All settings can be provided via command-line options or interactive prompts.
     * They are used in the bin/magento setup:install command during installation.
     *
     * @return array<string, mixed> Configuration array with store settings
     */
    protected function collectStoreConfig(): array
    {
        return ['base_url' => $this->input->getOption('base-url') ?? $this->text(
            label: 'Base URL',
            placeholder: 'http://localhost/',
            default: 'http://localhost/',
            required: true
        ), 'language' => $this->input->getOption('language') ?? $this->select(
            label: 'Default language',
            options: [
                'en_US' => 'English (United States)',
                'en_GB' => 'English (United Kingdom)',
                'fr_FR' => 'French (France)',
                'de_DE' => 'German (Germany)',
                'es_ES' => 'Spanish (Spain)',
            ],
            default: 'en_US'
        ), 'currency' => $this->input->getOption('currency') ?? $this->select(
            label: 'Default currency',
            options: [
                'USD' => 'US Dollar (USD)',
                'EUR' => 'Euro (EUR)',
                'GBP' => 'British Pound (GBP)',
            ],
            default: 'USD'
        ), 'timezone' => $this->input->getOption('timezone') ?? $this->select(
            label: 'Default timezone',
            options: [
                'America/New_York' => 'America/New_York (EST)',
                'America/Chicago' => 'America/Chicago (CST)',
                'America/Los_Angeles' => 'America/Los_Angeles (PST)',
                'Europe/London' => 'Europe/London (GMT)',
                'Europe/Paris' => 'Europe/Paris (CET)',
            ],
            default: 'America/New_York'
        )];
    }
}
