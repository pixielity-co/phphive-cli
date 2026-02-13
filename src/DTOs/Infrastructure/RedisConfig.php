<?php

declare(strict_types=1);

namespace PhpHive\Cli\DTOs\Infrastructure;

use PhpHive\Cli\Contracts\AppTypeInterface;

/**
 * Redis Configuration DTO.
 *
 * Data Transfer Object for Redis connection configuration.
 * Encapsulates all Redis setup parameters in a type-safe structure.
 *
 * Properties:
 * - host: Redis server hostname or IP address
 * - port: Redis server port number
 * - password: Redis authentication password (optional)
 * - usingDocker: Whether Redis is running in Docker
 *
 * Example usage:
 * ```php
 * $config = new RedisConfig(
 *     host: 'localhost',
 *     port: 6379,
 *     password: 'secret',
 *     usingDocker: true
 * );
 *
 * // Convert to array for application configuration
 * $array = $config->toArray();
 *
 * // Create from array
 * $config = RedisConfig::fromArray($array);
 * ```
 */
final readonly class RedisConfig
{
    /**
     * Create a new Redis configuration instance.
     *
     * @param string $host        Redis server hostname or IP address
     * @param int    $port        Redis server port number
     * @param string $password    Redis authentication password (empty if none)
     * @param bool   $usingDocker Whether Redis is running in Docker
     */
    public function __construct(
        public string $host,
        public int $port,
        public string $password,
        public bool $usingDocker,
    ) {}

    /**
     * Create configuration from array.
     *
     * Factory method to instantiate RedisConfig from an array,
     * typically from application configuration or user input.
     *
     * @param  array{redis_host: string, redis_port: int, redis_password: string, using_docker: bool} $data Configuration array
     * @return self                                                                                   New RedisConfig instance
     */
    public static function fromArray(array $data): self
    {
        return new self(
            host: $data['redis_host'],
            port: $data['redis_port'],
            password: $data['redis_password'],
            usingDocker: $data[AppTypeInterface::CONFIG_USING_DOCKER],
        );
    }

    /**
     * Convert configuration to array format.
     *
     * Returns an array suitable for application configuration files
     * and environment variable generation.
     *
     * @return array{redis_host: string, redis_port: int, redis_password: string, using_docker: bool}
     */
    public function toArray(): array
    {
        return [
            'redis_host' => $this->host,
            'redis_port' => $this->port,
            'redis_password' => $this->password,
            AppTypeInterface::CONFIG_USING_DOCKER => $this->usingDocker,
        ];
    }
}
