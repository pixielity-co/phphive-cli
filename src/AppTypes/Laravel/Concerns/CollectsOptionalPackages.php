<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Laravel\Concerns;

use PhpHive\Cli\Contracts\AppTypeInterface;
use PhpHive\Cli\Enums\OctaneServer;

/**
 * Collects Laravel optional packages configuration (Horizon, Telescope, Sanctum, Octane).
 *
 * This trait handles the collection of configuration for Laravel's optional first-party
 * packages that enhance the framework with additional capabilities:
 *
 * - Horizon: Beautiful dashboard and queue monitoring for Redis queues
 * - Telescope: Elegant debug assistant providing insight into requests, exceptions, logs, etc.
 * - Sanctum: Simple token-based API authentication system
 * - Octane: Supercharges application performance by serving via high-performance servers
 *
 * Special handling for Octane:
 * - If Octane is selected, prompts for server choice (RoadRunner, FrankenPHP, Swoole)
 * - If Swoole is selected, checks if the PHP extension is installed
 * - Offers automatic installation of Swoole via PECL if not present
 *
 * @see handleSwooleInstallation() For Swoole extension installation logic
 */
trait CollectsOptionalPackages
{
    /**
     * Collect optional Laravel packages configuration.
     *
     * Prompts the user with yes/no questions for each optional package.
     * If Octane is selected, additional prompts collect the server preference
     * and handle Swoole installation if needed.
     *
     * Configuration keys returned:
     * - CONFIG_INSTALL_HORIZON: bool - Install Laravel Horizon
     * - CONFIG_INSTALL_TELESCOPE: bool - Install Laravel Telescope
     * - CONFIG_INSTALL_SANCTUM: bool - Install Laravel Sanctum (defaults to true)
     * - CONFIG_INSTALL_OCTANE: bool - Install Laravel Octane
     * - CONFIG_OCTANE_SERVER: string - Server choice (roadrunner|frankenphp|swoole) if Octane enabled
     *
     * @return array<string, mixed> Configuration array with package installation flags
     */
    protected function collectOptionalPackagesConfig(): array
    {
        $config = [];

        // Laravel Horizon - Queue monitoring dashboard for Redis-based queues
        // Provides beautiful UI for monitoring job throughput, runtime, and failures
        $config[AppTypeInterface::CONFIG_INSTALL_HORIZON] = $this->confirm(
            label: 'Install Laravel Horizon (Queue monitoring)?',
            default: false
        );

        // Laravel Telescope - Debug assistant for development
        // Tracks requests, exceptions, database queries, jobs, mail, and more
        $config[AppTypeInterface::CONFIG_INSTALL_TELESCOPE] = $this->confirm(
            label: 'Install Laravel Telescope (Debugging)?',
            default: false
        );

        // Laravel Sanctum - API authentication system
        // Provides token-based authentication for SPAs and mobile apps
        // Defaults to true as most modern Laravel apps need API authentication
        $config[AppTypeInterface::CONFIG_INSTALL_SANCTUM] = $this->confirm(
            label: 'Install Laravel Sanctum (API authentication)?',
            default: true
        );

        // Laravel Octane - High-performance application server
        // Serves application using RoadRunner, FrankenPHP, or Swoole for massive performance gains
        $config[AppTypeInterface::CONFIG_INSTALL_OCTANE] = $this->confirm(
            label: 'Install Laravel Octane (High-performance server)?',
            default: false
        );

        // If Octane is selected, prompt for server choice
        if ($config[AppTypeInterface::CONFIG_INSTALL_OCTANE] === true) {
            $config[AppTypeInterface::CONFIG_OCTANE_SERVER] = $this->select(
                label: 'Octane server',
                options: OctaneServer::choices(),
                default: OctaneServer::default()->value
            );

            // Swoole requires a PHP extension - check if installed and offer to install
            if ($config[AppTypeInterface::CONFIG_OCTANE_SERVER] === OctaneServer::SWOOLE->value) {
                $this->handleSwooleInstallation();
            }
        }

        return $config;
    }

