<?php

declare(strict_types=1);

namespace PhpHive\Cli\Support;

/**
 * Configuration Operation Builder.
 *
 * Provides fluent static methods to create ConfigOperation objects for declaring
 * configuration changes that should be applied after application installation.
 *
 * This class serves as a convenient factory for ConfigOperation objects, making
 * AppType configuration declarations more readable and expressive.
 *
 * Supported Operations:
 * - set: Overwrite existing keys with new values (creates file if not exists)
 * - append: Add new keys without overwriting existing ones
 * - merge: Deep merge nested arrays (useful for PHP config files)
 *
 * Single File Operations:
 * Use when you need to configure one file at a time. Most readable for
 * different action types on different files.
 *
 * Bulk Operations:
 * Use when you need to apply the same action to multiple files. Reduces
 * verbosity when configuring many files with the same operation type.
 *
 * File Format Support:
 * The Config class doesn't care about file formats - it just creates operation
 * objects. The ConfigWriter service handles format detection and writing based
 * on file extension (.env, .php, .yaml, etc.)
 *
 * @example Single file operations
 * ```php
 * return [
 *     Config::set('.env', [
 *         'DATABASE_HOST' => 'db',
 *         'REDIS_HOST' => 'redis',
 *     ]),
 *     Config::append('.env.local', [
 *         'DEBUG' => 'true',
 *     ]),
 *     Config::merge('app/etc/env.php', [
 *         'session' => ['save' => 'redis'],
 *     ]),
 * ];
 * ```
 * @example Bulk operations
 * ```php
 * return [
 *     ...Config::setBulk([
 *         '.env' => ['DATABASE_HOST' => 'db'],
 *         '.env.local' => ['DEBUG' => 'true'],
 *     ]),
 * ];
 * ```
 */
final class Config
{
    /**
     * Set configuration values in a single file.
     *
     * Creates a 'set' operation that overwrites existing keys with new values.
     * If the file doesn't exist, it will be created. Existing keys not specified
     * in the values array will remain unchanged.
     *
     * Use this for:
     * - Setting environment variables in .env files
     * - Updating configuration values that should replace existing ones
     * - Initial configuration of new files
     *
     * @param  string               $file   Relative path to config file (e.g., '.env', 'app/etc/env.php')
     * @param  array<string, mixed> $values Key-value pairs to set
     * @return ConfigOperation      The operation object to be processed later
     *
     * @example
     * ```php
     * Config::set('.env', [
     *     'DATABASE_HOST' => 'db',
     *     'DATABASE_NAME' => 'myapp',
     *     'REDIS_HOST' => 'redis',
     * ]);
     * ```
     */
    public static function set(string $file, array $values): ConfigOperation
    {
        return new ConfigOperation('set', $file, $values);
    }

    /**
     * Set configuration values across multiple files at once.
     *
     * Creates multiple 'set' operations, one for each file. Useful when you need
     * to configure multiple files with the same action type, reducing verbosity.
     *
     * @param  array<string, array<string, mixed>> $fileValues Map of file paths to their key-value pairs
     * @return array<ConfigOperation>              Array of operation objects
     *
     * @example
     * ```php
     * Config::setBulk([
     *     '.env' => ['DATABASE_HOST' => 'db'],
     *     '.env.local' => ['DEBUG' => 'true'],
     *     'pub/.env' => ['MAGE_MODE' => 'developer'],
     * ]);
     * ```
     */
    public static function setBulk(array $fileValues): array
    {
        $operations = [];
        foreach ($fileValues as $file => $values) {
            $operations[] = new ConfigOperation('set', $file, $values);
        }

        return $operations;
    }

    /**
     * Append configuration values to a file without overwriting.
     *
     * Creates an 'append' operation that adds new keys or appends to existing values.
     * Useful for adding additional configuration without replacing what's already there.
     *
     * Use this for:
     * - Adding new environment variables without touching existing ones
     * - Supplementing configuration in .env.local files
     * - Adding optional configuration that shouldn't override defaults
     *
     * @param  string               $file   Relative path to config file
     * @param  array<string, mixed> $values Key-value pairs to append
     * @return ConfigOperation      The operation object to be processed later
     *
     * @example
     * ```php
     * Config::append('.env.local', [
     *     'CUSTOM_VAR' => 'value',
     *     'FEATURE_FLAG' => 'enabled',
     * ]);
     * ```
     */
    public static function append(string $file, array $values): ConfigOperation
    {
        return new ConfigOperation('append', $file, $values);
    }

    /**
     * Append configuration values across multiple files at once.
     *
     * Creates multiple 'append' operations, one for each file.
     *
     * @param  array<string, array<string, mixed>> $fileValues Map of file paths to their key-value pairs
     * @return array<ConfigOperation>              Array of operation objects
     *
     * @example
     * ```php
     * Config::appendBulk([
     *     '.env.local' => ['DEBUG' => 'true'],
     *     '.env.testing' => ['TEST_MODE' => 'true'],
     * ]);
     * ```
     */
    public static function appendBulk(array $fileValues): array
    {
        $operations = [];
        foreach ($fileValues as $file => $values) {
            $operations[] = new ConfigOperation('append', $file, $values);
        }

        return $operations;
    }

    /**
     * Deep merge configuration values (for nested arrays).
     *
     * Creates a 'merge' operation that recursively merges arrays. Particularly
     * useful for PHP config files with nested structures where you want to update
     * specific nested values without replacing the entire structure.
     *
     * Use this for:
     * - Magento's app/etc/env.php with nested cache/session config
     * - Laravel's config files with nested arrays
     * - Any PHP config file with complex nested structures
     *
     * @param  string               $file   Relative path to config file
     * @param  array<string, mixed> $values Nested array structure to merge
     * @return ConfigOperation      The operation object to be processed later
     *
     * @example
     * ```php
     * Config::merge('app/etc/env.php', [
     *     'session' => [
     *         'save' => 'redis',
     *         'redis' => [
     *             'host' => 'redis',
     *             'port' => 6379,
     *         ],
     *     ],
     *     'cache' => [
     *         'frontend' => [
     *             'default' => [
     *                 'backend' => 'Cm_Cache_Backend_Redis',
     *             ],
     *         ],
     *     ],
     * ]);
     * ```
     */
    public static function merge(string $file, array $values): ConfigOperation
    {
        return new ConfigOperation('merge', $file, $values);
    }

    /**
     * Deep merge configuration values across multiple files at once.
     *
     * Creates multiple 'merge' operations, one for each file.
     *
     * @param  array<string, array<string, mixed>> $fileValues Map of file paths to their nested structures
     * @return array<ConfigOperation>              Array of operation objects
     *
     * @example
     * ```php
     * Config::mergeBulk([
     *     'app/etc/env.php' => [
     *         'session' => ['save' => 'redis'],
     *     ],
     *     'config/app.php' => [
     *         'cache' => ['default' => 'redis'],
     *     ],
     * ]);
     * ```
     */
    public static function mergeBulk(array $fileValues): array
    {
        $operations = [];
        foreach ($fileValues as $file => $values) {
            $operations[] = new ConfigOperation('merge', $file, $values);
        }

        return $operations;
    }
}
