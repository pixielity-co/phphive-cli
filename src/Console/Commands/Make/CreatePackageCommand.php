<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Make;

use Exception;
use InvalidArgumentException;

use function is_dir;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;
use PhpHive\Cli\Contracts\PackageTypeInterface;
use PhpHive\Cli\Factories\PackageTypeFactory;
use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Support\NameSuggestionService;
use PhpHive\Cli\Support\PreflightChecker;
use PhpHive\Cli\Support\PreflightResult;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function str_replace;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create Package Command.
 *
 * This command scaffolds a reusable PHP library package within the monorepo's
 * packages/ directory. Packages are shared libraries that can be used by
 * multiple applications within the monorepo, promoting code reuse and
 * maintaining a clean separation of concerns.
 *
 * The scaffolding process:
 * 1. Validates the package name doesn't already exist
 * 2. Creates the directory structure (src, tests)
 * 3. Generates composer.json with library configuration
 * 4. Generates package.json with Turbo task definitions
 * 5. Creates PHPUnit configuration for testing
 * 6. Generates README.md with usage instructions
 * 7. Adds .gitkeep files to preserve empty directories
 *
 * Directory structure created:
 * packages/{name}/
 * ├── src/              # Package source code (PSR-4)
 * ├── tests/            # Test files
 * │   └── Unit/         # Unit tests
 * ├── composer.json     # PHP dependencies and autoloading
 * ├── package.json      # Turbo tasks and npm scripts
 * ├── phpunit.xml       # PHPUnit configuration
 * └── README.md         # Documentation
 *
 * Generated composer.json includes:
 * - PHP 8.2+ requirement
 * - Library type designation
 * - PHPUnit, PHPStan, and Pint dev dependencies
 * - PSR-4 autoloading configuration
 * - Proper namespace based on package name
 *
 * Generated package.json includes Turbo tasks:
 * - test: Run PHPUnit tests
 * - test:unit: Run unit tests only
 * - lint: Check code style with Pint
 * - format: Fix code style with Pint
 * - typecheck: Run PHPStan static analysis
 * - clean: Remove cache files
 *
 * PHPUnit configuration includes:
 * - Bootstrap with Composer autoloader
 * - Color output enabled
 * - Fail on risky tests and warnings
 * - Code coverage configuration
 * - Test suite definitions
 *
 * Naming conventions:
 * - Package names use kebab-case (e.g., logger, http-client)
 * - Namespaces use PascalCase (e.g., PhpHive\Logger)
 * - Composer names use phphive/{name} format
 * - NPM names use @phphive/{name} format
 *
 * Package vs Application:
 * - Packages are libraries (type: library)
 * - Applications are projects (type: project)
 * - Packages don't have public/ or config/ directories
 * - Packages focus on reusable functionality
 * - Packages can be required by multiple apps
 *
 * Features:
 * - Automatic namespace generation from package name
 * - Pre-configured with monorepo best practices
 * - Ready for Turbo task execution
 * - Complete testing infrastructure
 * - Configured for code quality tools
 * - PSR-4 autoloading ready
 *
 * Example usage:
 * ```bash
 * # Create a logging package
 * hive create:package logger
 *
 * # Create an HTTP client package
 * hive create:package http-client
 *
 * # Create a validation package
 * hive create:package validator
 *
 * # Using aliases
 * hive make:package database
 * hive new:package cache
 * ```
 *
 * After creation workflow:
 * ```bash
 * # Navigate to the new package
 * cd packages/logger
 *
 * # Install dependencies
 * composer install
 *
 * # Start coding in src/
 * # Add your classes and interfaces
 *
 * # Run tests
 * composer test
 *
 * # Use in an application
 * # Add to app's composer.json:
 * # "require": { "phphive/logger": "*" }
 * ```
 *
 * @see BaseCommand For inherited functionality
 * @see CreateAppCommand For creating applications
 * @see InteractsWithMonorepo For workspace discovery
 * @see Filesystem For file operations
 */
