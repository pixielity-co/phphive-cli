<?php

declare(strict_types=1);

namespace PhpHive\Cli\Services\Infrastructure;

use Illuminate\Support\Str;
use PhpHive\Cli\DTOs\Infrastructure\QueueConfig;
use PhpHive\Cli\Enums\QueueDriver;
use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Support\Process;
use Pixielity\StubGenerator\Exceptions\StubNotFoundException;
use Pixielity\StubGenerator\Facades\Stub;

/**
 * Queue Setup Service.
 *
 * Handles the business logic for setting up queue infrastructure including
 * Docker-based and local queue services. Supports Redis, RabbitMQ, and SQS.
 *
 * This service encapsulates all queue setup logic that was previously in
 * the InteractsWithQueue trait, providing a cleaner separation of concerns
 * and better testability.
 *
 * Supported queue backends:
 * - Redis: Lightweight, fast queue (Docker or local)
 * - RabbitMQ: Full-featured message broker (Docker or local)
 * - Amazon SQS: Managed cloud queue service
 *
 * Example usage:
 * ```php
 * $service = QueueSetupService::make($process, $filesystem);
 * $config = $service->setup($queueConfig, '/path/to/app');
 * ```
 */
final readonly class QueueSetupService
{
    /**
     * Create a new queue setup service instance.
     *
     * @param Process    $process    Process service for command execution
     * @param Filesystem $filesystem Filesystem service for file operations
     */
    public function __construct(
        private Process $process,
        private Filesystem $filesystem,
    ) {}

    /**
     * Setup queue infrastructure based on configuration.
     *
     * Main entry point for queue setup. Determines whether to use Docker
     * or local setup based on the configuration.
     *
     * @param  QueueConfig $queueConfig Queue configuration
     * @param  string      $appPath     Absolute path to application directory
     * @return QueueConfig Updated configuration with connection details
     */
    public function setup(QueueConfig $queueConfig, string $appPath): QueueConfig
    {
        // If sync driver, no setup needed
        if ($queueConfig->driver === QueueDriver::SYNC) {
            return $queueConfig;
        }

        // If using Docker, setup Docker containers
        if ($queueConfig->usingDocker) {
            $dockerConfig = $this->setupDocker($queueConfig, $appPath);
            if ($dockerConfig instanceof QueueConfig) {
                return $dockerConfig;
            }
        }

        // Fall back to local setup
        return $this->setupLocal($queueConfig);
    }

    /**
     * Create a new instance using static factory pattern.
     *
     * @param  Process    $process    Process service
     * @param  Filesystem $filesystem Filesystem service
     * @return self       New service instance
     */
    public static function make(Process $process, Filesystem $filesystem): self
    {
        return new self($process, $filesystem);
    }

    /**
     * Setup queue using Docker containers.
     *
     * Creates Docker Compose configuration and starts containers for
     * the specified queue backend.
     *
     * @param  QueueConfig      $queueConfig Queue configuration
     * @param  string           $appPath     Absolute path to application directory
     * @return QueueConfig|null Updated configuration or null on failure
     */
    public function setupDocker(QueueConfig $queueConfig, string $appPath): ?QueueConfig
    {
        return match ($queueConfig->driver) {
            QueueDriver::REDIS => $this->setupDockerRedis($queueConfig, $appPath),
            QueueDriver::RABBITMQ => $this->setupDockerRabbitMQ($queueConfig, $appPath),
            default => null,
        };
    }

    /**
     * Setup queue using local installation.
     *
     * Configures queue to use locally installed queue services.
     *
     * @param  QueueConfig $queueConfig Queue configuration
     * @return QueueConfig Updated configuration
     */
    public function setupLocal(QueueConfig $queueConfig): QueueConfig
    {
        return match ($queueConfig->driver) {
            QueueDriver::REDIS => $this->setupLocalRedis($queueConfig),
            QueueDriver::RABBITMQ => $this->setupLocalRabbitMQ($queueConfig),
            QueueDriver::SQS => $queueConfig, // SQS is always "local" (cloud-based)
            default => $queueConfig,
        };
    }

    /**
     * Setup Redis queue using Docker.
     *
     * @param  QueueConfig      $queueConfig Queue configuration
     * @param  string           $appPath     Application directory path
     * @return QueueConfig|null Updated configuration or null on failure
     */
    private function setupDockerRedis(QueueConfig $queueConfig, string $appPath): ?QueueConfig
    {
        // Generate docker-compose.yml for Redis
        $appName = basename($appPath);
        $generated = $this->generateRedisDockerComposeFile($appPath, $appName);

        if (! $generated) {
            return null;
        }

        // Start containers
        if (! $this->startDockerContainers($appPath)) {
            return null;
        }

        // Wait for Redis to be ready
        $this->waitForDockerService($appPath, 'redis', 30);

        return new QueueConfig(
            driver: QueueDriver::REDIS,
            host: $queueConfig->host ?? 'localhost',
            port: $queueConfig->port ?? 6379,
            password: $queueConfig->password ?? '',
            usingDocker: true,
            connection: 'default',
        );
    }

    /**
     * Setup RabbitMQ queue using Docker.
     *
     * @param  QueueConfig      $queueConfig Queue configuration
     * @param  string           $appPath     Application directory path
     * @return QueueConfig|null Updated configuration or null on failure
     */
    private function setupDockerRabbitMQ(QueueConfig $queueConfig, string $appPath): ?QueueConfig
    {
        // Generate docker-compose.yml for RabbitMQ
        $appName = basename($appPath);
        $generated = $this->generateRabbitMQDockerComposeFile(
            $appPath,
            $appName,
            $queueConfig->user ?? 'guest',
            $queueConfig->password ?? 'guest',
            $queueConfig->vhost ?? '/'
        );

        if (! $generated) {
            return null;
        }

        // Start containers
        if (! $this->startDockerContainers($appPath)) {
            return null;
        }

        // Wait for RabbitMQ to be ready
        $this->waitForDockerService($appPath, 'rabbitmq', 30);

        return new QueueConfig(
            driver: QueueDriver::RABBITMQ,
            host: $queueConfig->host ?? 'localhost',
            port: $queueConfig->port ?? 5672,
            user: $queueConfig->user ?? 'guest',
            password: $queueConfig->password ?? 'guest',
            vhost: $queueConfig->vhost ?? '/',
            usingDocker: true,
            managementPort: 15672,
        );
    }

    /**
     * Setup local Redis queue.
     *
     * @param  QueueConfig $queueConfig Queue configuration
     * @return QueueConfig Updated configuration
     */
    private function setupLocalRedis(QueueConfig $queueConfig): QueueConfig
    {
        return new QueueConfig(
            driver: QueueDriver::REDIS,
            host: $queueConfig->host ?? 'localhost',
            port: $queueConfig->port ?? 6379,
            password: $queueConfig->password ?? '',
            usingDocker: false,
            connection: 'default',
        );
    }

    /**
     * Setup local RabbitMQ queue.
     *
     * @param  QueueConfig $queueConfig Queue configuration
     * @return QueueConfig Updated configuration
     */
    private function setupLocalRabbitMQ(QueueConfig $queueConfig): QueueConfig
    {
        return new QueueConfig(
            driver: QueueDriver::RABBITMQ,
            host: $queueConfig->host ?? 'localhost',
            port: $queueConfig->port ?? 5672,
            user: $queueConfig->user ?? 'guest',
            password: $queueConfig->password ?? 'guest',
            vhost: $queueConfig->vhost ?? '/',
            usingDocker: false,
        );
    }

    /**
     * Generate docker-compose.yml file for Redis.
     *
     * @param  string $appPath Application directory path
     * @param  string $appName Application name
     * @return bool   True on success
     */
    private function generateRedisDockerComposeFile(string $appPath, string $appName): bool
    {
        try {
            Stub::setBasePath(dirname(__DIR__, 3) . '/stubs');

            $normalizedName = Str::lower(preg_replace('/[^a-zA-Z0-9]/', '-', $appName) ?? $appName);

            $outputPath = $appPath . '/docker-compose.yml';
            if ($this->filesystem->exists($outputPath)) {
                $existingContent = $this->filesystem->read($outputPath);
                if (str_contains($existingContent, 'redis:')) {
                    return true;
                }
            }

            Stub::create('docker/redis.yml', [
                'container_prefix' => "phphive-{$normalizedName}",
                'volume_prefix' => "phphive-{$normalizedName}",
                'network_name' => "phphive-{$normalizedName}",
            ])->saveTo($appPath, 'docker-compose.yml');

            return true;
        } catch (StubNotFoundException) {
            return false;
        }
    }

    /**
     * Generate docker-compose.yml file for RabbitMQ.
     *
     * @param  string $appPath  Application directory path
     * @param  string $appName  Application name
     * @param  string $user     RabbitMQ username
     * @param  string $password RabbitMQ password
     * @param  string $vhost    RabbitMQ virtual host
     * @return bool   True on success
     */
    private function generateRabbitMQDockerComposeFile(
        string $appPath,
        string $appName,
        string $user,
        string $password,
        string $vhost
    ): bool {
        try {
            Stub::setBasePath(dirname(__DIR__, 3) . '/stubs');

            $normalizedName = Str::lower(preg_replace('/[^a-zA-Z0-9]/', '-', $appName) ?? $appName);

            $outputPath = $appPath . '/docker-compose.yml';
            if ($this->filesystem->exists($outputPath)) {
                $existingContent = $this->filesystem->read($outputPath);
                if (str_contains($existingContent, 'rabbitmq:')) {
                    return true;
                }
            }

            Stub::create('docker/rabbitmq.yml', [
                'container_prefix' => "phphive-{$normalizedName}",
                'volume_prefix' => "phphive-{$normalizedName}",
                'network_name' => "phphive-{$normalizedName}",
                'rabbitmq_user' => $user,
                'rabbitmq_password' => $password,
                'rabbitmq_vhost' => $vhost,
                'rabbitmq_port' => '5672',
                'rabbitmq_management_port' => '15672',
            ])->saveTo($appPath, 'docker-compose.yml');

            return true;
        } catch (StubNotFoundException) {
            return false;
        }
    }

    /**
     * Start Docker containers using docker-compose.
     *
     * @param  string $appPath Application directory path
     * @return bool   True if containers started successfully
     */
    private function startDockerContainers(string $appPath): bool
    {
        return $this->process->succeeds(['docker', 'compose', 'up', '-d'], $appPath, 300);
    }

    /**
     * Wait for a Docker service to be ready.
     *
     * @param  string $appPath     Application directory path
     * @param  string $serviceName Service name in docker-compose.yml
     * @param  int    $maxAttempts Maximum number of polling attempts
     * @return bool   True if service is ready
     */
    private function waitForDockerService(string $appPath, string $serviceName, int $maxAttempts = 30): bool
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            if ($this->process->succeeds(
                ['docker', 'compose', 'exec', '-T', $serviceName, 'echo', 'ready'],
                $appPath
            )) {
                return true;
            }

            sleep(2);
            $attempts++;
        }

        return false;
    }
}
