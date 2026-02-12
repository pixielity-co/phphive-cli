<?php

declare(strict_types=1);

namespace PhpHive\Cli\Concerns;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

use PhpHive\Cli\Support\Filesystem;
use RuntimeException;

/**
 * Redis Interaction Trait.
 *
 * This trait provides comprehensive Redis setup functionality for application
 * types that require caching and session storage configuration. It supports
 * both Docker-based and local Redis setups with automatic configuration and
 * graceful fallbacks.
 *
 * Key features:
 * - Docker-first approach: Recommends Docker when available
 * - Automatic Docker Compose integration
 * - Container management and health checking
 * - Secure password generation
 * - Local Redis fallback for non-Docker setups
 * - Graceful error handling with fallback options
 * - Detailed user feedback using Laravel Prompts
 * - Reusable across multiple app types (Magento, Laravel, Symfony, etc.)
 *
 * Docker-first workflow:
 * 1. Check if Docker is available
 * 2. If yes, offer Docker Redis setup (recommended)
 * 3. Generate secure password
 * 4. Generate docker-compose section for Redis
 * 5. Start Docker container
 * 6. Wait for Redis to be ready (health check)
 * 7. Return connection details with password
 * 8. If Docker unavailable or user declines, fall back to local setup
 *
 * Local Redis workflow:
 * 1. Assume Redis is installed and running locally
 * 2. Prompt for Redis host and port
 * 3. Prompt for password (if configured)
 * 4. Return configuration for application
 * 5. Provide installation guidance if needed
 *
 * Example usage:
 * ```php
 * use PhpHive\Cli\Concerns\InteractsWithRedis;
 * use PhpHive\Cli\Concerns\InteractsWithDocker;
 *
 * class MyAppType extends AbstractAppType
 * {
 *     use InteractsWithRedis;
 *     use InteractsWithDocker;
 *
 *     public function collectConfiguration($input, $output): array
 *     {
 *         $this->input = $input;
 *         $this->output = $output;
 *
 *         // Orchestrate Redis setup (Docker-first)
 *         $cacheConfig = $this->setupRedis('my-app', '/path/to/app');
 *
 *         return $cacheConfig;
 *     }
 * }
 * ```
 *
 * Security considerations:
 * - Passwords are generated using cryptographically secure random bytes
 * - Passwords are 32 characters long (hex-encoded 16 bytes)
 * - Docker containers are isolated per project
 * - Connection attempts include health checks
 * - Password authentication is enforced in Docker setup
 *
 * @phpstan-ignore-next-line trait.unused
 *
 * @see AbstractAppType For base app type functionality
 * @see InteractsWithDocker For Docker management functionality
 * @see InteractsWithPrompts For prompt helper methods
 */
trait InteractsWithRedis
{
    /**
     * Get the Filesystem service instance.
     *
     * This method provides access to the Filesystem service for file operations.
     * It should be implemented by the class using this trait to return the
     * appropriate Filesystem instance from the dependency injection container.
     *
     * @return Filesystem The Filesystem service instance
     */
    abstract protected function filesystem(): Filesystem;

    /**
     * Orchestrate Redis setup with Docker-first approach.
     *
     * This is the main entry point for Redis setup. It intelligently
     * chooses between Docker and local Redis based on availability and
     * user preference, with graceful fallbacks at each step.
     *
     * Decision flow:
     * 1. Check if Docker is available (requires InteractsWithDocker trait)
     * 2. If Docker available:
     *    - Offer Docker setup (recommended)
     *    - If user accepts → setupDockerRedis()
     *    - If user declines → setupLocalRedis()
     * 3. If Docker not available:
     *    - Show installation guidance (optional)
     *    - Fall back to setupLocalRedis()
     *
     * Redis features:
     * - In-memory data structure store
     * - Caching and session storage
     * - Pub/Sub messaging
     * - Persistence options (RDB, AOF)
     * - High performance and scalability
     *
     * Return value structure:
     * ```php
     * [
     *     'redis_host' => 'localhost',      // Host
     *     'redis_port' => 6379,             // Port
     *     'redis_password' => 'password',   // Password (optional)
     *     'using_docker' => true,           // Whether Docker is used
     * ]
     * ```
     *
     * @param  string $appName Application name for defaults
     * @param  string $appPath Absolute path to application directory
     * @return array  Redis configuration array
     */
    protected function setupRedis(string $appName, string $appPath): array
    {
        // Check if Docker is available (requires InteractsWithDocker trait)

        if ($this->isDockerAvailable()) {
            // Docker is available - offer Docker setup
            note(
                'Docker detected! Using Docker provides isolated Redis instances, easy management, and no local installation needed.',
                'Redis Setup'
            );

            $useDocker = confirm(
                label: 'Would you like to use Docker for Redis? (recommended)',
                default: true
            );

            if ($useDocker) {
                $cacheConfig = $this->setupDockerRedis($appName, $appPath);
                if ($cacheConfig !== null) {
                    return $cacheConfig;
                }

                // Docker setup failed, fall back to local
                warning('Docker setup failed. Falling back to local Redis setup.');
            }

        } elseif (! $this->isDockerInstalled()) {
            // Docker not installed - offer installation guidance
            $installDocker = confirm(
                label: 'Docker is not installed. Would you like to see installation instructions?',
                default: false
            );

            if ($installDocker) {
                $this->provideDockerInstallationGuidance();
                info('After installing Docker, you can recreate this application to use Docker.');
            }
        }

        // Fall back to local Redis setup
        return $this->setupLocalRedis($appName);
    }

