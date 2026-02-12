<?php

declare(strict_types=1);

namespace PhpHive\Cli\Console\Commands\Make;

use Exception;
use InvalidArgumentException;

use function is_dir;

use Override;
use PhpHive\Cli\Console\Commands\BaseCommand;
use PhpHive\Cli\Factories\PackageTypeFactory;
use PhpHive\Cli\Support\Filesystem;
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
     * Set help text for the command.
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
            ->setHelp(
                <<<'HELP'
                The <info>create:package</info> command scaffolds a new PHP package.

                <comment>Examples:</comment>
                  <info>hive create:package logger</info>
                  <info>hive create:package http-client</info>

                This creates a complete package structure with all necessary files.
                HELP
            );
    }

    /**
     * Execute the create package command.
     *
     * This method orchestrates the entire package scaffolding process:
     * 1. Extracts and validates the package name
     * 2. Prompts for package type if not provided
     * 3. Checks if the package already exists
     * 4. Creates the complete directory structure using stubs
     * 5. Processes stub templates with variables
     * 6. Displays next steps to the user
     *
     * @param  InputInterface  $input  Command input (arguments and options)
     * @param  OutputInterface $output Command output (for displaying messages)
     * @return int             Exit code (0 for success, 1 for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Extract the package name from command arguments
        $name = $input->getArgument('name');

        // Display intro banner with package name
        $this->intro("Creating package: {$name}");

        // Determine package type (prompt if not provided)
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
        $this->comment('Selected: ' . $packageType->getDisplayName());
        $this->line('');

        // Determine the full path for the new package
        $root = $this->getMonorepoRoot();
        $packagePath = "{$root}/packages/{$name}";

        // Check if package already exists to prevent overwriting
        if (is_dir($packagePath)) {
            $this->error("Package '{$name}' already exists");

            return Command::FAILURE;
        }

        // Get stub path for the selected package type
        $stubsBasePath = dirname(__DIR__, 4) . '/stubs';
        $stubPath = $packageType->getStubPath($stubsBasePath);

        if (! is_dir($stubPath)) {
            $this->error("Stub directory not found for package type '{$type}' at: {$stubPath}");

            return Command::FAILURE;
        }

        // Initialize filesystem helper
        $filesystem = $this->filesystem();

        // Create package directory
        $this->info('Creating package directory...');
        $filesystem->makeDirectory($packagePath, 0755, true);

        // Copy stub files and process templates
        $this->info('Processing stub templates...');

        // Prepare stub variables using package type
        $description = $input->getOption('description') ?? "A {$type} package";
        $variables = $packageType->prepareVariables($name, $description);

        // Copy and process all stub files with package type naming rules
        $this->copyStubFiles($stubPath, $packagePath, $variables, $filesystem, $packageType->getFileNamingRules());

        // Run post-creation tasks (e.g., composer install)
        $this->line('');
        $this->info('Installing dependencies...');

        try {
            $packageType->postCreate($packagePath);
            $this->comment('✓ Dependencies installed successfully');
        } catch (Exception $exception) {
            $this->warning('Composer install failed. You may need to run it manually.');
            $this->line($exception->getMessage());
        }

        // Display success message and next steps
        $this->line('');
        $this->outro("✓ Package '{$name}' created successfully!");
        $this->line('');
        $this->comment('Next steps:');
        $this->line("  1. cd packages/{$name}");
        $this->line('  2. Start coding in src/');

        return Command::SUCCESS;
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

                if ($isJsonFile && isset($variables['{{NAMESPACE}}'])) {
                    // Escape single backslashes to double backslashes for JSON
                    // But don't double-escape already escaped backslashes
                    $namespace = $variables['{{NAMESPACE}}'];
                    $variablesToUse['{{NAMESPACE}}'] = str_replace('\\', '\\\\', $namespace);
                }

                // Replace variables
                $content = str_replace(array_keys($variablesToUse), array_values($variablesToUse), $content);

                // Write processed content
                $filesystem->write($destinationPath, $content);
            }
        }
    }
}
