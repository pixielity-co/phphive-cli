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
 * Console Command (Symfony).
 *
 * This command provides direct access to Symfony Console commands within workspace contexts.
 * It acts as a passthrough wrapper that allows running any Symfony console command in a
 * Symfony workspace without manually navigating to workspace directories or managing multiple
 * Symfony applications across the monorepo.
 *
 * Symfony Console is the command-line interface included with Symfony applications.
 * It provides powerful commands for managing the application, including cache management,
 * database operations, debugging tools, code generation, and asset management.
 *
 * The command forwards all arguments directly to Symfony Console, preserving all flags,
 * options, and behavior of the underlying console command. This makes it a flexible
 * tool for any Symfony operation within the monorepo context.
 *
 * Features:
 * - Run any Symfony console command in workspace context
 * - Interactive workspace selection if not specified
 * - Workspace validation before execution
 * - Automatic Symfony application detection
 * - Full argument passthrough to Symfony console
 * - Support for all Symfony flags and options
 * - Real-time command output streaming
 * - Automatic working directory management
 *
 * Common use cases:
 * - Cache management (cache:clear, cache:warmup, cache:pool:clear)
 * - Database operations (doctrine:migrations:migrate, doctrine:schema:update)
 * - Code generation (make:controller, make:entity, make:form)
 * - Debugging (debug:router, debug:container, debug:config, debug:autowiring)
 * - Asset management (assets:install, importmap:install)
 * - Messenger/Queue (messenger:consume, messenger:failed:show)
 * - Security (security:hash-password, security:check)
 * - Translation (translation:extract, translation:pull, translation:push)
 * - Secrets management (secrets:set, secrets:list, secrets:decrypt-to-local)
 * - Server management (server:start, server:stop, server:status)
 *
 * Workflow:
 * 1. Accepts any Symfony console command as arguments
 * 2. Selects or validates target workspace
 * 3. Verifies workspace is a Symfony application
 * 4. Changes to workspace directory
 * 5. Executes Symfony console with provided arguments
 * 6. Streams output in real-time
 * 7. Reports success or failure
 *
 * Example usage:
 * ```bash
 * # Clear cache
 * hive console cache:clear --workspace=api
 *
 * # Run database migrations
 * hive console doctrine:migrations:migrate -w admin
 *
 * # List all routes
 * hive console debug:router
 *
 * # Create a new entity
 * hive console make:entity User -w api
 *
 * # Dump environment variables
 * hive console debug:dotenv
 *
 * # Update database schema
 * hive console doctrine:schema:update --force
 *
 * # Consume messenger messages
 * hive console messenger:consume async --limit=10
 *
 * # Hash a password
 * hive console security:hash-password
 *
 * # Install assets
 * hive console assets:install public
 *
 * # Check security vulnerabilities
 * hive console security:check
 *
 * # Using aliases
 * hive sf cache:clear -w api
 * hive symfony debug:router
 * ```
 *
 * Common options inherited from BaseCommand:
 * - --workspace, -w: Target specific workspace
 * - --force, -f: Force operation by ignoring cache
 * - --no-cache: Disable Turbo cache
 * - --no-interaction, -n: Run in non-interactive mode
 *
 * Symfony-specific options (passed through):
 * - --env, -e: The environment name (dev, prod, test)
 * - --no-debug: Disable debug mode
 * - --quiet, -q: Do not output any message
 * - --verbose, -v|vv|vvv: Increase verbosity
 *
 * Performance tips:
 * - Use --env=prod for production environment
 * - Run cache:warmup after cache:clear in production
 * - Use --no-debug flag in production for better performance
 * - Compile container in production with cache:warmup
 *
 * @see BaseCommand For inherited functionality and common options
 * @see InteractsWithMonorepo For workspace discovery
 * @see ComposerCommand For Composer operations
 * @see ArtisanCommand For Laravel Artisan operations
 * @see MagentoCommand For Magento CLI operations
 */
#[AsCommand(
    name: 'framework:console',
    description: 'Run Symfony Console command in a workspace',
    aliases: ['console', 'sf', 'symfony'],
)]
final class ConsoleCommand extends BaseCommand
{
    /**
     * Configure the command options and arguments.
     *
     * Defines the command signature with flexible argument handling to accept
     * any Symfony console command and its options. The command argument is defined as
     * an array to capture all parts of the console command including subcommands,
     * arguments, and flags.
     *
     * Common options like --workspace are inherited from BaseCommand, allowing
     * users to specify which Symfony application to target.
     *
     * The help text provides examples of common Symfony console commands and explains
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
                'The Symfony console command to run (e.g., cache:clear, make:entity, debug:router)',
            )
            ->setHelp(
                <<<'HELP'
                The <info>console</info> command runs Symfony Console commands in workspace contexts.

                <comment>Examples:</comment>
                  <info>hive console cache:clear</info>
                  <info>hive console doctrine:migrations:migrate --workspace=api</info>
                  <info>hive console make:controller UserController</info>
                  <info>hive console debug:router</info>
                  <info>hive console messenger:consume async</info>
                  <info>hive console doctrine:schema:update --force</info>

                If no workspace is specified, you'll be prompted to select one.
                HELP
            );
    }

    /**
     * Execute the symfony console command.
     *
     * This method orchestrates the Symfony console command execution:
     * 1. Extracts command arguments from user input
     * 2. Selects target workspace (interactive if not specified)
     * 3. Validates workspace exists and is a Symfony application
     * 4. Displays execution details
     * 5. Runs Symfony console command in workspace directory
     * 6. Reports execution results
     *
     * The command uses passthru() to execute Symfony console, which streams output
     * in real-time to the console. This is important for long-running commands
     * like migrations or message consumption.
     *
     * Symfony application detection is performed by checking for the presence
     * of the 'bin/console' file in the workspace root. If not found, the command
     * fails with a helpful error message.
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Display intro banner
        $this->intro('Running Symfony Console command...');

        // =====================================================================
        // COMMAND EXTRACTION
        // =====================================================================

        // Get the console command arguments
        // These are passed as an array and need to be joined into a single string
        // Example: ['cache:clear', '--env=prod'] becomes 'cache:clear --env=prod'
        $commandArgs = $input->getArgument('command');
        $consoleCommand = implode(' ', $commandArgs);

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
        // SYMFONY APPLICATION DETECTION
        // =====================================================================

        // Check if bin/console file exists in the workspace
        // This confirms the workspace is a Symfony application
        if (! $this->filesystem()->exists("{$workspacePath}/bin/console")) {
            $this->error("Symfony Console not found in workspace '{$workspace}'. Is this a Symfony application?");

            return Command::FAILURE;
        }

        // =====================================================================
        // COMMAND EXECUTION
        // =====================================================================

        // Display execution details to user
        $this->info("Running: php bin/console {$consoleCommand}");
        $this->comment("Workspace: {$workspace}");
        $this->line('');

        // Run console command in workspace directory
        // passthru() streams output in real-time and captures exit code
        // The cd command ensures we're in the correct directory for Symfony
        $fullCommand = "cd {$workspacePath} && php bin/console {$consoleCommand}";
        passthru($fullCommand, $exitCode);

        // =====================================================================
        // RESULT REPORTING
        // =====================================================================

        // Report results to user based on exit code
        if ($exitCode === 0) {
            // Success - command completed without errors
            $this->outro('✓ Symfony Console command completed successfully');
        } else {
            // Failure - command returned non-zero exit code
            $this->error('✗ Symfony Console command failed');
        }

        return $exitCode;
    }
}
