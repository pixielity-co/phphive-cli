<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Make;

use Exception;

use function is_dir;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;
use PhpHive\Cli\Contracts\AppTypeInterface;
use PhpHive\Cli\Factories\AppTypeFactory;
use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Support\NameSuggestionService;
use PhpHive\Cli\Support\PreflightChecker;
use PhpHive\Cli\Support\PreflightResult;

use function str_replace;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Create App Command.
 *
 * This command scaffolds a complete PHP application structure within the monorepo's
 * apps/ directory using the app type system. It supports multiple application types
 * (Laravel, Symfony, Magento, Skeleton) with their specific configurations and
 * scaffolding requirements.
 *
 * The scaffolding process:
 * 1. Prompt user to select application type (if not provided)
 * 2. Collect configuration through interactive prompts
 * 3. Validate the application name doesn't already exist
 * 4. Create the application directory
 * 5. Run the app type's installation command (e.g., composer create-project)
 * 6. Copy and process stub template files
 * 7. Run post-installation commands (migrations, compilation, etc.)
 * 8. Display next steps to the user
 *
 * Supported app types:
 * - Laravel: Full-stack PHP framework with Breeze/Jetstream, Sanctum, Octane
 * - Symfony: High-performance framework with Maker, Security, Doctrine
 * - Magento: Enterprise e-commerce with Elasticsearch, Redis, sample data
 * - Skeleton: Minimal PHP application with Composer and optional tools
 *
 * Example usage:
 * ```bash
 * # Interactive mode - prompts for app type
 * hive create:app my-app
 *
 * # Specify app type directly
 * hive create:app my-app --type=laravel
 * hive create:app shop --type=magento
 * hive create:app api --type=symfony
 *
 * # Using aliases
 * hive make:app dashboard --type=laravel
 * hive new:app service --type=skeleton
 * ```
 *
 * After creation workflow:
 * ```bash
 * # Navigate to the new app
 * cd apps/my-app
 *
 * # Dependencies are already installed by the scaffolding process
 *
 * # Start development server (varies by app type)
 * hive dev --workspace=my-app
 * ```
 *
 * @see BaseCommand For inherited functionality
 * @see AppTypeFactory For app type management
 * @see AppTypeInterface For app type contract
 * @see Filesystem For file operations
 */
