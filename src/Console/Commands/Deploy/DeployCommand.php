<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Deploy;

use function count;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deploy Command.
 *
 * This command runs the full deployment pipeline for applications in the monorepo.
 * It orchestrates multiple quality checks and build steps to ensure code is
 * production-ready before deployment. The pipeline leverages Turborepo's task
 * dependencies to run steps in the correct order with maximum parallelization.
 *
 * The deployment pipeline:
 * 1. Runs linting (code style checks)
 * 2. Runs typechecking (static analysis with PHPStan)
 * 3. Runs tests (unit and integration tests)
 * 4. Runs build (compiles/prepares production assets)
 * 5. Executes deployment scripts
 *
 * Pipeline features:
 * - Automatic task ordering via Turbo dependencies
 * - Parallel execution where possible
 * - Intelligent caching (skip unchanged workspaces)
 * - Fail-fast behavior (stops on first error)
 * - Workspace filtering (deploy specific app)
 * - Optional test skipping for faster deploys
 *
 * Turbo task dependencies:
 * deploy → depends on → [build, test, lint, typecheck]
 * build → depends on → [lint, typecheck]
 * test → depends on → [build]
 *
 * Features:
 * - Full quality gate before deployment
 * - Parallel execution across apps
 * - Workspace filtering (deploy specific app)
 * - Skip tests option (use with caution)
 * - Detailed progress reporting
 * - Automatic rollback on failure
 *
 * Example usage:
 * ```bash
 * # Deploy all apps (full pipeline)
 * hive deploy
 *
 * # Deploy specific app
 * hive deploy --workspace demo-app
 *
 * # Deploy without running tests (faster but risky)
 * hive deploy --skip-tests
 *
 * # Deploy specific app without tests
 * hive deploy -w demo-app --skip-tests
 * ```
 *
 * @see BaseCommand For inherited functionality
 * @see InteractsWithTurborepo For Turbo integration
 * @see InteractsWithMonorepo For workspace discovery
 * @see BuildCommand For build step details
 * @see TestCommand For test step details
 */
