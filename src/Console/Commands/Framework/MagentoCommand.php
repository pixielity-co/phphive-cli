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
 * Magento Command.
 *
 * This command provides direct access to Magento CLI commands within workspace contexts.
 * It acts as a passthrough wrapper that allows running any Magento command in a Magento
 * workspace without manually navigating to workspace directories or managing multiple
 * Magento installations across the monorepo.
 *
 * Magento CLI (bin/magento) is the command-line interface included with Magento 2.
 * It provides essential commands for managing the e-commerce platform, including
 * setup, configuration, cache management, indexing, deployment, and module management.
 *
 * The command forwards all arguments directly to Magento CLI, preserving all flags,
 * options, and behavior of the underlying Magento command. This makes it a flexible
 * tool for any Magento operation within the monorepo context.
 *
 * Features:
 * - Run any Magento CLI command in workspace context
 * - Interactive workspace selection if not specified
 * - Workspace validation before execution
 * - Automatic Magento installation detection
 * - Full argument passthrough to Magento CLI
 * - Support for all Magento flags and options
 * - Real-time command output streaming
 * - Automatic working directory management
 *
 * Common use cases:
 * - Cache management (cache:flush, cache:clean, cache:enable)
 * - Indexing (indexer:reindex, indexer:status, indexer:reset)
 * - Setup operations (setup:upgrade, setup:di:compile, setup:static-content:deploy)
 * - Module management (module:enable, module:disable, module:status)
 * - Configuration (config:set, config:show, config:sensitive:set)
 * - Deployment (deploy:mode:set, deploy:mode:show)
 * - Maintenance (maintenance:enable, maintenance:disable, maintenance:status)
 * - Cron management (cron:run, cron:install, cron:remove)
 * - Customer management (customer:hash:upgrade)
 * - Admin user management (admin:user:create, admin:user:unlock)
 *
 * Workflow:
 * 1. Accepts any Magento CLI command as arguments
 * 2. Selects or validates target workspace
 * 3. Verifies workspace is a Magento installation
 * 4. Changes to workspace directory
 * 5. Executes Magento CLI with provided arguments
 * 6. Streams output in real-time
 * 7. Reports success or failure
 *
 * Example usage:
 * ```bash
 * # Reindex all indexers
 * hive magento indexer:reindex --workspace=shop
 *
 * # Flush all caches
 * hive magento cache:flush -w shop
 *
 * # Deploy static content for production
 * hive magento setup:static-content:deploy en_US -f
 *
 * # Run setup upgrade after installing modules
 * hive magento setup:upgrade --keep-generated
 *
 * # Compile dependency injection
 * hive magento setup:di:compile
 *
 * # Enable a module
 * hive magento module:enable Vendor_Module -w shop
 *
 * # Set deployment mode to production
 * hive magento deploy:mode:set production
 *
 * # Create admin user
 * hive magento admin:user:create --admin-user=admin --admin-password=Admin123
 *
 * # Check module status
 * hive magento module:status
 *
 * # Using aliases
 * hive mage cache:flush -w shop
 * hive bin/magento indexer:reindex
 * ```
 *
 * Common options inherited from BaseCommand:
 * - --workspace, -w: Target specific workspace
 * - --force, -f: Force operation by ignoring cache
 * - --no-cache: Disable Turbo cache
 * - --no-interaction, -n: Run in non-interactive mode
 *
 * Performance tips:
 * - Use --keep-generated flag with setup:upgrade to speed up deployment
 * - Run setup:di:compile in production mode for better performance
 * - Use specific locales with setup:static-content:deploy to reduce deployment time
 * - Enable flat catalog for better frontend performance
 *
 * @see BaseCommand For inherited functionality and common options
 * @see InteractsWithMonorepo For workspace discovery
 * @see ComposerCommand For Composer operations
 * @see ArtisanCommand For Laravel Artisan operations
 * @see ConsoleCommand For Symfony console operations
 */
#[AsCommand(
    name: 'framework:magento',
    description: 'Run Magento CLI command in a workspace',
    aliases: ['magento', 'mage', 'bin/magento'],
)]
final class MagentoCommand extends BaseCommand
{
    /**
     * Configure the command options and arguments.
     *
     * Defines the command signature with flexible argument handling to accept
     * any Magento CLI command and its options. The command argument is defined as
     * an array to capture all parts of the Magento command including subcommands,
     * arguments, and flags.
     *
     * Common options like --workspace are inherited from BaseCommand, allowing
     * users to specify which Magento installation to target.
     *
     * The help text provides examples of common Magento commands and explains
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
                'The Magento CLI command to run (e.g., cache:flush, indexer:reindex, setup:upgrade)',
            )
            ->setHelp(
                <<<'HELP'
                The <info>magento</info> command runs Magento CLI commands in workspace contexts.

                <comment>Examples:</comment>
                  <info>hive magento indexer:reindex</info>
                  <info>hive magento cache:flush --workspace=shop</info>
                  <info>hive magento setup:upgrade --keep-generated</info>
                  <info>hive magento deploy:mode:set production</info>
                  <info>hive magento module:enable Vendor_Module</info>
                  <info>hive magento setup:static-content:deploy en_US -f</info>

                If no workspace is specified, you'll be prompted to select one.
                HELP
            );
    }

    /**
     * Execute the magento command.
     *
     * This method orchestrates the Magento CLI command execution:
     * 1. Extracts command arguments from user input
     * 2. Selects target workspace (interactive if not specified)
     * 3. Validates workspace exists and is a Magento installation
     * 4. Displays execution details
     * 5. Runs Magento CLI command in workspace directory
     * 6. Reports execution results
     *
     * The command uses passthru() to execute Magento CLI, which streams output
     * in real-time to the console. This is important for long-running commands
     * like static content deployment or reindexing.
     *
     * Magento installation detection is performed by checking for the presence
     * of the 'bin/magento' file in the workspace root. If not found, the command
     * fails with a helpful error message.
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Display intro banner
        $this->intro('Running Magento command...');

        // =====================================================================
        // COMMAND EXTRACTION
        // =====================================================================

        // Get the magento command arguments
        // These are passed as an array and need to be joined into a single string
        // Example: ['cache:flush', '--all'] becomes 'cache:flush --all'
        $commandArgs = $input->getArgument('command');
        $magentoCommand = implode(' ', $commandArgs);

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
        // MAGENTO INSTALLATION DETECTION
        // =====================================================================

        // Check if bin/magento file exists in the workspace
        // This confirms the workspace is a Magento installation
        if (! $this->filesystem()->exists("{$workspacePath}/bin/magento")) {
            $this->error("Magento CLI not found in workspace '{$workspace}'. Is this a Magento application?");

            return Command::FAILURE;
        }

        // =====================================================================
        // COMMAND EXECUTION
        // =====================================================================

        // Display execution details to user
        $this->info("Running: php bin/magento {$magentoCommand}");
        $this->comment("Workspace: {$workspace}");
        $this->line('');

        // Run magento command in workspace directory
        // passthru() streams output in real-time and captures exit code
        // The cd command ensures we're in the correct directory for Magento
        $fullCommand = "cd {$workspacePath} && php bin/magento {$magentoCommand}";
        passthru($fullCommand, $exitCode);

        // =====================================================================
        // RESULT REPORTING
        // =====================================================================

        // Report results to user based on exit code
        if ($exitCode === 0) {
            // Success - command completed without errors
            $this->outro('✓ Magento command completed successfully');
        } else {
            // Failure - command returned non-zero exit code
            $this->error('✗ Magento command failed');
        }

        return $exitCode;
    }
}
