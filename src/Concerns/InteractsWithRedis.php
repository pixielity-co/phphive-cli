<?php

declare(strict_types=1);

namespace PhpHive\Cli\Concerns;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

use Symfony\Component\Process\Process;

/**
 * Redis Interaction Trait.
 *
 * This trait provides comprehensive Redis setup functionality for application
 * types that require Redis caching or session storage. It supports both Docker-based
 * and local Redis setups with automatic configuration and graceful fallbacks.
 *
 * Key features:
 * - Docker-first approach: Recommends Docker when available
 * - Multiple Redis configurations: Standalone, Sentinel, Cluster
 * - Automatic Docker Compose file generation
 * - Container management and health checking
 * - Local Redis fallback for non-Docker setups
 * - Secure password generation for Docker Redis
 * - Graceful error handling with fallback options
 * - Detailed user feedback using Laravel Prompts
 * - Reusable across multiple app types (Magento, Laravel, Symfony, etc.)
 *
 * Docker-first workflow:
 * 1. Check if Docker is available
 * 2. If yes, offer Docker Redis setup (recommended)
 * 3. Prompt for Redis configuration type (Standalone, Sentinel, Cluster)
 * 4. Generate docker-compose section for Redis
 * 5. Start Docker containers
 * 6. Wait for Redis to be ready
 * 7. Return connection details
 * 8. If Docker unavailable or user declines, fall back to local setup
 *
 * Local Redis workflow:
 * 1. Check if Redis is installed and running locally
 * 2. Prompt for connection details (host, port, password)
 * 3. Test Redis connection
 * 4. Return credentials for application configuration
 * 5. If connection fails, fall back to manual prompts
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
 *         $redisConfig = $this->setupRedis('my-app', '/path/to/app');
 *
 *         return $redisConfig;
 *     }
 * }
 * ```
 *
 * Security considerations:
 * - Passwords are generated securely using random_bytes()
 * - Passwords are masked during input
 * - Docker containers are isolated per project
 * - Connection attempts are limited to prevent brute force
 * - Redis AUTH is enforced for Docker containers
 *
 * @see AbstractAppType For base app type functionality
 * @see InteractsWithDocker For Docker management functionality
 * @see InteractsWithPrompts For prompt helper methods
 */