    /**
     * Handle Swoole PHP extension installation.
     *
     * Checks if the Swoole extension is loaded in the current PHP environment.
     * If not installed, offers to automatically install it via PECL.
     *
     * Installation process:
     * 1. Check if swoole extension is loaded via extension_loaded()
     * 2. If not loaded, prompt user for automatic installation
     * 3. Detect OS (macOS vs Linux) for platform-specific instructions
     * 4. Attempt installation via PECL
     * 5. Provide manual installation instructions if automatic fails
     *
     * Platform-specific behavior:
     * - macOS: Uses PECL (requires PECL to be installed via Homebrew)
     * - Linux: Uses PECL
     * - Both: Provides fallback instructions for manual installation
     *
     * Note: After installation, users may need to:
     * - Add 'extension=swoole.so' to php.ini
     * - Restart their terminal or PHP-FPM
     */
    private function handleSwooleInstallation(): void
    {
        // Check if Swoole extension is already loaded
        $swooleInstalled = extension_loaded('swoole');

        if (! $swooleInstalled) {
            $this->warning('Swoole PHP extension is not installed.');

            // Prompt user for automatic installation attempt
            $autoInstall = $this->confirm(
                label: 'Would you like to attempt automatic Swoole installation?',
                default: true,
                hint: 'This will use PECL or Homebrew depending on your system'
            );

            if ($autoInstall) {
                $this->info('Attempting to install Swoole...');

                // Detect operating system for platform-specific installation
                $os = PHP_OS_FAMILY;
                $installSuccess = false;
                $errorOutput = [];

                if ($os === 'Darwin') {
                    // macOS - Swoole must be installed via PECL
                    $this->info('Detected macOS - Swoole must be installed via PECL');

                    // Check if PECL is available
                    exec('which pecl 2>&1', $peclCheck, $peclExists);

                    if ($peclExists === 0) {
                        // PECL is available, attempt installation
                        $this->info('Running: pecl install swoole');
                        exec('pecl install swoole 2>&1', $errorOutput, $result);
                        $installSuccess = ($result === 0);

                        if (! $installSuccess) {
                            $this->error("PECL installation failed with exit code: {$result}");
                            $this->note(implode("\n", $errorOutput), 'Error Output');
                        }
                    } else {
                        // PECL not found - provide installation instructions
                        $this->error('PECL is not installed.');
                        $this->note(
                            "PECL is required to install Swoole on macOS.\n\n" .
                            "Install PECL first:\n" .
                            "  brew install php (includes PECL)\n\n" .
                            "Or if using Herd/Valet:\n" .
                            '  PECL should already be available',
                            'PECL Required'
                        );
                    }
                } else {
                    // Linux or other Unix-like systems - try PECL
                    $this->info('Trying PECL installation...');
                    $this->info('Running: pecl install swoole');
                    exec('pecl install swoole 2>&1', $errorOutput, $result);
                    $installSuccess = ($result === 0);

                    if (! $installSuccess) {
                        $this->error("PECL installation failed with exit code: {$result}");
                        $this->note(implode("\n", $errorOutput), 'Error Output');
                    }
                }

                // Report installation result
                if ($installSuccess) {
                    $this->info('✓ Swoole installation completed!');
                    $this->warning('You may need to restart your terminal/PHP-FPM for changes to take effect.');
                } else {
                    // Installation failed - provide manual instructions
                    $this->error('Automatic installation failed.');
                    $this->note(
                        "Please install Swoole manually:\n\n" .
                        "  macOS:\n" .
                        "    pecl install swoole\n\n" .
                        "  Linux:\n" .
                        "    pecl install swoole\n\n" .
                        "  Or use Docker with a Swoole-enabled PHP image.\n\n" .
                        "Note: You may need to add 'extension=swoole.so' to your php.ini",
                        'Manual Installation'
                    );
                }
            } else {
                // User declined automatic installation - show manual instructions
                $this->note(
                    "To install Swoole manually:\n\n" .
                    "  macOS:\n" .
                    "    pecl install swoole\n\n" .
                    "  Linux:\n" .
                    "    pecl install swoole\n\n" .
                    "  Or use Docker with a Swoole-enabled PHP image.\n\n" .
                    "Note: You may need to add 'extension=swoole.so' to your php.ini",
                    'Manual Installation'
                );
            }

            // Pause to let user read the information before continuing
            $this->pause('Press enter to continue with Laravel installation...');
            $this->warning('Note: If Swoole was just installed, you may need to restart your terminal.');
        }
    }
}
