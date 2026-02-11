<?php

declare(strict_types=1);

namespace MonoPhp\Cli\AppTypes;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony Application Type.
 *
 * This class handles the scaffolding and configuration of Symfony applications
 * within the monorepo. Symfony is a high-performance PHP framework for web
 * applications, known for its flexibility, reusable components, and strong
 * focus on best practices.
 *
 * Features supported:
 * - Multiple Symfony versions (6.4 LTS, 7.1 LTS, 7.2 Latest)
 * - Project types (Full web application or minimal microservice/API)
 * - Database configuration (MySQL, PostgreSQL, SQLite)
 * - Optional bundles (Maker, Security)
 * - Doctrine ORM integration
 * - Automatic database creation and migrations
 *
 * The scaffolding process:
 * 1. Collect configuration through interactive prompts
 * 2. Create Symfony project using Composer (webapp or skeleton)
 * 3. Install selected bundles and packages
 * 4. Configure database and run migrations
 * 5. Apply stub templates for monorepo integration
 *
 * Project types:
 * - webapp: Full-featured web application with Twig, forms, security, etc.
 * - skeleton: Minimal microservice/API with only essential components
 *
 * Example configuration:
 * ```php
 * [
 *     'name' => 'api',
 *     'description' => 'REST API service',
 *     'symfony_version' => '7.2',
 *     'project_type' => 'skeleton',
 *     'database' => 'postgresql',
 *     'install_maker' => true,
 *     'install_security' => true,
 * ]
 * ```
 *
 * @see https://symfony.com Symfony Framework
 * @see AbstractAppType
 */
class SymfonyAppType extends AbstractAppType
{
    /**
     * Get the display name of this application type.
     *
     * Returns a human-readable name shown in the application type selection menu.
     *
     * @return string The display name "Symfony"
     */
    public function getName(): string
    {
        return 'Symfony';
    }

    /**
     * Get a brief description of this application type.
     *
     * Returns a short description shown in the application type selection menu
     * to help users understand what this app type provides.
     *
     * @return string A brief description of Symfony
     */
    public function getDescription(): string
    {
        return 'High-performance PHP framework for web applications';
    }

    /**
     * Collect configuration through interactive prompts.
     *
     * This method guides the user through a series of interactive questions
     * to gather all necessary configuration for creating a Symfony application.
     *
     * Configuration collected:
     * - Application name and description
     * - Symfony version (6.4 LTS, 7.1 LTS, 7.2 Latest)
     * - Project type (Full webapp or minimal skeleton)
     * - Database driver (MySQL, PostgreSQL, SQLite)
     * - Optional bundles (Maker, Security)
     *
     * The configuration array is used by:
     * - getInstallCommand() to determine the installation command
     * - getPostInstallCommands() to install additional bundles
     * - getStubVariables() to populate stub templates
     *
     * @param  InputInterface       $input  Console input interface for reading arguments/options
     * @param  OutputInterface      $output Console output interface for displaying messages
     * @return array<string, mixed> Configuration array with all collected settings
     */
    public function collectConfiguration(InputInterface $input, OutputInterface $output): array
    {
        // Store input/output for use in helper methods
        $this->input = $input;
        $this->output = $output;

        // Initialize configuration array
        $config = [];

        // =====================================================================
        // BASIC INFORMATION
        // =====================================================================

        // Application name - used for directory name, package name, and namespace
        $config['name'] = $this->askText(
            label: 'Application name',
            placeholder: 'my-app',
            required: true
        );

        // Application description - used in composer.json and documentation
        $config['description'] = $this->askText(
            label: 'Application description',
            placeholder: 'A Symfony application',
            required: false
        );

        // =====================================================================
        // SYMFONY VERSION
        // =====================================================================

        // Symfony version selection
        // - Version 7.2: Latest features and improvements
        // - Version 7.1: Long-term support (LTS) with extended maintenance
        // - Version 6.4: Previous LTS version for legacy compatibility
        $config['symfony_version'] = $this->askSelect(
            label: 'Symfony version',
            options: [
                '7.2' => 'Symfony 7.2 (Latest)',
                '7.1' => 'Symfony 7.1 (LTS)',
                '6.4' => 'Symfony 6.4 (LTS)',
            ],
            default: '7.2'
        );

        // =====================================================================
        // PROJECT TYPE
        // =====================================================================

        // Project type selection determines the base skeleton
        // - webapp: Full-featured with Twig, forms, security, asset management
        // - skeleton: Minimal with only HTTP kernel and routing
        $config['project_type'] = $this->askSelect(
            label: 'Project type',
            options: [
                'webapp' => 'Web Application (Full-featured)',
                'skeleton' => 'Microservice/API (Minimal)',
            ],
            default: 'webapp'
        );

        // =====================================================================
        // DATABASE CONFIGURATION
        // =====================================================================

        // Database driver selection
        // Determines the default database connection in config/packages/doctrine.yaml
        $config['database'] = $this->askSelect(
            label: 'Database driver',
            options: [
                'mysql' => 'MySQL',
                'postgresql' => 'PostgreSQL',
                'sqlite' => 'SQLite',
            ],
            default: 'mysql'
        );

        // =====================================================================
        // OPTIONAL BUNDLES
        // =====================================================================

        // Symfony Maker Bundle - Code generation tool
        // Provides console commands to generate controllers, entities, forms, etc.
        $config['install_maker'] = $this->askConfirm(
            label: 'Install Symfony Maker Bundle (Code generation)?',
            default: true
        );

        // Symfony Security Bundle - Authentication and authorization
        // Provides user authentication, authorization, and security features
        $config['install_security'] = $this->askConfirm(
            label: 'Install Security Bundle (Authentication)?',
            default: true
        );

        return $config;
    }