#[AsCommand(
    name: 'make:app',
    description: 'Create a new application',
    aliases: ['create:app', 'new:app'],
)]
final class CreateAppCommand extends BaseCommand
{
    /**
     * Configure the command options and arguments.
     *
     * Defines the command signature with required arguments, options, and help text.
     * The name argument is required and should be a valid directory name.
     * The type option allows specifying the app type directly.
     *
     * Common options (workspace, force, no-cache, no-interaction) are inherited from BaseCommand.
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Application name (e.g., admin, api, shop)',
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Application type (laravel, symfony, magento, skeleton)',
            )
            ->addOption(
                'description',
                'd',
                InputOption::VALUE_REQUIRED,
                'Application description',
            )
            // Magento-specific options
            ->addOption(
                'magento-edition',
                null,
                InputOption::VALUE_REQUIRED,
                'Magento edition (community, enterprise)',
            )
            ->addOption(
                'magento-version',
                null,
                InputOption::VALUE_REQUIRED,
                'Magento version (2.4.5, 2.4.6, 2.4.7)',
            )
            ->addOption(
                'magento-public-key',
                null,
                InputOption::VALUE_REQUIRED,
                'Magento public key (username) from marketplace.magento.com',
            )
            ->addOption(
                'magento-private-key',
                null,
                InputOption::VALUE_REQUIRED,
                'Magento private key (password) from marketplace.magento.com',
            )
            ->addOption(
                'db-host',
                null,
                InputOption::VALUE_REQUIRED,
                'Database host',
            )
            ->addOption(
                'db-port',
                null,
                InputOption::VALUE_REQUIRED,
                'Database port',
            )
            ->addOption(
                'db-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Database name',
            )
            ->addOption(
                'db-user',
                null,
                InputOption::VALUE_REQUIRED,
                'Database username',
            )
            ->addOption(
                'db-password',
                null,
                InputOption::VALUE_REQUIRED,
                'Database password',
            )
            ->addOption(
                'admin-firstname',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin first name',
            )
            ->addOption(
                'admin-lastname',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin last name',
            )
            ->addOption(
                'admin-email',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin email address',
            )
            ->addOption(
                'admin-user',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin username',
            )
            ->addOption(
                'admin-password',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin password',
            )
            ->addOption(
                'base-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Store base URL',
            )
            ->addOption(
                'language',
                null,
                InputOption::VALUE_REQUIRED,
                'Default language (en_US, en_GB, fr_FR, de_DE, es_ES)',
            )
            ->addOption(
                'currency',
                null,
                InputOption::VALUE_REQUIRED,
                'Default currency (USD, EUR, GBP)',
            )
            ->addOption(
                'timezone',
                null,
                InputOption::VALUE_REQUIRED,
                'Default timezone',
            )
            ->addOption(
                'sample-data',
                null,
                InputOption::VALUE_NONE,
                'Install sample data',
            )
            ->addOption(
                'elasticsearch',
                null,
                InputOption::VALUE_NONE,
                'Use Elasticsearch for search',
            )
            ->addOption(
                'elasticsearch-host',
                null,
                InputOption::VALUE_REQUIRED,
                'Elasticsearch host',
            )
            ->addOption(
                'elasticsearch-port',
                null,
                InputOption::VALUE_REQUIRED,
                'Elasticsearch port',
            )
            ->addOption(
                'redis',
                null,
                InputOption::VALUE_NONE,
                'Use Redis for caching',
            )
            ->addOption(
                'redis-host',
                null,
                InputOption::VALUE_REQUIRED,
                'Redis host',
            )
            ->addOption(
                'redis-port',
                null,
                InputOption::VALUE_REQUIRED,
                'Redis port',
            )
            ->addOption(
                'use-redis',
                null,
                InputOption::VALUE_NONE,
                'Enable Redis for caching and sessions',
            )
            ->addOption(
                'use-elasticsearch',
                null,
                InputOption::VALUE_NONE,
                'Enable Elasticsearch for search',
            )
            ->addOption(
                'use-meilisearch',
                null,
                InputOption::VALUE_NONE,
                'Enable Meilisearch for search',
            )
            ->addOption(
                'use-minio',
                null,
                InputOption::VALUE_NONE,
                'Enable MinIO for object storage',
            )
            ->addOption(
                'use-docker',
                null,
                InputOption::VALUE_NONE,
                'Use Docker for database (if available)',
            )
            ->addOption(
                'no-docker',
                null,
                InputOption::VALUE_NONE,
                'Do not use Docker for database',
            )
            ->setHelp(
                <<<'HELP'
                The <info>create:app</info> command scaffolds a new PHP application.

                <comment>Examples:</comment>
                  <info>hive create:app admin</info>
                  <info>hive create:app shop --type=magento</info>
                  <info>hive create:app api --type=symfony</info>

                <comment>Magento with flags:</comment>
                  <info>hive create:app shop --type=magento \
                    --description="E-commerce store" \
                    --magento-version=2.4.7 \
                    --magento-public-key=YOUR_PUBLIC_KEY \
                    --magento-private-key=YOUR_PRIVATE_KEY \
                    --db-name=shop_db \
                    --db-user=shop_user \
                    --db-password=secret \
                    --admin-firstname=Admin \
                    --admin-lastname=User \
                    --admin-email=admin@example.com \
                    --admin-user=admin \
                    --admin-password=Admin123! \
                    --base-url=http://localhost/ \
                    --language=en_US \
                    --currency=USD \
                    --timezone=America/New_York</info>

                Available app types: laravel, symfony, magento, skeleton
                HELP
            );
    }

    /**
     * Execute the create app command.
     *
     * This method orchestrates the entire application scaffolding process:
     * 1. Runs preflight checks to validate environment
     * 2. Validates application name with smart suggestions
     * 3. Prompts for app type selection (if not provided)
     * 4. Collects configuration through app type prompts
     * 5. Creates the application directory
     * 6. Runs the app type's installation command
     * 7. Copies and processes stub template files
     * 8. Runs post-installation commands
     * 9. Displays next steps to the user
     *
     * The command will fail if an application with the same name already
     * exists in the apps/ directory to prevent accidental overwrites.
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Display intro banner
        $this->intro('Application Creation');

        // Step 1: Run preflight checks
        $this->info('Running environment checks...');
        $preflightResult = $this->runPreflightChecks();

        if ($preflightResult->failed()) {
            return Command::FAILURE;
        }

        $this->line('');

        // Step 2: Get and validate application name with smart suggestions
        $name = $this->getValidatedAppName($input);

        // Step 3: APP TYPE SELECTION
        $typeOption = $input->getOption('type');
        if ($typeOption !== null && $typeOption !== '') {
            $appTypeId = $typeOption;

            // Validate the provided app type
            if (! AppTypeFactory::isValid($appTypeId)) {
                $this->error("Invalid app type: {$appTypeId}");
                $this->line('Available types: ' . implode(', ', AppTypeFactory::getIdentifiers()));

                return Command::FAILURE;
            }
        } else {
            // Prompt user to select app type
            $appTypeId = $this->select(
                label: 'Select application type',
                options: AppTypeFactory::getChoices()
            );
        }

        // Create the app type instance
        $appType = AppTypeFactory::create($appTypeId);
        $this->comment("Selected: {$appType->getName()}");

        // Step 4: CONFIGURATION COLLECTION
        $this->line('');
        $this->comment('Configuration:');

        $config = $appType->collectConfiguration($input, $output);

        // Set name and description from command arguments/options
        $config[AppTypeInterface::CONFIG_NAME] = $name;

        // Set description if not already set by collectConfiguration (from --description option)
        if (! isset($config[AppTypeInterface::CONFIG_DESCRIPTION]) || $config[AppTypeInterface::CONFIG_DESCRIPTION] === '') {
            $config[AppTypeInterface::CONFIG_DESCRIPTION] = "A {$appType->getName()} application";
        }

        // Step 5: Execute application creation with progress feedback
        $root = $this->getMonorepoRoot();
        $appPath = "{$root}/apps/{$name}";
        $filesystem = $this->filesystem();

        $steps = [
            'Creating application structure' => fn (): bool => $this->createAppStructure($appPath, $filesystem),
            'Running pre-installation setup' => fn (): bool => $this->runPreInstallCommands($appType, $config, $appPath),
            'Installing application framework' => fn (): bool => $this->runInstallCommand($appType, $config, $appPath),
            'Processing configuration files' => fn () => $this->processStubs($appType, $config, $appPath, $filesystem),
            'Running post-installation tasks' => fn (): bool => $this->runPostInstallCommands($appType, $config, $appPath),
        ];

        $this->line('');
        foreach ($steps as $message => $step) {
            $result = $this->spin($step, "{$message}...");

            if ($result === false) {
                return Command::FAILURE;
            }

            $this->comment("✓ {$message} complete");
        }

        // Step 6: Display success summary
        $this->line('');
        $this->outro("🎉 Application '{$name}' created successfully!");
        $this->line('');
        $this->comment('Next steps:');
        $this->line("  1. cd apps/{$name}");
        $this->line('  2. Review the generated files');
        $this->line("  3. hive dev --workspace={$name}");

        return Command::SUCCESS;
    }

    /**
     * Run preflight checks to validate environment.
     */
    private function runPreflightChecks(): PreflightResult
    {
        $preflightChecker = new PreflightChecker($this->process());
        $preflightResult = $preflightChecker->check();

        // Display check results
        foreach ($preflightResult->checks as $checkName => $checkResult) {
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

        if ($preflightResult->passed) {
            $this->line('');
            $this->info('✓ All checks passed');
        }

        return $preflightResult;
    }

    /**
     * Get and validate application name with smart suggestions.
     *
     * @return string Validated application name
     */
    private function getValidatedAppName(InputInterface $input): string
    {
        $name = $input->getArgument('name');

        // Validate the name format first
        $validation = $this->validateAppName($name);
        if ($validation !== null) {
            $this->error($validation);
            exit(Command::FAILURE);
        }

        $root = $this->getMonorepoRoot();
        $appPath = "{$root}/apps/{$name}";

        // Check if name is available
        if (! is_dir($appPath)) {
            $this->info("✓ Application name '{$name}' is available");

            return $name;
        }

        // Name is taken, offer suggestions
        $this->warning("Application '{$name}' already exists");
        $this->line('');

        $nameSuggestionService = new NameSuggestionService();
        $suggestions = $nameSuggestionService->suggest(
            $name,
            'app',
            fn (?string $suggestedName): bool => $this->validateAppName($suggestedName) === null && ! is_dir("{$root}/apps/{$suggestedName}")
        );

        if ($suggestions === []) {
            $this->error('Could not generate alternative names. Please choose a different name.');
            exit(Command::FAILURE);
        }

        // Get the best suggestion
        $bestSuggestion = $nameSuggestionService->getBestSuggestion($suggestions);

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

        // Validate the chosen name format
        $validation = $this->validateAppName($choice);
        if ($validation !== null) {
            $this->error($validation);
            exit(Command::FAILURE);
        }

        // Validate the chosen name availability
        $chosenPath = "{$root}/apps/{$choice}";
        if (is_dir($chosenPath)) {
            $this->error("Application '{$choice}' also exists. Please try again with a different name.");
            exit(Command::FAILURE);
        }

        $this->info("✓ Application name '{$choice}' is available");

        return $choice;
    }

    /**
     * Validate application name.
     *
     * Ensures the application name follows conventions:
     * - Not empty
     * - Contains only lowercase letters and hyphens
     * - Starts with a letter
     * - No consecutive hyphens
     * - No numbers (since we generate PHP namespaces from names)
     *
     * @param  string|null $name The application name to validate
     * @return string|null Error message if invalid, null if valid
     */
    private function validateAppName(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return 'Application name cannot be empty';
        }

        if (preg_match('/^[a-z][a-z-]*$/', $name) !== 1) {
            return 'Application name must start with a letter and contain only lowercase letters and hyphens (no numbers)';
        }

        if (str_contains($name, '--')) {
            return 'Application name cannot contain consecutive hyphens';
        }

        return null;
    }

