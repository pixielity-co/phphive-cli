<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Magento\Concerns;

/**
 * Collects basic Magento application configuration.
 *
 * This trait handles the collection of fundamental application metadata:
 * application name and description. Unlike other AppTypes, Magento requires
 * the name to be provided as a command argument (not prompted).
 *
 * The application name is used for:
 * - Directory naming in the monorepo (apps/magento-store)
 * - Database naming (magento_store)
 * - Service naming in Docker (magento-store-db, magento-store-redis)
 * - Package identification
 *
 * The description is optional and used in:
 * - composer.json metadata
 * - Documentation
 * - Project README files
 */
trait CollectsBasicConfiguration
{
    /**
     * Collect basic application configuration (name and description).
     *
     * Retrieves the application name from the command argument (required)
     * and optionally collects a description via option or prompt.
     *
     * Name source:
     * - Command argument: php phive create:app magento-store
     *
     * Description sources:
     * - --description option
     * - Interactive prompt if option not provided
     *
     * @return array<string, mixed> Configuration array with CONFIG_NAME and CONFIG_DESCRIPTION keys
     */
    protected function collectBasicConfig(): array
    {
        return ['name' => $this->input->getArgument('name'), 'description' => $this->input->getOption('description') ?? $this->text(
            label: 'Application description',
            placeholder: 'A Magento e-commerce store',
            required: false
        )];
    }
}
