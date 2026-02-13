<?php

declare(strict_types=1);

namespace PhpHive\Cli\DTOs\Infrastructure;

use PhpHive\Cli\Enums\QueueDriver;

/**
 * Queue Configuration Data Transfer Object.
 *
 * Encapsulates all queue configuration data in a type-safe, immutable structure.
 * Used to pass queue configuration between services and components.
 *
 * Supported queue drivers:
 * - SYNC: Synchronous processing (no queue)
 * - REDIS: Redis-based queue
 * - RABBITMQ: RabbitMQ message broker
 * - SQS: Amazon SQS cloud service
 *
 * Example usage:
 * ```php
 * $config = new QueueConfig(
 *     driver: QueueDriver::RABBITMQ,
 *     host: 'localhost',
 *     port: 5672,
 *     user: 'guest',
 *     password: 'secret',
 *     vhost: '/',
 *     usingDocker: true
 * );
 *
 * $array = $config->toArray();
 * $restored = QueueConfig::fromArray($array);
 * ```
 */
final readonly class QueueConfig
{
    /**
     * Create a new queue configuration instance.
     *
     * @param QueueDriver $driver         Queue driver type
     * @param string|null $host           Queue server host
     * @param int|null    $port           Queue server port
     * @param string|null $user           Queue server username
     * @param string|null $password       Queue server password
     * @param string|null $vhost          RabbitMQ virtual host
     * @param bool        $usingDocker    Whether Docker is being used
     * @param string|null $region         AWS region (for SQS)
     * @param string|null $prefix         Queue name prefix (for SQS)
     * @param string|null $connection     Redis connection name
     * @param int|null    $managementPort RabbitMQ management UI port
     */
    public function __construct(
        public QueueDriver $driver,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $user = null,
        public ?string $password = null,
        public ?string $vhost = null,
        public bool $usingDocker = false,
        public ?string $region = null,
        public ?string $prefix = null,
        public ?string $connection = null,
        public ?int $managementPort = null,
    ) {}

    /**
     * Create configuration from array.
     *
     * Reconstructs a QueueConfig instance from an array representation,
     * typically from application configuration or storage.
     *
     * @param  array<string, mixed> $data Configuration array
     * @return self                 New QueueConfig instance
     */
    public static function fromArray(array $data): self
    {
        $driver = isset($data['queue_driver'])
            ? QueueDriver::from($data['queue_driver'])
            : QueueDriver::SYNC;

        return new self(
            driver: $driver,
            host: $data['queue_host'] ?? null,
            port: isset($data['queue_port']) ? (int) $data['queue_port'] : null,
            user: $data['queue_user'] ?? null,
            password: $data['queue_password'] ?? null,
            vhost: $data['queue_vhost'] ?? null,
            usingDocker: (bool) ($data['using_docker'] ?? false),
            region: $data['queue_region'] ?? null,
            prefix: $data['queue_prefix'] ?? null,
            connection: $data['queue_connection'] ?? null,
            managementPort: isset($data['rabbitmq_management_port']) ? (int) $data['rabbitmq_management_port'] : null,
        );
    }

    /**
     * Convert configuration to array format.
     *
     * Returns an array representation suitable for merging with application
     * configuration or storing in configuration files.
     *
     * @return array<string, mixed> Configuration array
     */
    public function toArray(): array
    {
        $config = [
            'queue_driver' => $this->driver->value,
            'using_docker' => $this->usingDocker,
        ];

        if ($this->host !== null) {
            $config['queue_host'] = $this->host;
        }

        if ($this->port !== null) {
            $config['queue_port'] = $this->port;
        }

        if ($this->user !== null) {
            $config['queue_user'] = $this->user;
        }

        if ($this->password !== null) {
            $config['queue_password'] = $this->password;
        }

        if ($this->vhost !== null) {
            $config['queue_vhost'] = $this->vhost;
        }

        if ($this->region !== null) {
            $config['queue_region'] = $this->region;
        }

        if ($this->prefix !== null) {
            $config['queue_prefix'] = $this->prefix;
        }

        if ($this->connection !== null) {
            $config['queue_connection'] = $this->connection;
        }

        if ($this->managementPort !== null) {
            $config['rabbitmq_management_port'] = $this->managementPort;
        }

        return $config;
    }
}