#[AsCommand(
    name: 'make:package',
    description: 'Create a new package',
    aliases: ['create:package', 'new:package'],
)]
final class CreatePackageCommand extends BaseCommand
{
    /**
     * Configure the command options and arguments.
     *
     * Defines the command signature with required arguments, options, and help text.
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Package name (e.g., logger, http-client)',
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Package type (laravel, symfony, magento, skeleton)',
            )
            ->addOption(
                'description',
                'd',
                InputOption::VALUE_REQUIRED,
                'Package description',
            );
    }

    /**
     * Execute the create package command.
     *
     * This method orchestrates the entire package scaffolding process:
     * 1. Runs preflight checks to validate environment
     * 2. Extracts and validates the package name with smart suggestions
     * 3. Prompts for package type if not provided
     * 4. Creates the complete directory structure using stubs
     * 5. Processes stub templates with variables
     * 6. Installs dependencies with progress feedback
     * 7. Displays next steps to the user
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Display intro banner
        $this->intro('Package Creation');

        // Step 1: Run preflight checks
        $this->info('Running environment checks...');
        $preflightResult = $this->runPreflightChecks();

        if ($preflightResult->failed()) {
            return Command::FAILURE;
        }

        $this->line('');

        // Step 2: Get and validate package name with smart suggestions
        $name = $this->getValidatedPackageName($input);

        // Step 3: Determine package type (prompt if not provided)
        $type = $input->getOption('type');
        $packageTypeFactory = new PackageTypeFactory($this->composerService());

        if ($type === null) {
            $type = $this->select(
                label: 'Select package type',
                options: $packageTypeFactory->getTypeOptions(),
                default: 'skeleton'
            );
        }

        // Validate and create package type instance
        try {
            $packageType = $packageTypeFactory->create($type);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->error($invalidArgumentException->getMessage());

            return Command::FAILURE;
        }

        $this->line('');
        $this->comment("Selected: {$packageType->getDisplayName()}");
        $this->line('');

        // Step 4: Execute package creation steps with progress feedback
        $root = $this->getMonorepoRoot();
        $packagePath = "{$root}/packages/{$name}";

        $steps = [
            'Checking name availability' => fn (): bool => $this->checkNameAvailability($name, $packagePath),
            'Creating package structure' => fn (): bool => $this->createPackageStructure($packagePath),
            'Generating configuration files' => fn (): bool => $this->generateConfigFiles($input, $name, $type, $packagePath, $packageType),
        ];

        foreach ($steps as $message => $step) {
            $result = $this->spin($step, "{$message}...");

            if ($result === false) {
                return Command::FAILURE;
            }

            $this->comment("✓ {$message} complete");
        }

        // Step 5: Install dependencies with progress feedback
        $this->line('');
        $installResult = $this->spin(
            fn (): bool => $this->installDependencies($packageType, $packagePath),
            'Installing dependencies...'
        );

        if ($installResult) {
            $this->comment('✓ Dependencies installed successfully');
        } else {
            $this->warning('⚠ Dependency installation had issues (you may need to run composer install manually)');
        }

        // Step 6: Display success summary
        $this->line('');
        $this->outro("🎉 Package '{$name}' created successfully!");
        $this->line('');
        $this->comment('Next steps:');
        $this->line("  1. cd packages/{$name}");
        $this->line('  2. Start coding in src/');
        $this->line('  3. Run tests with: composer test');

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
     * Get and validate package name with smart suggestions.
     *
     * @return string Validated package name
     */
    private function getValidatedPackageName(InputInterface $input): string
    {
        $name = $input->getArgument('name');

        // Validate the name format first
        $validation = $this->validatePackageName($name);
        if ($validation !== null) {
            $this->error($validation);
            exit(Command::FAILURE);
        }

        $root = $this->getMonorepoRoot();
        $packagePath = "{$root}/packages/{$name}";

        // Check if name is available
        if (! is_dir($packagePath)) {
            $this->info("✓ Package name '{$name}' is available");

            return $name;
        }

        // Name is taken, offer suggestions
        $this->warning("Package '{$name}' already exists");
        $this->line('');

        $nameSuggestionService = new NameSuggestionService();
        $suggestions = $nameSuggestionService->suggest(
            $name,
            'package',
            fn (?string $suggestedName): bool => $this->validatePackageName($suggestedName) === null && ! is_dir("{$root}/packages/{$suggestedName}")
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
        $validation = $this->validatePackageName($choice);
        if ($validation !== null) {
            $this->error($validation);
            exit(Command::FAILURE);
        }

        // Validate the chosen name availability
        $chosenPath = "{$root}/packages/{$choice}";
        if (is_dir($chosenPath)) {
            $this->error("Package '{$choice}' also exists. Please try again with a different name.");
            exit(Command::FAILURE);
        }

        $this->info("✓ Package name '{$choice}' is available");

        return $choice;
    }

    /**
     * Validate package name.
     *
     * Ensures the package name follows conventions:
     * - Not empty
     * - Contains only lowercase letters and hyphens
     * - Starts with a letter
     * - No consecutive hyphens
     * - No numbers (since we generate PHP namespaces from names)
     *
     * @param  string|null $name The package name to validate
     * @return string|null Error message if invalid, null if valid
     */
    private function validatePackageName(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return 'Package name cannot be empty';
        }

        if (preg_match('/^[a-z][a-z-]*$/', $name) !== 1) {
            return 'Package name must start with a letter and contain only lowercase letters and hyphens (no numbers)';
        }

        if (str_contains($name, '--')) {
            return 'Package name cannot contain consecutive hyphens';
        }

        return null;
    }

