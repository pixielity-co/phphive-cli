<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Dev;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dev Command.
 *
 * This command starts the development server for an application workspace.
 * It provides an interactive workspace selector if no workspace is specified,
 * making it easy to start development with a single command. The command
 * leverages Turborepo to execute the dev task with proper workspace filtering.
 *
 * The development process:
 * 1. Identifies available application workspaces
 * 2. Prompts user to select if multiple apps exist
 * 3. Auto-selects if only one app exists
 * 4. Validates workspace exists
 * 5. Starts the dev server via Turbo
 * 6. Streams output in real-time
 *
 * Development workflow:
 * - Discovers all application workspaces (excludes packages)
 * - Provides interactive selection for multiple apps
 * - Auto-selects when only one app is available
 * - Validates workspace before starting server
 * - Executes dev script from workspace's package.json
 * - Streams server output to console in real-time
 *
 * Features:
 * - Interactive workspace selection
 * - Auto-selection for single app
 * - Workspace validation
 * - Custom port support (future)
 * - Real-time output streaming
 * - Graceful error handling
 * - Hot reload support (via workspace dev script)
 * - Automatic dependency watching
 *
 * Common options inherited from BaseCommand:
 * - --workspace, -w: Target specific workspace
 * - --force, -f: Force operation by ignoring cache
 * - --no-cache: Disable Turbo cache
 * - --no-interaction, -n: Run in non-interactive mode
 *
 * Example usage:
 * ```bash
 * # Start dev server (interactive selection)
 * hive dev
 *
 * # Start specific app
 * hive dev --workspace demo-app
 *
 * # Start with shorthand
 * hive dev -w calculator
 *
 * # Start with custom port (future)
 * hive dev --workspace demo-app --port 3000
 * ```
 *
 * @see BaseCommand For inherited functionality and common options
 * @see InteractsWithTurborepo For Turbo integration
 * @see InteractsWithMonorepo For workspace discovery
 * @see InteractsWithPrompts For interactive selection
 * @see BuildCommand For production builds
 */
