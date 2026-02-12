<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Make;

use function exec;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;

use function preg_match;
use function str_contains;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function trim;

/**
 * Make Workspace Command.
 *
 * This command creates a new monorepo workspace by cloning the official
 * PhpHive template repository from GitHub. The template includes:
 * - Pre-configured Turborepo setup
 * - Sample application and package
 * - Complete monorepo structure
 * - All necessary configuration files
 *
 * The workspace creation process:
 * 1. Prompts for workspace name (or uses provided argument)
 * 2. Clones the template repository from GitHub
 * 3. Updates configuration with the new workspace name
 * 4. Initializes a fresh git repository
 * 5. Provides next steps for getting started
 *
 * Created structure:
 * ```
 * workspace-name/
 * ├── apps/                    # Application workspaces
 * │   └── sample-app/         # Sample skeleton app
 * ├── packages/                # Package workspaces
 * │   └── sample-package/     # Sample package
 * ├── bin/                     # Executable scripts
 * │   └── hive                # Hive CLI wrapper
 * ├── turbo.json               # Turborepo configuration
 * ├── pnpm-workspace.yaml      # pnpm workspace configuration
 * ├── package.json             # Root package.json
 * ├── composer.json            # Root composer.json
 * ├── .gitignore               # Git ignore patterns
 * └── README.md                # Project documentation
 * ```
 *
 * Features:
 * - Clones from official template repository
 * - Interactive prompts for workspace name
 * - Non-interactive mode support (--no-interaction)
 * - Automatic configuration updates
 * - Fresh git repository initialization
 * - Validation of workspace name
 * - Directory existence checks
 * - Beautiful CLI output with Laravel Prompts
 *
 * Common options inherited from BaseCommand:
 * - --no-interaction, -n: Run in non-interactive mode
 *
 * Example usage:
 * ```bash
 * # Interactive mode (prompts for name)
 * hive make:workspace
 *
 * # With workspace name
 * hive make:workspace my-project
 *
 * # Non-interactive mode
 * hive make:workspace my-project --no-interaction
 * ```
 *
 * After running this command:
 * 1. cd into the new workspace directory
 * 2. Run `pnpm install` to install Node dependencies
 * 3. Run `composer install` to install PHP dependencies
 * 4. Start developing!
 *
 * @see BaseCommand For inherited functionality and common options
 * @see CreateAppCommand For creating apps within workspace
 * @see CreatePackageCommand For creating packages within workspace
 */
#[AsCommand(
    name: 'make:workspace',
    description: 'Create a new workspace from template',
    aliases: ['init', 'new'],
)]
final class MakeWorkspaceCommand extends BaseCommand
{
    /**
     * Template repository URL.
     */
    private const string TEMPLATE_URL = 'https://github.com/pixielity-co/hive-template.git';

    /**
     * Configure the command options and arguments.
     *
     * Inherits common options from BaseCommand and defines workspace-specific
     * argument for the workspace name.
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Workspace name (e.g., my-project)',
            )
            ->setHelp(
                <<<'HELP'
                The <info>make:workspace</info> command creates a new workspace from the official template.

                <comment>Examples:</comment>
                  <info>hive make:workspace</info>                    Interactive mode
                  <info>hive make:workspace my-project</info>         With name
                  <info>hive make:workspace my-project -n</info>      Non-interactive

                The template includes:
                  - Sample app and package
                  - Turborepo configuration
                  - Complete monorepo structure
                  - All necessary config files

                After creation, run:
                  <info>cd workspace-name</info>
                  <info>pnpm install</info>
                  <info>composer install</info>
                HELP,
            );
    }

    /**
     * Execute the make:workspace command.
     *
     * This method orchestrates the entire workspace creation process:
     * 1. Runs preflight checks to validate environment
     * 2. Gets workspace name with smart suggestions if taken
     * 3. Validates the name
     * 4. Clones the template repository
     * 5. Updates configuration with new name
     * 6. Initializes fresh git repository
     * 7. Displays success message with next steps
     *
     * @param  InputInterface  $input   Command input (arguments and options)
     * @param  OutputInterface $_output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $_output): int
    {
        // Display intro banner
        $this->intro('Create New Workspace');

        // Step 1: Run preflight checks
        $this->info('Running environment checks...');
        $preflightResult = $this->runPreflightChecks();

        if ($preflightResult->failed()) {
            return Command::FAILURE;
        }

        $this->line('');

        // Step 2: Get and validate workspace name
        $name = $this->getValidatedWorkspaceName($input);

        // Step 3: Execute workspace creation with progress feedback
        $steps = [
            'Cloning workspace template' => fn () => $this->cloneTemplate($name),
            'Configuring workspace' => fn () => $this->updateWorkspaceConfig($name),
        ];

        $this->line('');
        $this->info("Creating workspace: {$name}");
        $this->line('');

        foreach ($steps as $message => $step) {
            $result = $this->spin($step, "{$message}...");

            if ($result !== 0 && $result !== true) {
                $this->error("Failed: {$message}");

                return Command::FAILURE;
            }

            $this->comment("✓ {$message} complete");
        }

        // Step 4: Display success summary
        $this->line('');
        $this->outro('🎉 Workspace created successfully!');
        $this->line('');
        $this->comment('Next steps:');
        $this->line("  1. cd {$name}");
        $this->line('  2. pnpm install');
        $this->line('  3. composer install');
        $this->line('  4. Start building!');

        return Command::SUCCESS;
    }

    /**
     * Run preflight checks to validate environment.
     */
    private function runPreflightChecks(): \PhpHive\Cli\Support\PreflightResult
    {
        $checker = new \PhpHive\Cli\Support\PreflightChecker($this->process());
        $result = $checker->check();

        // Display check results
        foreach ($result->checks as $checkName => $checkResult) {
            if ($checkResult['passed']) {
                $this->comment("✓ {$checkName}: {$checkResult['message']}");
            } else {
                $this->error("✗ {$checkName}: {$checkResult['message']}");

                if (isset($checkResult['fix'])) {
                    $this->line('');
                    $this->note($checkResult['fix'], 'Suggested fix');
                }
            }
        }

        if ($result->passed) {
            $this->line('');
            $this->info('✓ All checks passed');
        }

        return $result;
    }