    /**
     * Get the Composer command to install Symfony.
     *
     * Generates the Composer create-project command to install Symfony
     * with the specified version and project type. The command creates
     * a new Symfony project in the current directory.
     *
     * Project types:
     * - webapp: symfony/website-skeleton (full-featured)
     * - skeleton: symfony/skeleton (minimal)
     *
     * Command format:
     * ```bash
     * composer create-project symfony/{type}:{version}.* .
     * ```
     *
     * The .* allows any patch version (e.g., 7.2.0, 7.2.1, 7.2.2)
     *
     * @param  array<string, mixed> $config Configuration from collectConfiguration()
     * @return string               The Composer command to execute
     */
    public function getInstallCommand(array $config): string
    {
        // Extract version and project type from config
        $version = $config['symfony_version'] ?? '7.2';
        $type = $config['project_type'] ?? 'webapp';

        // Determine which Symfony skeleton to use
        if ($type === 'webapp') {
            // Full-featured web application skeleton
            return "composer create-project symfony/website-skeleton:{$version}.* .";
        }

        // Minimal microservice/API skeleton
        return "composer create-project symfony/skeleton:{$version}.* .";
    }

    /**
     * Get post-installation commands to execute.
     *
     * Returns an array of shell commands to execute after the base Symfony
     * installation is complete. These commands install additional bundles,
     * configure the application, and run initial setup tasks.
     *
     * Command execution order:
     * 1. Install optional bundles (Maker, Security)
     * 2. Install Doctrine ORM pack
     * 3. Create database if it doesn't exist
     * 4. Run database migrations
     *
     * All commands are executed in the application directory and should
     * complete successfully before the scaffolding is considered complete.
     *
     * @param  array<string, mixed> $config Configuration from collectConfiguration()
     * @return array<string>        Array of shell commands to execute sequentially
     */
    public function getPostInstallCommands(array $config): array
    {
        // Initialize commands array
        $commands = [];

        // =====================================================================
        // OPTIONAL BUNDLES
        // =====================================================================

        // Install Symfony Maker Bundle if requested
        // Maker provides code generation commands (make:controller, make:entity, etc.)
        if ($config['install_maker'] ?? true) {
            $commands[] = 'composer require --dev symfony/maker-bundle';
        }

        // Install Symfony Security Bundle if requested
        // Security provides authentication, authorization, and user management
        if ($config['install_security'] ?? true) {
            $commands[] = 'composer require symfony/security-bundle';
        }

        // =====================================================================
        // DATABASE SETUP
        // =====================================================================

        // Install Doctrine ORM pack (includes doctrine-bundle, doctrine-orm, etc.)
        $commands[] = 'composer require symfony/orm-pack';

        // Create database if it doesn't exist
        // The --if-not-exists flag prevents errors if database already exists
        $commands[] = 'php bin/console doctrine:database:create --if-not-exists';

        // Run database migrations to create tables
        // The --no-interaction flag runs migrations without prompting
        $commands[] = 'php bin/console doctrine:migrations:migrate --no-interaction';

        return $commands;
    }

    /**
     * Get the path to Symfony-specific stub templates.
     *
     * Returns the absolute path to the directory containing stub templates
     * specifically for Symfony applications. These stubs include:
     * - composer.json with Symfony-specific dependencies
     * - package.json for frontend assets
     * - phpunit.xml for testing configuration
     * - .env.example with Symfony environment variables
     * - Monorepo-specific configuration files
     *
     * The stub files contain placeholders (e.g., {{APP_NAME}}) that are
     * replaced with actual values using getStubVariables().
     *
     * @return string Absolute path to cli/stubs/symfony-app directory
     */
    public function getStubPath(): string
    {
        // Get base stubs directory and append symfony-app subdirectory
        return $this->getBaseStubPath() . '/symfony-app';
    }

    /**
     * Get variables for stub template replacement.
     *
     * Returns an associative array of placeholder => value pairs used to
     * replace placeholders in stub template files. This method combines
     * common variables (from parent class) with Symfony-specific variables.
     *
     * Common variables (from AbstractAppType):
     * - {{APP_NAME}}: Original application name
     * - {{APP_NAME_NORMALIZED}}: Normalized directory/package name
     * - {{APP_NAMESPACE}}: PascalCase namespace component
     * - {{PACKAGE_NAME}}: Full Composer package name
     * - {{DESCRIPTION}}: Application description
     *
     * Symfony-specific variables:
     * - {{DATABASE_DRIVER}}: Selected database driver (mysql, postgresql, etc.)
     * - {{SYMFONY_VERSION}}: Selected Symfony version (6.4, 7.1, 7.2)
     *
     * Example stub usage:
     * ```json
     * {
     *   "name": "{{PACKAGE_NAME}}",
     *   "description": "{{DESCRIPTION}}",
     *   "require": {
     *     "symfony/framework-bundle": "^{{SYMFONY_VERSION}}"
     *   }
     * }
     * ```
     *
     * @param  array<string, mixed>  $config Configuration from collectConfiguration()
     * @return array<string, string> Associative array of placeholder => value pairs
     */
    public function getStubVariables(array $config): array
    {
        // Get common variables from parent class
        $common = $this->getCommonStubVariables($config);

        // Merge with Symfony-specific variables
        return array_merge($common, [
            // Database driver for .env and config/packages/doctrine.yaml
            '{{DATABASE_DRIVER}}' => $config['database'] ?? 'mysql',

            // Symfony version for composer.json constraints
            '{{SYMFONY_VERSION}}' => $config['symfony_version'] ?? '7.2',
        ]);
    }
}
