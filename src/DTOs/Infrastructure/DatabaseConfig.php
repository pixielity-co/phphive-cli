<?php

declare(strict_types=1);

namespace PhpHive\Cli\DTOs\Infrastructure;

use InvalidArgumentException;
use PhpHive\Cli\Enums\DatabaseType;

/**
 * Database Configuration Data Transfer Object.
 *
 * Encapsulates all database configuration parameters in a type-safe,
 * immutable object. Used to pass database configuration between services
 * and methods without relying on associative arrays.
 *
 * Benefits:
 * - Type safety: All properties are strongly typed
 * - Immutability: Configuration cannot be accidentally modified
 * - Validation: Constructor ensures all required fields are present
 * - IDE support: Full autocomplete and type hints
 * - Testability: Easy to create test fixtures
 *
 * Usage:
 * ```php
 * $config = new DatabaseConfig(
 *     type: DatabaseType::MYSQL,
 *     host: '127.0.0.1',
 *     port: 3306,
 *     name: 'my_app',
 *     user: 'my_app_user',
 *     password: 'secure_password',
 *     usingDocker: true
 * );
 *
 * // Convert to array for legacy code
 * $array = $config->toArray();
 *
 * // Create from array
 * $config = DatabaseConfig::fromArray($array);
 * ```
 */
final readonly class DatabaseConfig
{
    /**
     * Create a new database configuration instance.
     *
     * @param DatabaseType $type        Database type (MySQL, PostgreSQL, etc.)
     * @param string       $host        Database host address
     * @param int          $port        Database port number
     * @param string       $name        Database name
     * @param string       $user        Database username
     * @param string       $password    Database password
     * @param bool         $usingDocker Whether Docker is being used
     */
    public function __construct(
        public DatabaseType $type,
        public string $host,
        public int $port,
        public string $name,
        public string $user,
        public string $password,
        public bool $usingDocker,
    ) {}

    /**
     * Create a DatabaseConfig instance from an associative array.
     *
     * Converts legacy array-based configuration to a type-safe DTO.
     * Validates that all required keys are present.
     *
     * @param  array<string, mixed> $data Configuration array
     * @return self                 DatabaseConfig instance
     *
     * @throws InvalidArgumentException If required keys are missing
     */
    public static function fromArray(array $data): self
    {
        // Validate required keys
        $requiredKeys = ['db_type', 'db_host', 'db_port', 'db_name', 'db_user', 'db_password', 'using_docker'];
        foreach ($requiredKeys as $requiredKey) {
            if (! isset($data[$requiredKey])) {
                throw new InvalidArgumentException("Missing required key: {$requiredKey}");
            }
        }

        return new self(
            type: DatabaseType::from($data['db_type']),
            host: $data['db_host'],
            port: (int) $data['db_port'],
            name: $data['db_name'],
            user: $data['db_user'],
            password: $data['db_password'],
            usingDocker: (bool) $data['using_docker'],
        );
    }

    /**
     * Convert the configuration to an associative array.
     *
     * Returns an array compatible with AppTypeInterface configuration
     * constants, allowing seamless integration with existing code.
     *
     * @return array<string, mixed> Configuration array
     */
    public function toArray(): array
    {
        return [
            'db_type' => $this->type->value,
            'db_host' => $this->host,
            'db_port' => $this->port,
            'db_name' => $this->name,
            'db_user' => $this->user,
            'db_password' => $this->password,
            'using_docker' => $this->usingDocker,
        ];
    }
}