    /**
     * Get and validate workspace name with smart suggestions.
     *
     * @return string Validated workspace name
     */
    private function getValidatedWorkspaceName(InputInterface $input): string
    {
        // Get workspace name from argument or prompt
        $name = $this->argument('name');

        if (($name === null || $name === '') && ! $input->isInteractive()) {
            // Non-interactive mode without name - error
            $this->error('Workspace name is required in non-interactive mode');
            exit(Command::FAILURE);
        }

        if ($name === null || $name === '') {
            // Interactive mode - prompt for name
            $name = $this->text(
                label: 'What is the workspace name?',
                placeholder: 'my-project',
                required: true,
                validate: $this->validateWorkspaceName(...),
            );
        }

        // Validate workspace name
        $validation = $this->validateWorkspaceName($name);

        if ($validation !== null) {
            $this->error($validation);
            exit(Command::FAILURE);
        }

        // Check if directory already exists
        if (! is_dir($name)) {
            $this->info("✓ Workspace name '{$name}' is available");

            return $name;
        }

        // Name is taken, offer suggestions
        $this->warning("Directory '{$name}' already exists");
        $this->line('');

        $suggestionService = new \PhpHive\Cli\Support\NameSuggestionService();
        $suggestions = $suggestionService->suggest(
            $name,
            'workspace',
            fn ($suggestedName) => ! is_dir($suggestedName)
        );

        if (count($suggestions) === 0) {
            $this->error('Could not generate alternative names. Please choose a different name.');
            exit(Command::FAILURE);
        }

        // Get the best suggestion
        $bestSuggestion = $suggestionService->getBestSuggestion($suggestions);

        // Display suggestions with recommendation
        $this->comment('Suggested names:');
        $index = 1;
        foreach ($suggestions as $suggestion) {
            $marker = $suggestion === $bestSuggestion ? ' (recommended)' : '';
            $this->line("  {$index}. {$suggestion}{$marker}");
            $index++;
        }

        $this->line('');

        // Let user select or enter custom name with best suggestion pre-filled
        $choice = $this->suggest(
            label: 'Choose an available name',
            options: $suggestions,
            placeholder: $bestSuggestion ?? 'Enter a custom name',
            default: $bestSuggestion ?? '',
            required: true
        );

        // Validate the chosen name
        if (is_dir($choice)) {
            $this->error("Directory '{$choice}' also exists. Please try again with a different name.");
            exit(Command::FAILURE);
        }

        $this->info("✓ Workspace name '{$choice}' is available");

        return $choice;
    }

    /**
     * Validate workspace name.
     *
     * Ensures the workspace name follows conventions:
     * - Not empty
     * - Contains only lowercase letters, numbers, and hyphens
     * - Starts with a letter
     * - No consecutive hyphens
     *
     * @param  string|null $name The workspace name to validate
     * @return string|null Error message if invalid, null if valid
     */
    private function validateWorkspaceName(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return 'Workspace name cannot be empty';
        }

        if (preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1) {
            return 'Workspace name must start with a letter and contain only lowercase letters, numbers, and hyphens';
        }

        if (str_contains($name, '--')) {
            return 'Workspace name cannot contain consecutive hyphens';
        }

        return null;
    }

    /**
     * Clone the template repository from GitHub.
     *
     * Clones the official PhpHive template repository and removes the .git
     * directory to allow for a fresh git initialization.
     *
     * @param  string $name Workspace name (directory to clone into)
     * @return int    Exit code (0 for success, non-zero for failure)
     */
    private function cloneTemplate(string $name): int
    {
        // Clone the template repository
        $process = Process::fromShellCommandline(
            'git clone ' . self::TEMPLATE_URL . " {$name}",
            null,
            null,
            null,
            300 // 5 minute timeout
        );
        $process->run();

        // Remove .git directory to start fresh
        if ($process->isSuccessful()) {
            $process = Process::fromShellCommandline("rm -rf {$name}/.git");
            $process->run();
        }

        return $process->getExitCode() ?? 1;
    }

    /**
     * Update workspace configuration with the new name.
     *
     * Updates package.json and composer.json with the new workspace name,
     * then initializes a fresh git repository.
     *
     * @param string $name Workspace name
     */
    private function updateWorkspaceConfig(string $name): void
    {
        $filesystem = $this->filesystem();

        // Update package.json
        $packageJsonPath = "{$name}/package.json";
        if ($filesystem->exists($packageJsonPath)) {
            $content = $filesystem->read($packageJsonPath);
            $packageJson = json_decode($content, true);
            if (is_array($packageJson)) {
                $packageJson['name'] = $name;
                $filesystem->write(
                    $packageJsonPath,
                    json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
                );
            }
        }

        // Update composer.json
        $composerJsonPath = "{$name}/composer.json";
        if ($filesystem->exists($composerJsonPath)) {
            $content = $filesystem->read($composerJsonPath);
            $composerJson = json_decode($content, true);
            if (is_array($composerJson)) {
                // Update name to vendor/package format
                $composerJson['name'] = "phphive/{$name}";
                $filesystem->write(
                    $composerJsonPath,
                    json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
                );
            }
        }

        // Initialize new git repository
        exec("cd {$name} && git init && git add . && git commit -m 'Initial commit from hive-template' 2>&1");
    }
}
