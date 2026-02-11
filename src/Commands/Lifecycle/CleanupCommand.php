<?php

declare(strict_types=1);

namespace MonoPhp\Cli\Commands\Lifecycle;

use function exec;

use MonoPhp\Cli\Commands\BaseCommand;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cleanup Command.
 *
 * This command performs a deep clean of the entire monorepo by removing ALL
 * generated files including dependencies, lock files, and caches. This is a
 * destructive operation that returns the repository to a clean slate state,
 * requiring a full reinstall of dependencies afterward.
 *
 * The cleanup process:
 * 1. Displays a warning about what will be removed
 * 2. Requires explicit user confirmation (defaults to NO)
 * 3. Cleans Turbo cache first
 * 4. Recursively removes all generated directories and files
 * 5. Preserves only source code and configuration files
 *
 * Cleanup workflow:
 * - Shows detailed warning about destructive nature
 * - Lists all files and directories that will be removed
 * - Requires explicit user confirmation (safety measure)
 * - Cleans Turbo cache using Turbo CLI
 * - Recursively removes all vendor directories
 * - Recursively removes all node_modules directories
 * - Removes all lock files (composer.lock, pnpm-lock.yaml)
 * - Removes all cache directories (.turbo, .phpstan.cache, .phpunit.cache)
 * - Removes all log files
 * - Provides next steps for reinstallation
 *
 * What gets removed:
 * - All vendor directories (Composer dependencies)
 * - All node_modules directories (npm/pnpm dependencies)
 * - All lock files (composer.lock, pnpm-lock.yaml)
 * - All caches (.turbo, .phpstan.cache, .phpunit.cache)
 * - All log files (*.log)
 * - All build artifacts
 *
 * What is preserved:
 * - Source code files (.php, .ts, .js, etc.)
 * - Configuration files (composer.json, package.json, turbo.json)
 * - Git repository (.git directory)
 * - Documentation files
 * - Environment files (.env)
 *
 * Features:
 * - Requires explicit confirmation (safety measure)
 * - Recursive cleanup across entire monorepo
 * - Preserves source code and configuration
 * - Shows progress with spinner
 * - Provides next steps after cleanup
 * - Graceful cancellation support
 * - Detailed warning messages
 *
 * Use cases:
 * - Resolving dependency conflicts
 * - Starting fresh after major changes
 * - Freeing up disk space
 * - Troubleshooting build issues
 * - Preparing for clean reinstall
 * - Fixing corrupted dependencies
 *
 * Common options inherited from BaseCommand:
 * - --workspace, -w: Target specific workspace
 * - --force, -f: Force operation by ignoring cache
 * - --no-cache: Disable Turbo cache
 * - --no-interaction, -n: Run in non-interactive mode
 *
 * Example usage:
 * ```bash
 * # Deep clean everything (requires confirmation)
 * ./cli/bin/mono cleanup
 *
 * # After cleanup, reinstall dependencies
 * pnpm install
 *
 * # Then rebuild everything
 * ./cli/bin/mono build
 * ```
 *
 * WARNING: This is a destructive operation. You will need to run
 * `pnpm install` after cleanup to restore dependencies.
 *
 * @see BaseCommand For inherited functionality and common options
 * @see InteractsWithTurborepo For Turbo integration
 * @see InteractsWithMonorepo For workspace discovery
 * @see CleanCommand For lighter cleaning (preserves dependencies)
 * @see InstallCommand For reinstalling dependencies
 */
#[AsCommand(
    name: 'cleanup',
    description: 'Deep clean all generated files (destructive)',
)]
final class CleanupCommand extends BaseCommand
{
    /**
     * Configure the command options.
     *
     * Inherits common options from BaseCommand (workspace, force, no-cache, no-interaction).
     * No additional command-specific options needed for this command.
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();
    }

    /**
     * Execute the cleanup command.
     *
     * This method orchestrates the entire deep cleanup process:
     * 1. Displays a warning banner about destructive nature
     * 2. Lists exactly what will be removed
     * 3. Requires explicit user confirmation (defaults to NO)
     * 4. Cleans Turbo cache first using Turbo CLI
     * 5. Recursively removes all generated files and directories
     * 6. Reports success and provides next steps for reinstallation
     *
     * The cleanup uses shell commands with find to recursively locate and
     * remove generated files. The -prune flag prevents descending into
     * matched directories for efficiency.
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Display intro banner with warning
        $this->intro('Deep Cleanup');

        // Show warning about destructive nature
        $this->warning('This will remove:');
        $this->note(
            "- All vendor directories\n" .
            "- All node_modules directories\n" .
            "- All lock files (composer.lock, pnpm-lock.yaml)\n" .
            "- All caches (.turbo, .phpstan.cache, .phpunit.cache)\n" .
            '- All log files',
        );

        // Require explicit confirmation from user
        // Defaults to false for safety
        $confirmed = $this->confirm(
            'Are you sure you want to continue?',
            default: false,
        );

        // User cancelled - exit gracefully
        if (! $confirmed) {
            $this->info('Cleanup cancelled');

            return Command::SUCCESS;
        }

        // User confirmed - proceed with cleanup
        $this->info('Starting deep cleanup...');

        // Clean Turbo cache first using Turbo CLI
        // Show spinner while cleaning
        $this->spin(
            fn (): int => $this->turbo('clean --no-cache'),
            'Cleaning Turbo cache...',
        );

        // Get monorepo root for building file paths
        $root = $this->getMonorepoRoot();

        // Build array of shell commands to remove files/directories
        // Each command targets specific generated files
        $commands = [
            // Remove root-level directories
            "rm -rf {$root}/.turbo",
            "rm -rf {$root}/node_modules",

            // Remove root-level lock files
            "rm -f {$root}/pnpm-lock.yaml",
            "rm -f {$root}/composer.lock",

            // Recursively find and remove directories
            // -prune prevents descending into matched directories
            "find {$root} -type d -name 'node_modules' -prune -exec rm -rf {} +",
            "find {$root} -type d -name '.turbo' -prune -exec rm -rf {} +",
            "find {$root} -type d -name 'vendor' -prune -exec rm -rf {} +",
            "find {$root} -type d -name '.phpstan.cache' -prune -exec rm -rf {} +",
            "find {$root} -type d -name '.phpunit.cache' -prune -exec rm -rf {} +",

            // Recursively find and remove files
            "find {$root} -name 'composer.lock' -delete",
            "find {$root} -name '*.log' -delete",
        ];

        // Execute each cleanup command
        foreach ($commands as $command) {
            exec($command, $output, $exitCode);

            // Warn if command failed but continue with others
            if ($exitCode !== 0) {
                $this->warning("Command failed: {$command}");
            }
        }

        // Display success message
        $this->outro('✓ Deep cleanup completed!');

        // Provide next steps for user
        $this->info('Run "pnpm install" to reinstall dependencies');

        return Command::SUCCESS;
    }
}