    /**
     * Create application directory structure.
     *
     * @param  string     $appPath    Full application path
     * @param  Filesystem $filesystem Filesystem service
     * @return bool       True on success
     */
    private function createAppStructure(string $appPath, Filesystem $filesystem): bool
    {
        try {
            $filesystem->makeDirectory($appPath, 0755, true);

            return true;
        } catch (Exception $exception) {
            $this->error("Failed to create application directory: {$exception->getMessage()}");

            return false;
        }
    }

    /**
     * Run pre-installation commands.
     *
     * @param  AppTypeInterface     $appType App type instance
     * @param  array<string, mixed> $config  Configuration
     * @param  string               $appPath Application path
     * @return bool                 True on success
     */
    private function runPreInstallCommands(AppTypeInterface $appType, array $config, string $appPath): bool
    {
        $preCommands = $appType->getPreInstallCommands($config);
        if ($preCommands === []) {
            return true;
        }

        foreach ($preCommands as $preCommand) {
            $exitCode = $this->executeCommand($preCommand, $appPath);
            if ($exitCode !== 0) {
                $this->error("Pre-installation command failed: {$preCommand}");

                return false;
            }
        }

        return true;
    }

    /**
     * Run installation command.
     *
     * @param  AppTypeInterface     $appType App type instance
     * @param  array<string, mixed> $config  Configuration
     * @param  string               $appPath Application path
     * @return bool                 True on success
     */
    private function runInstallCommand(AppTypeInterface $appType, array $config, string $appPath): bool
    {
        $installCommand = $appType->getInstallCommand($config);
        if ($installCommand === '') {
            return true;
        }

        $exitCode = $this->executeCommand($installCommand, $appPath);
        if ($exitCode !== 0) {
            $this->error('Installation command failed');

            return false;
        }

        return true;
    }

