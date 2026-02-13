<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Symfony\Concerns;

use PhpHive\Cli\Contracts\AppTypeInterface;

/**
 * Collects basic Symfony application configuration.
 *
 * This trait handles the collection of fundamental application metadata for
 * Symfony applications: name and description.
 *
 * The application name is used for:
 * - Directory naming in the monorepo (apps/my-symfony-app)
 * - Composer package naming (phphive/my-symfony-app)
 * - Namespace generation (PhpHive\MySymfonyApp)
 * - Service naming in Docker (my-symfony-app-db, my-symfony-app-redis)
 *
 * The description is optional and used in:
 * - composer.json metadata
 * - Documentation
 * - Project README files
 *
 * All values are collected via interactive prompts (no command-line options).
 */
trait CollectsBasicConfiguration
{
    /**
     * Collect basic Symfony application configuration.
     *
     * Prompts the user for application name and description using interactive
     * text inputs. Both values are always collected via prompts.
     *
     * The name should be:
     * - Descriptive and meaningful
     * - Lowercase with hyphens (will be normalized automatically)
     * - Unique within the monorepo
     *
     * The description should briefly explain the application's purpose.
     *
     * @return array<string, mixed> Configuration array with CONFIG_NAME and CONFIG_DESCRIPTION keys
     */
    protected function collectBasicConfig(): array
    {
        // Get name from input argument if available (don't prompt if already provided)
        $name = $this->input->getArgument('name');
        if ($name === null || Str::trim($name) === '') {
            $name = $this->text(
                label: 'Application name',
                placeholder: 'my-app',
                required: true
            );
        }

        // Get description from input option if available
        $description = $this->input->getOption('description');
        if ($description === null || Str::trim($description) === '') {
            $description = $this->text(
                label: 'Application description',
                placeholder: 'A Symfony application',
                required: false
            );
        }

        return [
            AppTypeInterface::CONFIG_NAME => $name,
            AppTypeInterface::CONFIG_DESCRIPTION => $description,
        ];
    }
}