#[AsCommand(
    name: 'dev:start',
    description: 'Start development server',
    aliases: ['dev'],
)]
final class DevCommand extends BaseCommand
{
    /**
     * Configure the command options.
     *
     * Inherits common options from BaseCommand (workspace, force, no-cache, no-interaction)
     * and adds command-specific options for development server customization.
     *
     * Options added:
     * - --port (-p): Custom port number for the development server (future feature)
     *
     * Note: The port option is currently a placeholder for future implementation.
     * Most development servers read port configuration from their own config files
     * or environment variables. This option will be implemented when workspace-level
     * port configuration is supported.
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();

        // Add port option for future development server customization
        // Currently a placeholder - will be implemented when workspace-level
        // port configuration is supported
        $this->addOption(
            'port',
            'p',
            InputOption::VALUE_REQUIRED,
            'Custom port number (future feature)',
        );
    }

    /**
     * Execute the dev command.
     *
     * This method orchestrates the development server startup process with
     * intelligent workspace selection and validation. It provides a seamless
     * developer experience by auto-selecting single apps or prompting for
     * selection when multiple apps are available.
     *
     * Execution flow:
     * 1. Check if workspace specified via --workspace option
     * 2. If not specified:
     *    a. Discover all application workspaces (excludes packages)
     *    b. Validate at least one app exists
     *    c. Auto-select if only one app found
     *    d. Prompt for selection if multiple apps found
     * 3. Validate selected workspace exists in monorepo
     * 4. Display intro banner with workspace name
     * 5. Execute dev task via Turbo with workspace filter
     * 6. Stream server output to console in real-time
     * 7. Return exit code based on server process
     *
     * Workspace discovery:
     * - Uses getApps() to find application workspaces
     * - Excludes package workspaces (libraries/shared code)
     * - Apps are identified by their location in apps/* directory
     * - Packages are in packages/* directory
     *
     * Interactive selection:
     * - Only apps (not packages) are shown in selection menu
     * - Apps are displayed by their workspace name
     * - Selection uses arrow keys and Enter
     * - Can be bypassed with --workspace option
     * - Skipped in non-interactive mode (--no-interaction)
     *
     * Auto-selection behavior:
     * - If only one app exists, it's automatically selected
     * - User is informed which app is starting
     * - No prompt is shown (faster workflow)
     * - Useful for monorepos with single app
     *
     * Turborepo integration:
     * - Executes 'dev' task from workspace's package.json
     * - Uses --filter to target specific workspace
     * - Streams output in real-time (not buffered)
     * - Respects workspace dependencies
     * - Supports hot reload if configured in workspace
     *
     * Development server behavior:
     * - Runs in foreground (blocking)
     * - Output streamed to console
     * - Ctrl+C stops the server
     * - Exit code propagated from server process
     * - Hot reload typically enabled by workspace config
     *
     * Error handling:
     * - No apps found: Error message and exit
     * - Workspace not found: Error message and exit
     * - Server startup failure: Exit code propagated
     * - Invalid workspace name: Validation error
     *
     * Exit codes:
     * - 0 (SUCCESS): Server started and stopped cleanly
     * - 1 (FAILURE): No apps found, workspace not found, or server error
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // =====================================================================
        // GET WORKSPACE FROM OPTION
        // =====================================================================

        // Extract workspace option if provided
        // null = no workspace specified, trigger interactive selection
        $workspaceOption = $this->option('workspace');
        $workspace = is_string($workspaceOption) && $workspaceOption !== '' ? $workspaceOption : null;

        // =====================================================================
        // INTERACTIVE WORKSPACE SELECTION
        // =====================================================================

        // If no workspace specified, provide interactive selection
        if ($workspace === null) {
            // Get all application workspaces (not packages)
            // Apps are typically in apps/* directory
            // Packages are in packages/* directory (excluded)
            $apps = $this->getApps();

            // Check if any apps exist in the monorepo
            if ($apps->isEmpty()) {
                $this->error('No apps found in the monorepo');

                return Command::FAILURE;
            }

            // Auto-select if only one app exists
            // This provides a faster workflow for single-app monorepos
            if ($apps->count() === 1) {
                $workspace = $apps->first()['name'];
                $this->info("Starting {$workspace}...");
            } else {
                // Multiple apps - let user choose interactively
                // Displays a selection menu with arrow key navigation
                $workspace = $this->select(
                    'Select app to run',
                    $apps->pluck('name')->all(),
                );
            }
        }

        // =====================================================================
        // VALIDATE WORKSPACE EXISTS
        // =====================================================================

        // Verify the selected workspace exists in the monorepo
        // This catches typos in --workspace option or invalid selections
        if (! $this->hasWorkspace($workspace)) {
            $this->error("Workspace '{$workspace}' not found");

            return Command::FAILURE;
        }

        // =====================================================================
        // DISPLAY INTRO BANNER
        // =====================================================================

        // Show which workspace is starting
        $this->intro("Starting Development Server: {$workspace}");

        // =====================================================================
        // START DEVELOPMENT SERVER VIA TURBOREPO
        // =====================================================================

        // Run the dev task via Turbo with workspace filter
        // Turbo will:
        // 1. Find the workspace's package.json
        // 2. Execute the 'dev' script defined in package.json
        // 3. Stream output to console in real-time
        // 4. Handle process signals (Ctrl+C)
        // 5. Return exit code from dev server process
        //
        // The 'filter' option ensures only the specified workspace runs
        // Dependencies are not started automatically (unlike build task)
        $exitCode = $this->turboRun('dev', [
            'filter' => $workspace,  // Only run for this workspace
        ]);

        // =====================================================================
        // RETURN EXIT CODE
        // =====================================================================

        // Return appropriate exit code
        // 0 = server started and stopped cleanly
        // Non-zero = server error or startup failure
        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