    /**
     * Set up Redis using Docker container.
     *
     * Creates a Docker Compose configuration with Redis service
     * and starts the container. Includes health checking to ensure
     * Redis is ready before returning.
     *
     * Process:
     * 1. Generate secure password
     * 2. Prompt for port configuration (default: 6379)
     * 3. Generate docker-compose.yml section for Redis
     * 4. Start Docker container
     * 5. Wait for Redis to be ready (health check)
     * 6. Return connection details with password
     *
     * Generated configuration:
     * - Service name: redis
     * - Image: redis:7-alpine
     * - Port: 6379 (default, configurable)
     * - Volume: Persistent data storage with AOF
     * - Command: redis-server with password and persistence
     * - Health check: redis-cli ping
     *
     * Container naming:
     * - Format: phphive-{app-name}-redis
     * - Example: phphive-my-shop-redis
     *
     * @param  string     $appName Application name
     * @param  string     $appPath Application directory path
     * @return array|null Redis config on success, null on failure
     */
    protected function setupDockerRedis(string $appName, string $appPath): ?array
    {
        // Check if running in non-interactive mode
        if (! $this->input->isInteractive()) {
            return null;
        }

        // =====================================================================
        // CONFIGURATION
        // =====================================================================

        info('Configuring Redis...');

        // Generate secure password (32 characters hex)
        $password = bin2hex(random_bytes(16));

        // Prompt for port configuration
        $portInput = text(
            label: 'Redis port',
            placeholder: '6379',
            default: '6379',
            required: true,
            hint: 'Port for Redis server'
        );
        $port = (int) $portInput;

        // =====================================================================
        // GENERATE DOCKER COMPOSE FILE
        // =====================================================================

        info('Generating docker-compose.yml...');

        $composeGenerated = $this->generateRedisDockerComposeFile(
            $appPath,
            $appName,
            $port,
            $password
        );

        if (! $composeGenerated) {
            error('Failed to generate docker-compose.yml');

            return null;
        }

        // =====================================================================
        // START CONTAINER
        // =====================================================================

        info('Starting Redis container...');

        $started = spin(
            callback: fn (): bool => $this->startDockerContainers($appPath),
            message: 'Starting Redis container...'
        );

        if (! $started) {
            error('Failed to start Redis container');

            return null;
        }

        // =====================================================================
        // WAIT FOR REDIS TO BE READY
        // =====================================================================

        info('Waiting for Redis to be ready...');

        $ready = spin(
            callback: fn (): bool => $this->waitForDockerService($appPath, 'redis', 30),
            message: 'Waiting for Redis...'
        );

        if (! $ready) {
            warning('Redis may not be fully ready. You may need to wait a moment before using it.');
        } else {
            info('✓ Redis is ready!');
        }

        // =====================================================================
        // RETURN CONFIGURATION
        // =====================================================================

        info('✓ Docker Redis setup complete!');
        info("Redis connection: localhost:{$port}");
        info("Redis password: {$password}");

        return [
            'redis_host' => 'localhost',
            'redis_port' => $port,
            'redis_password' => $password,
            'using_docker' => true,
        ];
    }

