<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Symfony\Concerns;

use PhpHive\Cli\Contracts\AppTypeInterface;

/**
 * Collects Symfony optional bundles configuration.
 *
 * This trait handles the collection of preferences for optional Symfony bundles
 * that enhance the framework with additional capabilities:
 *
 * - Maker Bundle: Code generation tools for rapid development
 * - Security Bundle: Authentication and authorization system
 *
 * Maker Bundle provides commands like:
 * - make:controller: Generate controller classes
 * - make:entity: Generate Doctrine entities
 * - make:form: Generate form classes
 * - make:crud: Generate complete CRUD operations
 * - And many more...
 *
 * Security Bundle provides:
 * - User authentication (login/logout)
 * - Password hashing
 * - Access control (roles and permissions)
 * - Remember me functionality
 * - CSRF protection
 * - And more...
 *
 * Both bundles default to true as they're commonly needed in Symfony applications.
 */
trait CollectsOptionalBundles
{
    /**
     * Collect optional Symfony bundles configuration.
     *
     * Prompts the user with yes/no questions for each optional bundle.
     * Both bundles are recommended for most Symfony applications.
     *
     * Configuration keys returned:
     * - CONFIG_INSTALL_MAKER: bool - Install Maker Bundle (dev dependency)
     * - CONFIG_INSTALL_SECURITY: bool - Install Security Bundle
     *
     * @return array<string, mixed> Configuration array with bundle installation flags
     */
    protected function collectOptionalBundlesConfig(): array
    {
        return [
            // Maker Bundle - Code generation tools for rapid development
            // Installed as dev dependency (--dev) as it's only needed during development
            // Provides make:* commands for generating boilerplate code
            // Defaults to true as it significantly speeds up development
            AppTypeInterface::CONFIG_INSTALL_MAKER => $this->confirm(
                label: 'Install Symfony Maker Bundle (Code generation)?',
                default: true
            ),

            // Security Bundle - Authentication and authorization system
            // Provides user login, password hashing, access control, etc.
            // Defaults to true as most applications need some form of authentication
            AppTypeInterface::CONFIG_INSTALL_SECURITY => $this->confirm(
                label: 'Install Security Bundle (Authentication)?',
                default: true
            ),
        ];
    }
}