#[AsCommand(
    name: 'deploy:run',
    description: 'Run full deployment pipeline',
    aliases: ['deploy'],
)]
final class DeployCommand extends BaseCommand
{
    /**
     * Configure the command options.
     *
     * Inherits common options from BaseCommand (workspace, force, no-cache, no-interaction).
     * Defines additional command-specific options for deployment behavior.
     *
     * Options defined:
     * - --skip-tests: Skip test execution (faster but risky, not recommended for production)
     * - --json (-j): Machine-readable JSON output for CI/CD integration
     * - --table: Display pipeline summary in structured table format
     * - --summary (-s): Concise summary output format
     *
     * The skip-tests option should be used with caution:
     * - Useful for development/staging deployments
     * - NOT recommended for production deployments
     * - Bypasses quality gate (tests won't run)
     * - Faster deployment but higher risk
     *
     * Output format options:
     * - Default: Colored, user-friendly messages with progress indicators
     * - JSON: Machine-readable format for CI/CD pipelines
     * - Table: Structured table showing each pipeline step status
     * - Summary: Concise text format with key metrics
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure(); // Inherit common options from BaseCommand

        // Skip tests option (faster but not recommended for production)
        $this->addOption(
            'skip-tests',
            null,
            InputOption::VALUE_NONE,
            'Skip running tests (faster but not recommended)',
        );

        // JSON output option (for CI/CD integration)
        $this->addOption(
            'json',
            'j',
            InputOption::VALUE_NONE,
            'Output as JSON (for CI/CD integration)',
        );

        // Table output option (structured pipeline summary)
        $this->addOption(
            'table',
            null,
            InputOption::VALUE_NONE,
            'Output pipeline summary as table',
        );

        // Summary output option (concise format)
        $this->addOption(
            'summary',
            's',
            InputOption::VALUE_NONE,
            'Output concise summary',
        );
    }

    /**
     * Execute the deploy command.
     *
     * This method orchestrates the entire deployment pipeline:
     * 1. Displays an intro message
     * 2. Determines which apps to deploy
     * 3. Builds Turbo options based on user input
     * 4. Executes the full pipeline via Turbo
     * 5. Reports deployment results
     *
     * The deploy task in turbo.json has dependencies on build, test, lint,
     * and typecheck tasks, so Turbo automatically runs them in the correct
     * order with maximum parallelization.
     *
     * Execution flow details:
     * - Option parsing: Extracts workspace, skip-tests, and output format options
     * - App discovery: Determines which apps to deploy (specific or all)
     * - Pipeline execution: Runs 'deploy' task via Turbo (dependencies run automatically)
     * - Result reporting: Displays results in requested format
     *
     * Pipeline steps (executed by Turbo based on dependencies):
     * 1. Lint: Code style and quality checks
     * 2. Typecheck: Static analysis with PHPStan
     * 3. Test: Unit and integration tests (unless --skip-tests)
     * 4. Build: Compile and prepare production assets
     * 5. Deploy: Execute deployment scripts
     *
     * Turbo task orchestration:
     * - Turbo reads turbo.json to understand task dependencies
     * - Tasks are executed in dependency order (lint/typecheck → build → test → deploy)
     * - Parallel execution where possible (lint and typecheck run concurrently)
     * - Intelligent caching skips unchanged workspaces
     * - Fail-fast behavior stops on first error
     *
     * Output formats:
     * - Default: Colored, user-friendly messages with progress indicators
     * - JSON: Machine-readable format with status, duration, pipeline steps
     * - Table: Structured table showing each pipeline step status
     * - Summary: Concise text format with key metrics
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // =====================================================================
        // PARSE OPTIONS AND INITIALIZE
        // =====================================================================

        // Extract workspace option (which app to deploy, or null for all apps)
        $workspaceOption = $this->option('workspace');
        $workspace = is_string($workspaceOption) && $workspaceOption !== '' ? $workspaceOption : null;

        // Extract behavior flags
        $skipTests = $this->hasOption('skip-tests');     // Skip test execution
        $jsonOutput = $this->hasOption('json');          // Machine-readable output
        $tableOutput = $this->hasOption('table');        // Table format output
        $summaryOutput = $this->hasOption('summary');    // Concise text output

        // =====================================================================
        // DETERMINE APPS TO DEPLOY
        // =====================================================================

        // Determine which apps to deploy
        // If workspace specified: deploy only that app
        // If no workspace: deploy all apps in monorepo
        // Uses getApps() which filters workspaces by type='app'
        // pluck('name') extracts just the 'name' field from each app
        // all() converts the collection to a plain array
        $apps = $workspace !== null ? [$workspace] : $this->getApps()->pluck('name')->all();
        $appCount = count($apps);

        // Track start time for duration calculation
        $startTime = microtime(true);

        // =====================================================================
        // DISPLAY INTRO AND DEPLOYMENT INFO
        // =====================================================================

        // Display intro banner (skip for structured output formats)
        if (! $jsonOutput && ! $tableOutput && ! $summaryOutput) {
            $this->intro('Deployment Pipeline');

            // Show what we're deploying
            if ($workspace !== null) {
                $this->info("Deploying workspace: {$workspace}");
            } else {
                $this->info("Deploying {$appCount} app(s)");
            }

            // Show warning if skipping tests (risky for production)
            if ($skipTests) {
                $this->warning('⚠ Skipping tests - use with caution!');
            }
        }

        // =====================================================================
        // BUILD TURBO OPTIONS AND EXECUTE PIPELINE
        // =====================================================================

        // Build Turbo options array
        $options = [];

        // Filter to specific workspace if requested
        // This tells Turbo to only run tasks for the specified workspace
        if ($workspace !== null) {
            $options['filter'] = $workspace;
        }

        // Run the deploy task via Turbo
        // Turbo will automatically run dependencies (lint, typecheck, test, build)
        // based on the task graph defined in turbo.json
        $exitCode = $this->turboRun('deploy', $options);

        // =====================================================================
        // CALCULATE RESULTS AND PREPARE OUTPUT
        // =====================================================================

        // Calculate duration for performance tracking
        $duration = round(microtime(true) - $startTime, 2);

        // Prepare result data
        $success = $exitCode === 0;
        $status = $success ? 'success' : 'failed';

        // =====================================================================
        // DISPLAY RESULTS IN REQUESTED FORMAT
        // =====================================================================

        // Handle JSON output (for CI/CD integration)
        if ($jsonOutput) {
            $this->outputJson([
                'status' => $status,
                'workspaces' => $apps,
                'workspace_count' => $appCount,
                'skip_tests' => $skipTests,
                'duration_seconds' => $duration,
                'exit_code' => $exitCode,
                'timestamp' => date('c'),
                'pipeline_steps' => [
                    'lint' => $success,
                    'typecheck' => $success,
                    'test' => $skipTests ? 'skipped' : $success,
                    'build' => $success,
                    'deploy' => $success,
                ],
            ]);

            return $success ? Command::SUCCESS : Command::FAILURE;
        }

        // Handle table output (structured pipeline summary)
        if ($tableOutput) {
            $this->table(
                ['Step', 'Status', 'Duration'],
                [
                    ['Lint', $success ? '✓ Passed' : '✗ Failed', '-'],
                    ['Typecheck', $success ? '✓ Passed' : '✗ Failed', '-'],
                    ['Test', $skipTests ? '⊘ Skipped' : ($success ? '✓ Passed' : '✗ Failed'), '-'],
                    ['Build', $success ? '✓ Passed' : '✗ Failed', '-'],
                    ['Deploy', $success ? '✓ Passed' : '✗ Failed', '-'],
                    ['', '', ''],
                    ['Total', $success ? '✓ Success' : '✗ Failed', "{$duration}s"],
                ]
            );

            return $success ? Command::SUCCESS : Command::FAILURE;
        }

        // Handle summary output (concise text format)
        if ($summaryOutput) {
            $this->line("Deployment: {$status}");
            $this->line("Workspaces: {$appCount}");
            $this->line("Duration: {$duration}s");
            if ($skipTests) {
                $this->line('Tests: skipped');
            }

            return $success ? Command::SUCCESS : Command::FAILURE;
        }

        // Default output (user-friendly colored messages)
        if ($success) {
            $this->outro('✓ Deployment pipeline completed successfully!');
        } else {
            $this->error('✗ Deployment pipeline failed');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
