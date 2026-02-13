<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Laravel\Concerns;

use PhpHive\Cli\Contracts\AppTypeInterface;
use PhpHive\Cli\Enums\LaravelStarterKit;

/**
 * Collects Laravel starter kit configuration.
 *
 * This trait prompts the user to select a Laravel starter kit for authentication
 * scaffolding. Starter kits provide pre-built authentication UI and logic.
 *
 * Available options:
 * - None: No starter kit, build authentication from scratch
 * - Breeze: Minimal, simple authentication scaffolding with Blade or Inertia
 * - Jetstream: Full-featured authentication with teams, 2FA, and profile management
 *
 * The selected starter kit is installed during post-installation via:
 * - composer require laravel/breeze --dev
 * - php artisan breeze:install
 * Or:
 * - composer require laravel/jetstream
 * - php artisan jetstream:install livewire
 */
trait CollectsStarterKitConfiguration
{
    /**
     * Collect starter kit selection.
     *
     * Prompts the user to choose a Laravel starter kit for authentication.
     * Defaults to 'none' as many developers prefer to build custom authentication
     * or use API-only authentication with Sanctum.
     *
     * @return array<string, mixed> Configuration array with CONFIG_STARTER_KIT key
     */
    protected function collectStarterKitConfig(): array
    {
        return [
            // Starter kit selection - determines which authentication scaffolding to install
            // 'none' = no starter kit, 'breeze' = simple auth, 'jetstream' = full-featured
            AppTypeInterface::CONFIG_STARTER_KIT => $this->select(
                label: 'Starter kit',
                options: LaravelStarterKit::choices(),
                default: LaravelStarterKit::default()->value
            ),
        ];
    }
}
