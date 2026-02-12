<?php

declare(strict_types=1);

namespace PhpHive\Cli\Concerns;

use function error_log;
use function is_dir;
use function is_subclass_of;

use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Support\Reflection;

use function sprintf;
use function str_contains;
use function str_replace;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Throwable;

/**
 * Command Discovery Trait.
 *
 * This trait provides automatic command discovery functionality for the CLI
 * application. It scans specified directories for command classes and registers
 * them with the application automatically.
 *
 * Discovery process:
 * 1. Recursively scan the specified directory for PHP files
 * 2. Build fully qualified class names from file paths
 * 3. Validate that classes exist and extend Symfony Command
 * 4. Check for AsCommand attribute (Symfony 6.1+)
 * 5. Skip abstract classes, interfaces, and base command classes
 * 6. Register valid commands with the application
 *
 * This approach eliminates the need for manual command registration and
 * ensures all commands in the specified directory are automatically available.
 *
 * Example usage:
 * ```php
 * class Application extends BaseApplication
 * {
 *     use HasDiscovery;
 *
 *     public function boot(): void
 *     {
 *         $this->discoverCommands(
 *             __DIR__ . '/Commands',
 *             'PhpHive\\Cli\\Console\\Commands'
 *         );
 *     }
 * }
 * ```
 */
trait HasDiscovery
{
    /**
     * Discover and register commands from a directory.
     *
     * This method recursively scans a directory for PHP files, identifies
     * valid command classes, and registers them with the application. It
     * automatically handles namespace resolution based on the directory
     * structure.
     *
     * Uses the Filesystem service for directory traversal instead of raw
     * RecursiveDirectoryIterator, providing better error handling and
     * consistency with the rest of the codebase.
     *
     * The discovery process:
     * - Scans all PHP files recursively in the specified directory
     * - Converts file paths to fully qualified class names
     * - Validates that classes exist and are instantiable commands
     * - Skips abstract classes, interfaces, and base command classes
     * - Registers valid commands using registerCommand()
     * - Logs errors for failed registrations but continues processing
     *
     * @param string $path      Absolute path to the directory to scan
     * @param string $namespace Base namespace for commands in this directory
     */
    protected function discoverCommands(string $path, string $namespace): void
    {
        // Skip if directory doesn't exist
        if (! is_dir($path)) {
            return;
        }

        // Get all PHP files recursively using Filesystem service
        $files = $this->filesystem()->allFiles($path);

        // Process each file in the directory
        foreach ($files as $file) {
            // Only process PHP files
            if (! str_contains((string) $file, '.php')) {
                continue;
            }

            // Skip base command classes (not meant to be registered)
            if (str_contains((string) $file, 'BaseCommand')) {
                continue;
            }

            // Build fully qualified class name from file path
            // Example: Command/Install/InstallCommand.php
            //       -> \PhpHive\Cli\Command\Install\InstallCommand
            $classPath = str_replace('.php', '', $file);
            $classPath = str_replace('/', '\\', $classPath);
            $className = "{$namespace}\\{$classPath}";

            // Validate and register the command
            if ($this->isValidCommand($className)) {
                $this->registerDiscoveredCommand($className);
            }
        }
    }

    /**
     * Check if a class is a valid command that can be registered.
     *
     * A valid command must:
     * - Exist as a defined class
     * - Not be abstract (must be instantiable)
     * - Not be an interface
     * - Extend Symfony's Command class
     * - Have the AsCommand attribute (recommended for Symfony 6.1+)
     *
     * @param  string $className Fully qualified class name to validate
     * @return bool   True if the class is a valid command, false otherwise
     */
    protected function isValidCommand(string $className): bool
    {
        // Check if class exists
        if (! Reflection::exists($className)) {
            return false;
        }

        // Skip abstract classes (cannot be instantiated)
        if (Reflection::isAbstract($className)) {
            return false;
        }

        // Skip interfaces (cannot be instantiated)
        if (Reflection::isInterface($className)) {
            return false;
        }

        // Check if class extends Symfony Command
        if (! is_subclass_of($className, Command::class)) {
            return false;
        }

        // Check for AsCommand attribute (Symfony 6.1+)
        // This is the modern way to define commands
        return $this->hasAsCommandAttribute($className);
    }

    /**
     * Check if a class has the AsCommand attribute.
     *
     * The AsCommand attribute is the modern way to define Symfony Console
     * commands (introduced in Symfony 6.1). It provides a cleaner, more
     * declarative way to configure command metadata.
     *
     * Example:
     * ```php
     * #[AsCommand(
     *     name: 'app:test',
     *     description: 'Test command',
     *     hidden: false
     * )]
     * class TestCommand extends Command { }
     * ```
     *
     * @param  string $className Fully qualified class name to check
     * @return bool   True if the class has AsCommand attribute, false otherwise
     */
    protected function hasAsCommandAttribute(string $className): bool
    {
        // Check if class has any attributes
        if (! Reflection::hasAttributes($className)) {
            return false;
        }

        // Get all attributes on the class
        $attributes = Reflection::getClass($className)->getAttributes(AsCommand::class);

        // Return true if AsCommand attribute is present
        return $attributes !== [];
    }

    /**
     * Register a discovered command with the application.
     *
     * This method attempts to register a command class that was discovered
     * during the scanning process. If registration fails for any reason
     * (e.g., constructor errors, missing dependencies), the error is logged
     * but the discovery process continues.
     *
     * This graceful error handling ensures that one broken command doesn't
     * prevent the entire application from loading.
     *
     * @param string $className Fully qualified class name of the command to register
     */
    protected function registerDiscoveredCommand(string $className): void
    {
        try {
            // Attempt to register the command
            $this->registerCommand($className);
        } catch (Throwable $throwable) {
            // Log registration failure but continue with other commands
            // This prevents one broken command from breaking the entire CLI
            error_log(sprintf(
                'Failed to register command %s: %s',
                $className,
                $throwable->getMessage(),
            ));
        }
    }

    /**
     * Register a command with the application.
     *
     * This abstract method must be implemented by the class using this trait.
     * It should handle the actual instantiation and registration of the command
     * with the Symfony Console application.
     *
     * @param string $commandClass Fully qualified class name of the command
     */
    abstract protected function registerCommand(string $commandClass): void;

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
}