    /**
     * Generate docker-compose.yml file from template.
     *
     * Reads the Redis template file, replaces placeholders with actual values,
     * and writes the docker-compose.yml file to the application directory.
     * If a docker-compose.yml already exists, it appends the Redis service.
     *
     * Template placeholders:
     * - {{CONTAINER_PREFIX}}: phphive-{app-name}
     * - {{VOLUME_PREFIX}}: phphive-{app-name}
     * - {{NETWORK_NAME}}: phphive-{app-name}
     * - {{REDIS_PORT}}: Redis port (6379)
     * - {{REDIS_PASSWORD}}: Redis password
     *
     * @param  string $appPath  Application directory path
     * @param  string $appName  Application name
     * @param  int    $port     Redis port
     * @param  string $password Redis password
     * @return bool   True on success, false on failure
     */
    protected function generateRedisDockerComposeFile(
        string $appPath,
        string $appName,
        int $port,
        string $password
    ): bool {
        // Get template path
        $templatePath = dirname(__DIR__, 2) . '/stubs/docker/redis.yml';

        if (! $this->filesystem()->exists($templatePath)) {
            return false;
        }

        // Read template using Filesystem
        try {
            $template = $this->filesystem()->read($templatePath);
        } catch (RuntimeException) {
            return false;
        }

        // Normalize app name for container/volume names
        $normalizedName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $appName) ?? $appName);

        // Replace placeholders
        $replacements = [
            '{{CONTAINER_PREFIX}}' => "phphive-{$normalizedName}",
            '{{VOLUME_PREFIX}}' => "phphive-{$normalizedName}",
            '{{NETWORK_NAME}}' => "phphive-{$normalizedName}",
            '{{REDIS_PORT}}' => (string) $port,
            '{{REDIS_PASSWORD}}' => $password,
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Write docker-compose.yml using Filesystem
        $outputPath = $appPath . '/docker-compose.yml';

        try {
            $this->filesystem()->write($outputPath, $content);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Set up Redis using local installation.
     *
     * Falls back to local Redis setup when Docker is not available
     * or user prefers local installation. Prompts for connection details
     * and password configuration.
     *
     * Process:
     * 1. Display informational note about local setup
     * 2. Check if user wants to configure manually or use defaults
     * 3. Prompt for Redis connection details
     * 4. Prompt for password (if configured)
     * 5. Return configuration array
     *
     * Local installation requirements:
     * - Redis must be installed and running
     * - Default port: 6379
     * - Password is optional but recommended
     *
     * Installation guidance:
     * - macOS: brew install redis
     * - Linux: apt-get install redis-server / yum install redis
     * - Windows: Download from GitHub releases or use WSL
     *
     * @param  string $appName Application name
     * @return array  Redis configuration array
     */
    protected function setupLocalRedis(string $appName): array
    {
        note(
            'Setting up local Redis. Ensure Redis is installed and running.',
            'Local Redis Setup'
        );

        // Check if user wants automatic configuration
        $autoConfig = confirm(
            label: 'Is Redis already running locally?',
            default: false
        );

        if (! $autoConfig) {
            // Provide installation guidance
            $this->provideRedisInstallationGuidance();

            info('After installing and starting Redis, please configure the connection details.');
        }

        // Prompt for manual configuration
        return $this->promptRedisConfiguration($appName);
    }

    /**
     * Provide Redis installation guidance based on operating system.
     *
     * Displays helpful information and instructions for installing Redis
     * on the user's operating system. Includes download links, installation
     * methods, and verification steps.
     *
     * Installation guidance by OS:
     *
     * macOS:
     * - Homebrew installation (recommended)
     * - Verification and startup commands
     *
     * Linux:
     * - Package manager installation
     * - Systemd service setup
     *
     * Windows:
     * - WSL installation (recommended)
     * - Native Windows port
     */
    protected function provideRedisInstallationGuidance(): void
    {

        $os = $this->detectOS();

        note(
            'Redis is not running. Redis provides high-performance caching and session storage.',
            'Redis Not Available'
        );

        match ($os) {
            'macos' => $this->provideMacOSRedisGuidance(),
            'linux' => $this->provideLinuxRedisGuidance(),
            'windows' => $this->provideWindowsRedisGuidance(),
            default => $this->provideGenericRedisGuidance(),
        };
    }

    /**
     * Provide macOS-specific Redis installation guidance.
     */
    protected function provideMacOSRedisGuidance(): void
    {
        info('macOS Installation:');
        info('');
        info('Homebrew (Recommended):');
        info('  brew install redis');
        info('  brew services start redis');
        info('');
        info('After installation:');
        info('  1. Redis will start automatically');
        info('  2. Verify with: redis-cli ping');
        info('  3. Documentation: https://redis.io/docs');
    }

    /**
     * Provide Linux-specific Redis installation guidance.
     */
    protected function provideLinuxRedisGuidance(): void
    {
        info('Linux Installation:');
        info('');
        info('Ubuntu/Debian:');
        info('  sudo apt-get update');
        info('  sudo apt-get install redis-server');
        info('  sudo systemctl start redis-server');
        info('');
        info('RHEL/CentOS:');
        info('  sudo yum install redis');
        info('  sudo systemctl start redis');
        info('');
        info('After installation:');
        info('  1. Verify with: redis-cli ping');
        info('  2. Documentation: https://redis.io/docs');
    }

    /**
     * Provide Windows-specific Redis installation guidance.
     */
    protected function provideWindowsRedisGuidance(): void
    {
        info('Windows Installation:');
        info('');
        info('Option 1: WSL (Recommended):');
        info('  1. Install WSL2');
        info('  2. Follow Linux installation steps');
        info('');
        info('Option 2: Native Windows Port:');
        info('  1. Download from: https://github.com/microsoftarchive/redis/releases');
        info('  2. Extract and run redis-server.exe');
        info('');
        info('After installation:');
        info('  1. Verify with: redis-cli ping');
        info('  2. Documentation: https://redis.io/docs');
    }

    /**
     * Provide generic Redis installation guidance.
     */
    protected function provideGenericRedisGuidance(): void
    {
        info('Redis Installation:');
        info('');
        info('Visit the official Redis documentation:');
        info('  https://redis.io/docs/getting-started/installation/');
        info('');
        info('After installation, verify with:');
        info('  redis-cli ping');
    }

    /**
     * Prompt user for manual Redis configuration.
     *
     * This method provides configuration prompts when automatic setup
     * is not available or desired. It prompts the user to enter Redis
     * connection details for an existing installation.
     *
     * Use cases:
     * - User prefers manual configuration
     * - Automatic setup failed
     * - Redis already running
     * - Using remote Redis server
     * - Using managed Redis service
     *
     * Interactive prompts:
     * 1. Redis host (default: localhost)
     * 2. Redis port (default: 6379)
     * 3. Redis password (optional)
     *
     * Return value structure:
     * ```php
     * [
     *     'redis_host' => 'localhost',
     *     'redis_port' => 6379,
     *     'redis_password' => 'password',
     *     'using_docker' => false,
     * ]
     * ```
     *
     * Non-interactive mode:
     * - Returns defaults for all values
     * - Host: localhost, Port: 6379
     * - Password: empty
     *
     * @param  string $appName Application name (used for context)
     * @return array  Redis configuration array with user-provided values
     */
    protected function promptRedisConfiguration(string $appName): array
    {
        // Check if running in non-interactive mode
        if (! $this->input->isInteractive()) {
            // Return defaults for non-interactive mode
            return [
                'redis_host' => 'localhost',
                'redis_port' => 6379,
                'redis_password' => '',
                'using_docker' => false,
            ];
        }

        // Display informational note about manual configuration
        note(
            'Please enter the connection details for your Redis instance.',
            'Manual Redis Configuration'
        );

        // =====================================================================
        // REDIS CONNECTION DETAILS
        // =====================================================================

        // Prompt for Redis host
        $host = text(
            label: 'Redis host',
            placeholder: 'localhost',
            default: 'localhost',
            required: true,
            hint: 'The Redis server hostname or IP address'
        );

        // Prompt for Redis port
        $portInput = text(
            label: 'Redis port',
            placeholder: '6379',
            default: '6379',
            required: true,
            hint: 'The Redis server port number'
        );
        $port = (int) $portInput;

        // =====================================================================
        // PASSWORD CONFIGURATION
        // =====================================================================

        $hasPassword = confirm(
            label: 'Does Redis require a password?',
            default: false,
            hint: 'Redis can run with or without password authentication'
        );

        $redisPassword = '';
        if ($hasPassword) {
            $redisPassword = password(
                label: 'Redis password',
                required: true,
                hint: 'Enter the Redis password'
            );
        }

        // Return configuration
        return [
            'redis_host' => $host,
            'redis_port' => $port,
            'redis_password' => $redisPassword,
            'using_docker' => false,
        ];
    }
}
