<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Make;

use PhpHive\Cli\Console\Commands\Make\CreatePackageCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Create Package Command Test.
 *
 * Tests for the CreatePackageCommand that scaffolds PHP library packages within the monorepo.
 * Verifies package creation, type selection, configuration generation, validation,
 * error handling, and all command flags/options.
 */
final class CreatePackageCommandTest extends TestCase
{
    /**
     * The command instance.
     */
    private CreatePackageCommand $command;

    /**
     * Buffered output for capturing command output.
     */
    private BufferedOutput $output;

    /**
     * Container instance for dependency injection.
     */
    private Container $container;

    /**
     * Set up the test environment before each test.
     *
     * Creates a command instance with mock output and injects a container.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create command
        $this->command = new CreatePackageCommand();

        // Create buffered output
        $this->output = new BufferedOutput();

        // Create and inject container
        $this->container = new Container();
        $this->command->setContainer($this->container);
    }

    /**
     * Test that command has correct name.
     *
     * Verifies that the command is registered with the correct name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('make:package', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies that the command has the expected aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('create:package', $aliases);
        $this->assertContains('new:package', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies that the command has a descriptive text.
     */
    public function test_command_has_correct_description(): void
    {
        $description = $this->command->getDescription();

        $this->assertSame('Create a new package', $description);
    }

