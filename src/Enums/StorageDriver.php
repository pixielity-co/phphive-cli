<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Storage Driver Enum.
 *
 * Defines available object storage backends for file storage, media uploads,
 * backups, and other blob storage needs.
 *
 * Supported drivers:
 * - MINIO: Self-hosted S3-compatible object storage (Docker-friendly)
 * - S3: Amazon S3 cloud storage service (fully managed)
 *
 * Each driver provides S3-compatible API, allowing applications to switch
 * between them with minimal code changes.
 */
enum StorageDriver: string
{
    /**
     * MinIO - Self-hosted S3-compatible object storage.
     *
     * Best for:
     * - Development and testing environments
     * - On-premise deployments
     * - Cost-sensitive projects
     * - Full control over infrastructure
     * - Docker-based deployments
     *
     * Features:
     * - S3-compatible API
     * - Built-in web console
     * - High performance
     * - Easy Docker deployment
     * - No cloud vendor lock-in
     */
    case MINIO = 'minio';

    /**
     * Amazon S3 - Fully managed cloud object storage.
     *
     * Best for:
     * - Production cloud deployments
     * - AWS-based infrastructure
     * - High availability requirements
     * - Global content delivery
     * - Unlimited scalability needs
     *
     * Features:
     * - Fully managed service
     * - 99.999999999% durability
     * - Global infrastructure
     * - Advanced features (versioning, lifecycle, replication)
     * - Integration with AWS services
     * - Pay-per-use pricing
     */
    case S3 = 's3';

    /**
     * Get all storage driver choices for select prompts.
     *
     * Returns an associative array with display labels as keys
     * and enum values as values, suitable for use in select() prompts.
     *
     * @return array<string, string> Choices array
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getName() . ' - ' . $case->getDescription()] = $case->value;
        }

        return $choices;
    }

    /**
     * Get human-readable name for the storage driver.
     *
     * @return string Display name
     */
    public function getName(): string
    {
        return match ($this) {
            self::MINIO => 'MinIO',
            self::S3 => 'Amazon S3',
        };
    }

    /**
     * Get description of the storage driver.
     *
     * @return string Driver description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MINIO => 'Self-hosted S3-compatible storage (Docker-friendly)',
            self::S3 => 'Amazon S3 cloud storage (fully managed)',
        };
    }

    /**
     * Check if this driver requires Docker setup.
     *
     * @return bool True if Docker setup is available/recommended
     */
    public function supportsDocker(): bool
    {
        return match ($this) {
            self::MINIO => true,
            self::S3 => false, // S3 is cloud-based, no Docker needed
        };
    }

    /**
     * Check if this driver is cloud-based.
     *
     * @return bool True if cloud-based service
     */
    public function isCloudBased(): bool
    {
        return match ($this) {
            self::MINIO => false,
            self::S3 => true,
        };
    }

    /**
     * Get default port for the storage driver.
     *
     * @return int|null Default port, or null if not applicable
     */
    public function getDefaultPort(): ?int
    {
        return match ($this) {
            self::MINIO => 9000,
            self::S3 => null, // S3 uses HTTPS (443) via SDK
        };
    }
}
