<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Framework;

use function array_column;
use function implode;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Artisan Command.
 *
 * This command provides direct access to Laravel Artisan commands within workspace contexts.
 * It acts as a passthrough wrapper that allows running any Artisan command in a Laravel
 * workspace without manually navigating to workspace directories or managing multiple
 * Laravel applications across the monorepo.
 *
 * Laravel Artisan is the command-line interface included with Laravel. It provides
 * helpful commands for common tasks like running migrations, clearing caches, generating
 * code, and managing the application lifecycle.
 *
 * The command forwards all arguments directly to Artisan, preserving all flags,
 * options, and behavior of the underlying Artisan command. This makes it a flexible
 * tool for any Artisan operation within the monorepo context.
 *
 * Features:
 * - Run any Artisan command in workspace context
 * - Interactive workspace selection if not specified
 * - Workspace validation before execution
 * - Automatic Laravel application detection
 * - Full argument passthrough to Artisan
 * - Support for all Artisan flags and options
 * - Real-time command output streaming
 * - Automatic working directory management
 *
 * Common use cases:
 * - Database migrations (migrate, migrate:rollback, migrate:fresh)
 * - Cache management (cache:clear, config:cache, route:cache)
 * - Code generation (make:controller, make:model, make:migration)
 * - Queue management (queue:work, queue:restart, queue:failed)
 * - Application maintenance (down, up, optimize)
 * - Testing (test, dusk)
 * - Debugging (route:list, tinker, db:show)
 *
 * Workflow:
 * 1. Accepts any Artisan command as arguments
 * 2. Selects or validates target workspace
 * 3. Verifies workspace is a Laravel application
 * 4. Changes to workspace directory
 * 5. Executes Artisan with provided arguments
 * 6. Streams output in real-time
 * 7. Reports success or failure
 *
 * Example usage:
 * ```bash
 * # Run migrations
 * hive artisan migrate --workspace=api
 *
 * # Clear all caches
 * hive artisan cache:clear -w admin
 *
 * # Generate application key
 * hive artisan key:generate
 *
 * # List all routes
 * hive artisan route:list
 *
 * # Create a controller
 * hive artisan make:controller UserController -w api
 *
 * # Run database seeder
 * hive artisan db:seed --class=UserSeeder
 *
 * # Start queue worker
 * hive artisan queue:work --workspace=api
 *
 * # Run tests
 * hive artisan test --parallel
 *
 * # Optimize application
 * hive artisan optimize
 *
 * # Using alias
 * hive art migrate:fresh --seed -w api
 * ```
 *
 * Common options inherited from BaseCommand:
 * - --workspace, -w: Target specific workspace
 * - --force, -f: Force operation by ignoring cache
 * - --no-cache: Disable Turbo cache
 * - --no-interaction, -n: Run in non-interactive mode
 *
 * @see BaseCommand For inherited functionality and common options
 * @see InteractsWithMonorepo For workspace discovery
 * @see ComposerCommand For Composer operations
 * @see ConsoleCommand For Symfony console operations
 * @see MagentoCommand For Magento CLI operations
 */
#[AsCommand(
    name: 'framework:artisan',
    description: 'Run Laravel Artisan command in a workspace',
    aliases: ['artisan', 'art'],
)]
final class ArtisanCommand extends BaseCommand
{
    /**
     * Configure the command options and arguments.
     *
     * Defines the command signature with flexible argument handling to accept
     * any Artisan command and its options. The command argument is defined as
     * an array to capture all parts of the Artisan command including subcommands,
     * arguments, and flags.
     *
     * Common options like --workspace are inherited from BaseCommand, allowing
     * users to specify which Laravel application to target.
     *
     * The help text provides examples of common Artisan commands and explains
     * the interactive workspace selection behavior when no workspace is specified.
     */
    #[Override]
    protected function configure(): void
    {
        // Inherit common options from BaseCommand (workspace, force, no-cache, no-interaction)
        parent::configure();

        $this
            ->addArgument(
                'command',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'The Artisan command to run (e.g., migrate, cache:clear, make:controller)',
            )
            ->setHelp(
                <<<'HELP'
                The <info>artisan</info> command runs Laravel Artisan commands in workspace contexts.

                <comment>Examples:</comment>
                  <info>hive artisan migrate</info>
                  <info>hive artisan cache:clear --workspace=api</info>
                  <info>hive artisan make:controller UserController</info>
                  <info>hive artisan route:list</info>
                  <info>hive artisan queue:work --tries=3</info>
                  <info>hive artisan test --parallel</info>

                If no workspace is specified, you'll be prompted to select one.
                HELP
            );
    }