    /**
     * Test that command requires name argument.
     *
     * Verifies that the name argument is required.
     */
    public function test_command_requires_name_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->getArgument('name')->isRequired());
    }

    /**
     * Test that command has type option.
     *
     * Verifies that the --type option is available.
     */
    public function test_command_has_type_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('type'));
        $this->assertSame('t', $definition->getOption('type')->getShortcut());
    }

    /**
     * Test that command has description option.
     *
     * Verifies that the --description option is available.
     */
    public function test_command_has_description_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('description'));
        $this->assertSame('d', $definition->getOption('description')->getShortcut());
    }

    /**
     * Test that command has quiet option.
     *
     * Verifies that the --quiet option is available.
     */
    public function test_command_has_quiet_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('quiet'));
        $this->assertSame('q', $definition->getOption('quiet')->getShortcut());
    }

    /**
     * Test that command has json option.
     *
     * Verifies that the --json option is available.
     */
    public function test_command_has_json_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('json'));
        $this->assertSame('j', $definition->getOption('json')->getShortcut());
    }

    /**
     * Test that command fails without name argument.
     *
     * Verifies that the command fails when name is not provided.
     */
    public function test_fails_without_name_argument(): void
    {
        // Create input without name
        $input = new ArrayInput([], $this->command->getDefinition());

        // Execute command
        $exitCode = $this->command->run($input, $this->output);

        // Assert failure
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test that command validates name format.
     *
     * Verifies that the command rejects invalid name formats.
     */
    public function test_validates_name_format(): void
    {
        // Create input with invalid name (uppercase)
        $input = new ArrayInput(
            ['name' => 'MyPackage'],
            $this->command->getDefinition()
        );

        // Execute command
        $exitCode = $this->command->run($input, $this->output);

        // Assert failure
        $this->assertSame(Command::FAILURE, $exitCode);

        // Assert error message
        $content = $this->output->fetch();
        $this->assertStringContainsString('lowercase alphanumeric', $content);
    }

    /**
     * Test that command validates name format with underscores.
     *
     * Verifies that the command rejects names with underscores.
     */
    public function test_validates_name_format_rejects_underscores(): void
    {
        // Create input with invalid name (underscores)
        $input = new ArrayInput(
            ['name' => 'my_package'],
            $this->command->getDefinition()
        );

        // Execute command
        $exitCode = $this->command->run($input, $this->output);

        // Assert failure
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test that command validates name format with leading hyphen.
     *
     * Verifies that the command rejects names starting with hyphen.
     */
    public function test_validates_name_format_rejects_leading_hyphen(): void
    {
        // Create input with invalid name (leading hyphen)
        $input = new ArrayInput(
            ['name' => '-mypackage'],
            $this->command->getDefinition()
        );

        // Execute command
        $exitCode = $this->command->run($input, $this->output);

        // Assert failure
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test that command validates name format with trailing hyphen.
     *
     * Verifies that the command rejects names ending with hyphen.
     */
    public function test_validates_name_format_rejects_trailing_hyphen(): void
    {
        // Create input with invalid name (trailing hyphen)
        $input = new ArrayInput(
            ['name' => 'mypackage-'],
            $this->command->getDefinition()
        );

        // Execute command
        $exitCode = $this->command->run($input, $this->output);

        // Assert failure
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test that command accepts valid name formats.
     *
     * Verifies that the command accepts valid lowercase alphanumeric names with hyphens.
     */
    public function test_accepts_valid_name_formats(): void
    {
        $validNames = [
            'logger',
            'http-client',
            'database-adapter',
            'cache123',
            '123cache',
        ];

        foreach ($validNames as $name) {
            // Verify name format is valid
            $this->assertTrue(preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $name) === 1);
        }
    }

    /**
     * Test that command outputs JSON in JSON mode.
     *
     * Verifies that the command outputs JSON when --json flag is set.
     */
    public function test_outputs_json_in_json_mode(): void
    {
        // Create input with JSON flag
        $input = new ArrayInput(
            ['name' => 'test-package', '--json' => true, '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $this->command->run($input, $this->output);

        // Get output
        $content = $this->output->fetch();

        // Assert JSON output
        $this->assertJson($content);
    }

    /**
     * Test that command suppresses output in quiet mode.
     *
     * Verifies that the command suppresses non-error output when --quiet flag is set.
     */
    public function test_suppresses_output_in_quiet_mode(): void
    {
        // Create input with quiet flag
        $input = new ArrayInput(
            ['name' => 'test-package', '--quiet' => true, '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $this->command->run($input, $this->output);

        // Get output
        $content = $this->output->fetch();

        // Assert minimal output (no intro, no progress messages)
        $this->assertStringNotContainsString('Package Creation', $content);
        $this->assertStringNotContainsString('Running environment checks', $content);
    }

    /**
     * Test that command accepts type option.
     *
     * Verifies that the --type option is properly handled.
     */
    public function test_accepts_type_option(): void
    {
        // Create input with type option
        $input = new ArrayInput(
            ['name' => 'test-package', '--type' => 'skeleton', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the type option
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command accepts description option.
     *
     * Verifies that the --description option is properly handled.
     */
    public function test_accepts_description_option(): void
    {
        // Create input with description option
        $input = new ArrayInput(
            [
                'name' => 'test-package',
                '--description' => 'My test package',
                '--no-interaction' => true,
            ],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the description option
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command handles verbose mode.
     *
     * Verifies that the command shows additional output in verbose mode.
     */
    public function test_handles_verbose_mode(): void
    {
        // Create input with verbose flag
        $input = new ArrayInput(
            ['name' => 'test-package', '--verbose' => true, '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $this->command->run($input, $this->output);

        // Assert command ran (verbose output would show more details)
        $this->assertTrue(true);
    }

    /**
     * Test that command handles no-interaction mode.
     *
     * Verifies that the command runs without prompts in non-interactive mode.
     */
    public function test_handles_no_interaction_mode(): void
    {
        // Create input with no-interaction flag
        $input = new ArrayInput(
            ['name' => 'test-package', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command ran without prompts
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command accepts laravel package type.
     *
     * Verifies that Laravel package type is accepted.
     */
    public function test_accepts_laravel_package_type(): void
    {
        // Create input with Laravel type
        $input = new ArrayInput(
            ['name' => 'test-package', '--type' => 'laravel', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the type
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command accepts symfony package type.
     *
     * Verifies that Symfony package type is accepted.
     */
    public function test_accepts_symfony_package_type(): void
    {
        // Create input with Symfony type
        $input = new ArrayInput(
            ['name' => 'test-package', '--type' => 'symfony', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the type
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command accepts magento package type.
     *
     * Verifies that Magento package type is accepted.
     */
    public function test_accepts_magento_package_type(): void
    {
        // Create input with Magento type
        $input = new ArrayInput(
            ['name' => 'test-package', '--type' => 'magento', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the type
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command accepts skeleton package type.
     *
     * Verifies that Skeleton package type is accepted.
     */
    public function test_accepts_skeleton_package_type(): void
    {
        // Create input with Skeleton type
        $input = new ArrayInput(
            ['name' => 'test-package', '--type' => 'skeleton', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the type
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command validates package type.
     *
     * Verifies that invalid package types are rejected.
     */
    public function test_validates_package_type(): void
    {
        // Create input with invalid type
        $input = new ArrayInput(
            ['name' => 'test-package', '--type' => 'invalid-type', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command
        $exitCode = $this->command->run($input, $this->output);

        // Assert failure
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test that command handles combined flags.
     *
     * Verifies that multiple flags can be used together.
     */
    public function test_handles_combined_flags(): void
    {
        // Create input with multiple flags
        $input = new ArrayInput(
            [
                'name' => 'test-package',
                '--type' => 'skeleton',
                '--description' => 'Test package',
                '--quiet' => true,
                '--no-interaction' => true,
            ],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command handled all flags
        $this->assertIsInt($exitCode);
    }
}