    /**
     * Run post-installation commands.
     *
     * @param  AppTypeInterface     $appType App type instance
     * @param  array<string, mixed> $config  Configuration
     * @param  string               $appPath Application path
     * @return bool                 True on success (warnings don't fail)
     */
    private function runPostInstallCommands(AppTypeInterface $appType, array $config, string $appPath): bool
    {
        $postCommands = $appType->getPostInstallCommands($config);
        if ($postCommands === []) {
            return true;
        }

        foreach ($postCommands as $postCommand) {
            $exitCode = $this->executeCommand($postCommand, $appPath);
            if ($exitCode !== 0) {
                // Log warning but continue
                $this->warning("Command had issues: {$postCommand}");
            }
        }

        return true;
    }

    /**
     * Execute a shell command in a specific directory.
     *
     * Runs a shell command in the specified working directory and returns
     * the exit code. Output is displayed in real-time to the console.
     *
     * @param  string $command The shell command to execute
     * @param  string $cwd     The working directory for the command
     * @return int    The command exit code (0 for success)
     */
    private function executeCommand(string $command, string $cwd): int
    {
        // Use Symfony Process to execute command in specific directory
        $process = Process::fromShellCommandline($command, $cwd, null, null, null);

        // Run the process and display output in real-time
        $process->run(function ($type, $buffer): void {
            echo $buffer;
        });

        return $process->getExitCode() ?? 1;
    }