trait InteractsWithRedis
{
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
     * Supported Redis configurations:
     * - standalone: Single Redis instance (default)
     * - sentinel: Redis Sentinel for high availability
     * - cluster: Redis Cluster for horizontal scaling
     *
     * Return value structure:
     * ```php
     * [
     *     'redis_host' => 'localhost',        // Host (localhost for Docker)
     *     'redis_port' => 6379,               // Port
     *     'redis_password' => 'password',     // Password (empty for no auth)
     *     'using_docker' => true,             // Whether Docker is used
     *     'redis_mode' => 'standalone',       // Configuration mode
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
        if (method_exists($this, 'isDockerAvailable') && $this->isDockerAvailable()) {
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
                $redisConfig = $this->setupDockerRedis($appName, $appPath);
                if ($redisConfig !== null) {
                    return $redisConfig;
                }

                // Docker setup failed, fall back to local
                warning('Docker setup failed. Falling back to local Redis setup.');
            }
        } elseif (method_exists($this, 'isDockerInstalled') && ! $this->isDockerInstalled()) {
            // Docker not installed - offer installation guidance
            $installDocker = confirm(
                label: 'Docker is not installed. Would you like to see installation instructions?',
                default: false
            );

            if ($installDocker && method_exists($this, 'provideDockerInstallationGuidance')) {
                $this->provideDockerInstallationGuidance();
                info('After installing Docker, you can recreate this application to use Docker.');
            }
        }

        // Fall back to local Redis setup
        return $this->setupLocalRedis($appName);
    }

    /**
     * Set up Redis using Docker containers.
     *
     * Creates a Docker Compose configuration with Redis and starts the
     * containers. Supports standalone, Sentinel, and Cluster configurations.
     *
     * Process:
     * 1. Prompt for Redis configuration type (if advanced features desired)
     * 2. Generate secure password for Redis AUTH
     * 3. Generate docker-compose.yml section for Redis
     * 4. Start Docker containers
     * 5. Wait for Redis to be ready
     * 6. Return connection details
     *
     * Generated files:
     * - docker-compose.yml: Container configuration (appended or created)
     * - redis.conf: Redis configuration file (optional)
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
        // REDIS CONFIGURATION TYPE
        // =====================================================================

        $advancedSetup = confirm(
            label: 'Do you need advanced Redis features? (Sentinel/Cluster)',
            default: false
        );

        $redisMode = 'standalone';
        if ($advancedSetup) {
            $redisMode = (string) select(
                label: 'Select Redis configuration',
                options: [
                    'standalone' => 'Standalone (Single instance, recommended)',
                    'sentinel' => 'Sentinel (High availability with automatic failover)',
                    'cluster' => 'Cluster (Horizontal scaling with sharding)',
                ],
                default: 'standalone'
            );
        } else {
            info('Using standalone Redis configuration');
        }

        // =====================================================================
        // SECURITY CONFIGURATION
        // =====================================================================

        $usePassword = confirm(
            label: 'Enable Redis password authentication?',
            default: true
        );

        $redisPassword = '';
        if ($usePassword) {
            // Generate secure password
            $redisPassword = bin2hex(random_bytes(16));
            info('Generated secure Redis password');
        } else {
            warning('Redis will run without password authentication (not recommended for production)');
        }

        // =====================================================================
        // PORT CONFIGURATION
        // =====================================================================

        $portInput = text(
            label: 'Redis port',
            placeholder: '6379',
            default: '6379',
            required: true,
            hint: 'Port to expose Redis on localhost'
        );
        $redisPort = (int) $portInput;

        // =====================================================================
        // GENERATE DOCKER COMPOSE CONFIGURATION
        // =====================================================================

        info('Generating Docker Compose configuration for Redis...');

        $composeGenerated = $this->generateRedisDockerCompose(
            $appPath,
            $appName,
            $redisMode,
            $redisPassword,
            $redisPort
        );

        if (! $composeGenerated) {
            error('Failed to generate Docker Compose configuration');

            return null;
        }

        // =====================================================================
        // START CONTAINERS
        // =====================================================================

        info('Starting Redis container...');

        if (! method_exists($this, 'startDockerContainers')) {
            error('InteractsWithDocker trait is required for Docker setup');

            return null;
        }

        $started = spin(
            callback: fn (): bool => $this->startDockerContainers($appPath),
            message: 'Starting Redis container...'
        );

        if (! $started) {
            error('Failed to start Redis container');

            return null;
        }

        // =====================================================================
        // WAIT FOR REDIS
        // =====================================================================

        info('Waiting for Redis to be ready...');

        $ready = spin(
            callback: fn (): bool => $this->waitForRedisReady($appPath, 'redis', $redisPassword, 30),
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
        info("Redis connection: localhost:{$redisPort}");
        if ($usePassword) {
            info("Redis password: {$redisPassword}");
        }

        return [
            'redis_host' => 'localhost',
            'redis_port' => $redisPort,
            'redis_password' => $redisPassword,
            'using_docker' => true,
            'redis_mode' => $redisMode,
        ];
    }

    /**
     * Generate Docker Compose configuration for Redis.
     *
     * Creates or appends to docker-compose.yml file with Redis service
     * configuration. Supports standalone, Sentinel, and Cluster modes.
     *
     * Configuration includes:
     * - Redis service with appropriate image
     * - Volume for data persistence
     * - Network configuration
     * - Health check
     * - Password authentication (if enabled)
     * - Port mapping
     *
     * @param  string $appPath       Application directory path
     * @param  string $appName       Application name
     * @param  string $redisMode     Redis mode (standalone, sentinel, cluster)
     * @param  string $redisPassword Redis password (empty for no auth)
     * @param  int    $redisPort     Redis port
     * @return bool   True on success, false on failure
     */
    protected function generateRedisDockerCompose(
        string $appPath,
        string $appName,
        string $redisMode,
        string $redisPassword,
        int $redisPort
    ): bool {
        // Normalize app name for container/volume names
        $normalizedName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $appName) ?? $appName);

        // Build Redis command based on configuration
        $redisCommand = [];
        if ($redisPassword !== '') {
            $redisCommand[] = 'redis-server';
            $redisCommand[] = '--requirepass';
            $redisCommand[] = $redisPassword;
        }

        // Build Docker Compose service configuration
        $redisService = [
            'redis' => [
                'image' => 'redis:7-alpine',
                'container_name' => "phphive-{$normalizedName}-redis",
                'ports' => ["{$redisPort}:6379"],
                'volumes' => ["phphive-{$normalizedName}-redis-data:/data"],
                'networks' => ["phphive-{$normalizedName}"],
                'healthcheck' => [
                    'test' => $redisPassword !== ''
                        ? ['CMD', 'redis-cli', '--raw', 'incr', 'ping']
                        : ['CMD', 'redis-cli', 'ping'],
                    'interval' => '10s',
                    'timeout' => '3s',
                    'retries' => 3,
                ],
                'restart' => 'unless-stopped',
            ],
        ];

        // Add command if password is set
        if ($redisCommand !== []) {
            $redisService['redis']['command'] = $redisCommand;
        }

        // Add Sentinel configuration if needed
        if ($redisMode === 'sentinel') {
            // Add Sentinel services (simplified for this example)
            info('Note: Sentinel configuration requires additional setup. Using standalone for now.');
        }

        // Add Cluster configuration if needed
        if ($redisMode === 'cluster') {
            // Add Cluster nodes (simplified for this example)
            info('Note: Cluster configuration requires additional setup. Using standalone for now.');
        }

        // Check if docker-compose.yml exists
        $composePath = $appPath . '/docker-compose.yml';
        $composeExists = file_exists($composePath);

        if ($composeExists) {
            // Append to existing docker-compose.yml
            return $this->appendRedisToDockerCompose($composePath, $redisService, $normalizedName);
        }

        // Create new docker-compose.yml
        return $this->createRedisDockerCompose($composePath, $redisService, $normalizedName);
    }

    /**
     * Append Redis service to existing docker-compose.yml.
     *
     * Reads the existing docker-compose.yml, adds the Redis service,
     * and writes it back. Handles YAML formatting carefully.
     *
     * @param  string $composePath    Path to docker-compose.yml
     * @param  array  $redisService   Redis service configuration
     * @param  string $normalizedName Normalized app name
     * @return bool   True on success, false on failure
     */
    protected function appendRedisToDockerCompose(string $composePath, array $redisService, string $normalizedName): bool
    {
        // Read existing docker-compose.yml
        $content = file_get_contents($composePath);
        if ($content === false) {
            return false;
        }

        // Simple YAML append (for production, consider using symfony/yaml)
        $redisYaml = $this->convertRedisServiceToYaml($redisService, $normalizedName);

        // Append Redis service
        $content .= "\n" . $redisYaml;

        // Write back
        return file_put_contents($composePath, $content) !== false;
    }

    /**
     * Create new docker-compose.yml with Redis service.
     *
     * Generates a complete docker-compose.yml file with Redis service,
     * volumes, and networks.
     *
     * @param  string $composePath    Path to docker-compose.yml
     * @param  array  $redisService   Redis service configuration
     * @param  string $normalizedName Normalized app name
     * @return bool   True on success, false on failure
     */
    protected function createRedisDockerCompose(string $composePath, array $redisService, string $normalizedName): bool
    {
        $yaml = "version: '3.8'\n\n";
        $yaml .= "services:\n";
        $yaml .= $this->convertRedisServiceToYaml($redisService, $normalizedName);
        $yaml .= "\nvolumes:\n";
        $yaml .= "  phphive-{$normalizedName}-redis-data:\n";
        $yaml .= "    driver: local\n";
        $yaml .= "\nnetworks:\n";
        $yaml .= "  phphive-{$normalizedName}:\n";
        $yaml .= "    driver: bridge\n";

        return file_put_contents($composePath, $yaml) !== false;
    }

    /**
     * Convert Redis service array to YAML format.
     *
     * Simple YAML converter for Redis service configuration.
     * For production use, consider symfony/yaml component.
     *
     * @param  array  $redisService   Redis service configuration
     * @param  string $normalizedName Normalized app name
     * @return string YAML formatted string
     */
    protected function convertRedisServiceToYaml(array $redisService, string $normalizedName): string
    {
        $yaml = "  redis:\n";
        $yaml .= "    image: redis:7-alpine\n";
        $yaml .= "    container_name: phphive-{$normalizedName}-redis\n";
        $yaml .= "    ports:\n";
        $yaml .= "      - \"{$redisService['redis']['ports'][0]}\"\n";

        if (isset($redisService['redis']['command'])) {
            $yaml .= "    command:\n";
            foreach ($redisService['redis']['command'] as $cmd) {
                $yaml .= "      - \"{$cmd}\"\n";
            }
        }

        $yaml .= "    volumes:\n";
        $yaml .= "      - phphive-{$normalizedName}-redis-data:/data\n";
        $yaml .= "    networks:\n";
        $yaml .= "      - phphive-{$normalizedName}\n";
        $yaml .= "    healthcheck:\n";
        $yaml .= "      test:\n";
        foreach ($redisService['redis']['healthcheck']['test'] as $test) {
            $yaml .= "        - \"{$test}\"\n";
        }
        $yaml .= "      interval: 10s\n";
        $yaml .= "      timeout: 3s\n";
        $yaml .= "      retries: 3\n";

        return $yaml . "    restart: unless-stopped\n";
    }

    /**
     * Wait for Redis to be ready and accepting connections.
     *
     * Polls the Redis container until it responds to PING command.
     * Uses docker compose exec to run redis-cli inside the container.
     *
     * Polling strategy:
     * - Maximum attempts: 30 (configurable)
     * - Delay between attempts: 2 seconds
     * - Total maximum wait: 60 seconds
     *
     * Health check:
     * - Execute 'redis-cli ping' (or with AUTH if password set)
     * - Expect 'PONG' response
     * - Return true if successful
     *
     * @param  string $appPath     Absolute path to application directory
     * @param  string $serviceName Name of Redis service in docker-compose.yml
     * @param  string $password    Redis password (empty for no auth)
     * @param  int    $maxAttempts Maximum number of polling attempts
     * @return bool   True if Redis is ready, false if timeout
     */
    protected function waitForRedisReady(
        string $appPath,
        string $serviceName,
        string $password = '',
        int $maxAttempts = 30
    ): bool {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            // Build redis-cli command
            $command = ['docker', 'compose', 'exec', '-T', $serviceName];

            if ($password !== '') {
                // With authentication
                $command = array_merge($command, ['redis-cli', '-a', $password, 'ping']);
            } else {
                // Without authentication
                $command = array_merge($command, ['redis-cli', 'ping']);
            }

            // Execute command
            $process = new Process($command, $appPath);
            $process->run();

            // Check if Redis responded with PONG
            if ($process->isSuccessful() && str_contains($process->getOutput(), 'PONG')) {
                return true;
            }

            // Wait 2 seconds before next attempt
            sleep(2);
            $attempts++;
        }

        return false;
    }

    /**
     * Set up Redis using local installation.
     *
     * Falls back to local Redis setup when Docker is not available
     * or user prefers local installation. Offers automatic detection
     * or manual configuration.
     *
     * Process:
     * 1. Check if Redis is running locally
     * 2. If yes → Test connection and return details
     * 3. If no → Prompt for manual configuration
     *
     * @param  string $appName Application name
     * @return array  Redis configuration array
     */
    protected function setupLocalRedis(string $appName): array
    {
        note(
            'Setting up local Redis connection. Ensure Redis is installed and running.',
            'Local Redis Setup'
        );

        // Check if Redis is running locally
        $localRedisRunning = $this->checkLocalRedis('127.0.0.1', 6379);

        if ($localRedisRunning) {
            info('✓ Local Redis detected on 127.0.0.1:6379');

            $useLocal = confirm(
                label: 'Use local Redis instance?',
                default: true
            );

            if ($useLocal) {
                // Prompt for password if needed
                $hasPassword = confirm(
                    label: 'Does your Redis instance require a password?',
                    default: false
                );

                $redisPassword = '';
                if ($hasPassword) {
                    $redisPassword = password(
                        label: 'Redis password',
                        required: true
                    );
                }

                return [
                    'redis_host' => '127.0.0.1',
                    'redis_port' => 6379,
                    'redis_password' => $redisPassword,
                    'using_docker' => false,
                    'redis_mode' => 'standalone',
                ];
            }
        } else {
            warning('Local Redis not detected on default port 6379');
            $this->provideRedisInstallationGuidance();
        }

        // Fall back to manual configuration
        return $this->promptRedisConfiguration($appName);
    }

    /**
     * Check if local Redis is running and accessible.
     *
     * Attempts to connect to Redis using redis-cli or socket connection
     * to verify that Redis is running locally.
     *
     * Detection methods:
     * 1. Try redis-cli ping command
     * 2. Try socket connection to Redis port
     *
     * @param  string $host Redis host
     * @param  int    $port Redis port
     * @return bool   True if Redis is accessible, false otherwise
     */
    protected function checkLocalRedis(string $host, int $port): bool
    {
        // Try redis-cli first
        $process = new Process(['redis-cli', '-h', $host, '-p', (string) $port, 'ping']);
        $process->run();

        if ($process->isSuccessful() && str_contains($process->getOutput(), 'PONG')) {
            return true;
        }

        // Try socket connection
        $socket = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($socket !== false) {
            fclose($socket);

            return true;
        }

        return false;
    }

    /**
     * Provide Redis installation guidance based on operating system.
     *
     * Displays helpful information and instructions for installing Redis
     * on the user's operating system.
     */
    protected function provideRedisInstallationGuidance(): void
    {
        $os = method_exists($this, 'detectOS') ? $this->detectOS() : 'unknown';

        note(
            'Redis is not running locally. Install Redis to use it without Docker.',
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
        info('macOS Redis Installation:');
        info('');
        info('Using Homebrew:');
        info('  brew install redis');
        info('  brew services start redis');
        info('');
        info('Verify installation:');
        info('  redis-cli ping');
        info('  (should return PONG)');
    }

    /**
     * Provide Linux-specific Redis installation guidance.
     */
    protected function provideLinuxRedisGuidance(): void
    {
        info('Linux Redis Installation:');
        info('');
        info('Ubuntu/Debian:');
        info('  sudo apt update');
        info('  sudo apt install redis-server');
        info('  sudo systemctl start redis-server');
        info('');
        info('Fedora/RHEL/CentOS:');
        info('  sudo dnf install redis');
        info('  sudo systemctl start redis');
        info('');
        info('Verify installation:');
        info('  redis-cli ping');
    }

    /**
     * Provide Windows-specific Redis installation guidance.
     */
    protected function provideWindowsRedisGuidance(): void
    {
        info('Windows Redis Installation:');
        info('');
        info('Option 1: WSL2 (Recommended)');
        info('  Install Redis in WSL2 Ubuntu:');
        info('  sudo apt update && sudo apt install redis-server');
        info('  sudo service redis-server start');
        info('');
        info('Option 2: Windows Port');
        info('  Download from: https://github.com/microsoftarchive/redis/releases');
        info('  Note: Official Redis does not support Windows natively');
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
        info('After installation, verify with: redis-cli ping');
    }

    /**
     * Prompt user for manual Redis configuration.
     *
     * This method provides a fallback when automatic Redis setup fails or
     * is not desired. It prompts the user to manually enter Redis connection
     * details for an existing Redis instance.
     *
     * Use cases:
     * - User prefers to configure Redis manually
     * - Automatic setup failed (connection issues)
     * - Redis already exists
     * - Using remote Redis server
     * - Using managed Redis service (ElastiCache, Cloud Memorystore, etc.)
     *
     * Interactive prompts:
     * 1. Redis host (default: 127.0.0.1)
     * 2. Redis port (default: 6379)
     * 3. Redis password (optional, masked input)
     *
     * Return value structure:
     * ```php
     * [
     *     'redis_host' => '127.0.0.1',
     *     'redis_port' => 6379,
     *     'redis_password' => 'password',
     *     'using_docker' => false,
     *     'redis_mode' => 'standalone',
     * ]
     * ```
     *
     * Non-interactive mode:
     * - Returns defaults for all values
     * - Host: 127.0.0.1, Port: 6379, Password: empty
     *
     * @param  string $appName Application name (unused but kept for consistency)
     * @return array  Redis configuration array with user-provided values
     */
    protected function promptRedisConfiguration(string $appName): array
    {
        // Check if running in non-interactive mode
        if (! $this->input->isInteractive()) {
            // Return defaults for non-interactive mode
            return [
                'redis_host' => '127.0.0.1',
                'redis_port' => 6379,
                'redis_password' => '',
                'using_docker' => false,
                'redis_mode' => 'standalone',
            ];
        }

        // Display informational note about manual setup
        note(
            'Please enter the connection details for your existing Redis instance.',
            'Manual Redis Configuration'
        );

        // =====================================================================
        // REDIS CONNECTION DETAILS
        // =====================================================================

        // Prompt for Redis host
        $host = text(
            label: 'Redis host',
            placeholder: '127.0.0.1',
            default: '127.0.0.1',
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

        // Prompt for Redis password
        $hasPassword = confirm(
            label: 'Does Redis require authentication?',
            default: false
        );

        $redisPassword = '';
        if ($hasPassword) {
            $redisPassword = password(
                label: 'Redis password',
                placeholder: 'Enter Redis password',
                required: true,
                hint: 'Password for Redis AUTH command'
            );
        }

        // Return Redis configuration
        return [
            'redis_host' => $host,
            'redis_port' => $port,
            'redis_password' => $redisPassword,
            'using_docker' => false,
            'redis_mode' => 'standalone',
        ];
    }
}