    /**
     * Check if package name is available.
     *
     * @param  string $name        Package name
     * @param  string $packagePath Full package path
     * @return bool   True if available
     */
    private function checkNameAvailability(string $name, string $packagePath): bool
    {
        if (is_dir($packagePath)) {
            $this->error("Package '{$name}' already exists");

            return false;
        }

        return true;
    }

    /**
     * Create package directory structure.
     *
     * @param  string $packagePath Full package path
     * @return bool   True on success
     */
    private function createPackageStructure(string $packagePath): bool
    {
        try {
            $this->filesystem()->makeDirectory($packagePath, 0755, true);

            return true;
        } catch (Exception $exception) {
            $this->error("Failed to create package directory: {$exception->getMessage()}");

            return false;
        }
    }

    /**
     * Generate configuration files from stubs.
     *
     * @param  InputInterface       $input       Command input
     * @param  string               $name        Package name
     * @param  string               $type        Package type
     * @param  string               $packagePath Full package path
     * @param  PackageTypeInterface $packageType Package type instance
     * @return bool                 True on success
     */
    private function generateConfigFiles(InputInterface $input, string $name, string $type, string $packagePath, PackageTypeInterface $packageType): bool
    {
        try {
            // Get stub path for the selected package type
            $stubsBasePath = dirname(__DIR__, 4) . '/stubs';
            $stubPath = $packageType->getStubPath($stubsBasePath);

            if (! is_dir($stubPath)) {
                $this->error("Stub directory not found for package type '{$type}' at: {$stubPath}");

                return false;
            }

            // Prepare stub variables using package type
            $description = $input->getOption('description') ?? "A {$type} package";
            $variables = $packageType->prepareVariables($name, $description);

            // Copy and process all stub files with package type naming rules
            $this->copyStubFiles($stubPath, $packagePath, $variables, $this->filesystem(), $packageType->getFileNamingRules());

            return true;
        } catch (Exception $exception) {
            $this->error("Failed to generate configuration files: {$exception->getMessage()}");

            return false;
        }
    }

    /**
     * Install package dependencies.
     *
     * @param  PackageTypeInterface $packageType Package type instance
     * @param  string               $packagePath Full package path
     * @return bool                 True on success
     */
    private function installDependencies(PackageTypeInterface $packageType, string $packagePath): bool
    {
        try {
            $packageType->postCreate($packagePath);

            return true;
        } catch (Exception) {
            // Log error but don't fail the command
            return false;
        }
    }

    /**
     * Copy stub files to package directory with variable replacement.
     *
     * @param string                $stubPath    Source stub directory
     * @param string                $packagePath Destination package directory
     * @param array<string, string> $variables   Variables for template replacement
     * @param Filesystem            $filesystem  Filesystem service
     * @param array<string, string> $namingRules File naming rules for special files
     */
    private function copyStubFiles(string $stubPath, string $packagePath, array $variables, Filesystem $filesystem, array $namingRules = []): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($stubPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($stubPath) + 1);
            $destinationPath = $packagePath . '/' . $relativePath;

            if ($item->isDir()) {
                $filesystem->makeDirectory($destinationPath, 0755, true);
            } else {
                // Remove .stub extension if present
                if (str_ends_with($destinationPath, '.stub')) {
                    $destinationPath = substr($destinationPath, 0, -5);
                }

                // Apply naming rules from package type
                foreach ($namingRules as $pattern => $replacement) {
                    if (str_ends_with($destinationPath, $pattern)) {
                        // Replace pattern with actual values from variables
                        $replacedPattern = str_replace(array_keys($variables), array_values($variables), $replacement);
                        $destinationPath = str_replace($pattern, $replacedPattern, $destinationPath);

                        break;
                    }
                }

                // Read stub content
                $content = file_get_contents($item->getPathname());
                if ($content === false) {
                    continue;
                }

                // For JSON files, escape backslashes in namespace values
                $isJsonFile = str_ends_with($destinationPath, '.json');
                $variablesToUse = $variables;

                if ($isJsonFile && isset($variables[PackageTypeInterface::VAR_NAMESPACE])) {
                    // Escape single backslashes to double backslashes for JSON
                    // But don't double-escape already escaped backslashes
                    $namespace = $variables[PackageTypeInterface::VAR_NAMESPACE];
                    $variablesToUse[PackageTypeInterface::VAR_NAMESPACE] = str_replace('\\', '\\\\', $namespace);
                }

                // Replace variables
                $content = str_replace(array_keys($variablesToUse), array_values($variablesToUse), $content);

                // Write processed content
                $filesystem->write($destinationPath, $content);
            }
        }
    }
}