    /**
     * Process stub template files.
     *
     * Copies stub template files from the app type's stub directory to the
     * application directory, replacing placeholders with actual values.
     *
     * @param AppTypeInterface     $appType    The app type instance
     * @param array<string, mixed> $config     Configuration array
     * @param string               $appPath    Application directory path
     * @param Filesystem           $filesystem Filesystem helper instance
     */
    private function processStubs(AppTypeInterface $appType, array $config, string $appPath, Filesystem $filesystem): void
    {
        // Get the stub directory path for this app type
        $stubPath = $appType->getStubPath();

        // Check if stub directory exists
        if (! is_dir($stubPath)) {
            $this->warning("Stub directory not found: {$stubPath}");

            return;
        }

        // Get stub variables for replacement
        $variables = $appType->getStubVariables($config);

        // Get all stub files recursively
        $stubFiles = $this->getStubFiles($stubPath);

        // Process each stub file
        foreach ($stubFiles as $stubFile) {
            // Get relative path from stub directory
            $relativePath = str_replace($stubPath . '/', '', $stubFile);

            // Check if this is an append stub
            $isAppendStub = str_ends_with($relativePath, '.append.stub');

            // Remove .stub or .append.stub extension for target file
            $targetPath = $isAppendStub ? str_replace('.append.stub', '', $relativePath) : str_replace('.stub', '', $relativePath);

            // Read stub content
            $content = $filesystem->read($stubFile);

            // Replace placeholders with actual values
            $processedContent = str_replace(
                array_keys($variables),
                array_values($variables),
                $content
            );

            // Write to target location
            $targetFile = "{$appPath}/{$targetPath}";

            // Create directory if it doesn't exist
            $targetDir = dirname($targetFile);
            if (! is_dir($targetDir)) {
                $filesystem->makeDirectory($targetDir, 0755, true);
            }

            // Handle append vs replace
            if ($isAppendStub && $filesystem->exists($targetFile)) {
                // Append to existing file
                $existingContent = $filesystem->read($targetFile);
                $filesystem->write($targetFile, $existingContent . $processedContent);
            } else {
                // Write/overwrite the file
                $filesystem->write($targetFile, $processedContent);
            }
        }
    }

    /**
     * Get all stub files recursively.
     *
     * Scans the stub directory and returns an array of all .stub files.
     *
     * @param  string        $directory The directory to scan
     * @return array<string> Array of stub file paths
     */
    private function getStubFiles(string $directory): array
    {
        $files = [];

        // Get all items in the directory
        $items = scandir($directory);
        if ($items === false) {
            return [];
        }

        foreach ($items as $item) {
            // Skip . and ..
            if ($item === '.') {
                continue;
            }
            if ($item === '..') {
                continue;
            }
            $path = "{$directory}/{$item}";

            // If it's a directory, recurse into it
            if (is_dir($path)) {
                $files = [...$files, ...$this->getStubFiles($path)];
            }
            // If it's a .stub file, add it to the list
            elseif (str_ends_with($item, '.stub')) {
                $files[] = $path;
            }
        }

        return $files;
    }
}
