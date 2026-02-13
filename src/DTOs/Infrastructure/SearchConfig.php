<?php

declare(strict_types=1);

namespace PhpHive\Cli\DTOs\Infrastructure;

use PhpHive\Cli\Enums\SearchEngine;

/**
 * Search Configuration Data Transfer Object.
 *
 * Encapsulates search engine configuration data for setup and deployment.
 * Supports multiple search engines: Elasticsearch, Meilisearch, OpenSearch.
 *
 * Example usage:
 * ```php
 * $config = new SearchConfig(
 *     engine: SearchEngine::ELASTICSEARCH,
 *     host: 'localhost',
 *     port: 9200,
 *     usingDocker: true
 * );
 * ```
 */
final readonly class SearchConfig
{
    /**
     * Create a new search configuration instance.
     *
     * @param SearchEngine $engine      Search engine type
     * @param string       $host        Search engine host
     * @param int          $port        Search engine port
     * @param string|null  $endpoint    AWS OpenSearch endpoint (OpenSearch only)
     * @param string|null  $region      AWS region (OpenSearch only)
     * @param string|null  $apiKey      API key or master key for authentication
     * @param bool         $usingDocker Whether Docker is being used
     */
    public function __construct(
        public SearchEngine $engine,
        public string $host,
        public int $port,
        public ?string $endpoint = null,
        public ?string $region = null,
        public ?string $apiKey = null,
        public bool $usingDocker = false,
    ) {}

    /**
     * Create configuration from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            engine: SearchEngine::from($data['search_engine'] ?? 'none'),
            host: $data['host'] ?? 'localhost',
            port: (int) ($data['port'] ?? 9200),
            endpoint: $data['endpoint'] ?? null,
            region: $data['region'] ?? null,
            apiKey: $data['api_key'] ?? null,
            usingDocker: (bool) ($data['using_docker'] ?? false),
        );
    }

    /**
     * Convert configuration to array format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search_engine' => $this->engine->value,
            'host' => $this->host,
            'port' => $this->port,
            'endpoint' => $this->endpoint,
            'region' => $this->region,
            'api_key' => $this->apiKey,
            'using_docker' => $this->usingDocker,
        ];
    }
}