    /**
     * Execute the artisan command.
     *
     * This method orchestrates the Artisan command execution:
     * 1. Extracts command arguments from user input
     * 2. Selects target workspace (interactive if not specified)
     * 3. Validates workspace exists and is a Laravel application
     * 4. Displays execution details
     * 5. Runs Artisan command in workspace directory
     * 6. Reports execution results
     *
     * The command uses passthru() to execute Artisan, which streams output
     * in real-time to the console. This is important for long-running commands
     * like migrations or queue workers.
     *
     * Laravel application detection is performed by checking for the presence
     * of the 'artisan' file in the workspace root. If not found, the command
     * fails with a helpful error message.
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Display intro banner
        $this->intro('Running Artisan command...');

        // =====================================================================
        // COMMAND EXTRACTION
        // =====================================================================

        // Get the artisan command arguments
        // These are passed as an array and need to be joined into a single string
        // Example: ['migrate', '--force'] becomes 'migrate --force'
        $commandArgs = $input->getArgument('command');
        $artisanCommand = implode(' ', $commandArgs);

        // =====================================================================
        // WORKSPACE SELECTION
        // =====================================================================

        // Get workspace from option or prompt user
        $workspace = $input->getOption('workspace');

        if (! is_string($workspace) || $workspace === '') {
            // No workspace specified - prompt user to select one
            $workspaces = $this->getWorkspaces();

            if ($workspaces === []) {
                // No workspaces found in monorepo
                $this->error('No workspaces found');

                return Command::FAILURE;
            }

            // Interactive workspace selection
            // Displays a list of all available workspaces
            $workspace = $this->select(
                'Select workspace',
                array_column($workspaces, 'name'),
            );

            // Ensure workspace is a string after selection
            if (! is_string($workspace)) {
                $this->error('Invalid workspace selection');

                return Command::FAILURE;
            }
        }

        // =====================================================================
        // WORKSPACE VALIDATION
        // =====================================================================

        // Verify workspace exists in the monorepo
        if (! $this->hasWorkspace($workspace)) {
            $this->error("Workspace '{$workspace}' not found");

            return Command::FAILURE;
        }

        // Get the full path to the workspace directory
        $workspacePath = $this->getWorkspacePath($workspace);

        // =====================================================================
        // LARAVEL APPLICATION DETECTION
        // =====================================================================

        // Check if artisan file exists in the workspace
        // This confirms the workspace is a Laravel application
        if (! $this->filesystem()->exists("{$workspacePath}/artisan")) {
            $this->error("Artisan not found in workspace '{$workspace}'. Is this a Laravel application?");

            return Command::FAILURE;
        }

        // =====================================================================
        // COMMAND EXECUTION
        // =====================================================================

        // Display execution details to user
        $this->info("Running: php artisan {$artisanCommand}");
        $this->comment("Workspace: {$workspace}");
        $this->line('');

        // Run artisan command in workspace directory
        // passthru() streams output in real-time and captures exit code
        // The cd command ensures we're in the correct directory for Laravel
        $fullCommand = "cd {$workspacePath} && php artisan {$artisanCommand}";
        passthru($fullCommand, $exitCode);

        // =====================================================================
        // RESULT REPORTING
        // =====================================================================

        // Report results to user based on exit code
        if ($exitCode === 0) {
            // Success - command completed without errors
            $this->outro('✓ Artisan command completed successfully');
        } else {
            // Failure - command returned non-zero exit code
            $this->error('✗ Artisan command failed');
        }

        return $exitCode;
    }
}
