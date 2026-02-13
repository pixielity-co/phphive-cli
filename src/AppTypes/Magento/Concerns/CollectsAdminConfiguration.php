<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Magento\Concerns;

/**
 * Collects Magento admin user configuration.
 *
 * This trait handles the collection of administrator account credentials that
 * will be created during Magento installation. The admin user is required for
 * accessing the Magento Admin Panel.
 *
 * Configuration collected:
 * - Admin first name and last name
 * - Admin email address
 * - Admin username (for login)
 * - Admin password (must meet Magento requirements: min 7 chars, letters + numbers)
 *
 * All values can be provided via command-line options or interactive prompts.
 * The credentials are used in the `bin/magento setup:install` command.
 *
 * Password requirements:
 * - Minimum 7 characters
 * - Must include both letters and numbers
 * - Special characters recommended but not required
 */
trait CollectsAdminConfiguration
{
    /**
     * Collect admin user configuration.
     *
     * Prompts for or retrieves admin user credentials from command options.
     * All fields are required for Magento installation.
     *
     * Command-line options:
     * - --admin-firstname: Admin first name
     * - --admin-lastname: Admin last name
     * - --admin-email: Admin email address
     * - --admin-user: Admin username
     * - --admin-password: Admin password
     *
     * @return array<string, mixed> Configuration array with admin credentials
     */
    protected function collectAdminConfig(): array
    {
        return ['admin_firstname' => $this->input->getOption('admin-firstname') ?? $this->text(
            label: 'Admin first name',
            placeholder: 'Admin',
            default: 'Admin',
            required: true
        ), 'admin_lastname' => $this->input->getOption('admin-lastname') ?? $this->text(
            label: 'Admin last name',
            placeholder: 'User',
            default: 'User',
            required: true
        ), 'admin_email' => $this->input->getOption('admin-email') ?? $this->text(
            label: 'Admin email',
            placeholder: 'admin@example.com',
            default: 'admin@example.com',
            required: true
        ), 'admin_user' => $this->input->getOption('admin-user') ?? $this->text(
            label: 'Admin username',
            placeholder: 'admin',
            default: 'admin',
            required: true
        ), 'admin_password' => $this->input->getOption('admin-password') ?? $this->text(
            label: 'Admin password (min 7 chars, must include letters and numbers)',
            placeholder: 'Admin123!',
            default: 'Admin123!',
            required: true
        )];
    }
}
