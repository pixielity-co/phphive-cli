<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Laravel\Concerns;

use Illuminate\Support\Str;
use PhpHive\Cli\Contracts\AppTypeInterface;

/**
 * Collects basic Laravel application configuration.
 *
 * This trait is Laravel-specific and collects the application name and description
 * through interactive prompts. Unlike the base CollectsBasicConfiguration trait,
 * this version doesn't check command arguments/options - it always prompts.
 *
 * The collected configuration is used for:
 * - Application directory naming
 * - Composer package naming (phphive/app-name)
 * - Namespace generation (PhpHive\AppName)
 * - composer.json metadata
 *
 * @see \PhpHive\Cli\AppTypes\Concerns\CollectsBasicConfiguration Base version
 */
trait CollectsBasicConfiguration
{
    /**
     * Collect basic Laravel application configuration.
     *
     * Prompts the user for application name and description using interactive
     * text inputs. Both values are always collected via prompts regardless of
     * command arguments.
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
                placeholder: 'A Laravel application',
                required: false
            );
        }

        return [
            AppTypeInterface::CONFIG_NAME => $name,
            AppTypeInterface::CONFIG_DESCRIPTION => $description,
        ];
    }
}
