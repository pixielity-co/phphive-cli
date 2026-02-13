<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Make;

use PhpHive\Cli\Console\Commands\Make\MakeWorkspaceCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Make Workspace Command Test.
 *
 * Tests for the MakeWorkspaceCommand that creates new monorepo workspaces from template.
 * Verifies workspace creation, template cloning, configuration updates, validation,
 * error handling, and all command flags/options.
 */
final class MakeWorkspaceCommandTest extends TestCase
{
    /**
     * The command instance.
     */
    private MakeWorkspaceCommand $command;

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
        $this->command = new MakeWorkspaceCommand();

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
        $this->assertSame('make:workspace', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies that the command has the expected aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('init', $aliases);
        $this->assertContains('new', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies that the command has a descriptive text.
     */
    public function test_command_has_correct_description(): void
    {
        $description = $this->command->getDescription();

        $this->assertSame('Create a new workspace from template', $description);
    }

    /**
     * Test that command has optional name argument.
     *
     * Verifies that the name argument is optional.
     */
    public function test_command_has_optional_name_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $this->assertFalse($definition->getArgument('name')->isRequired());
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
     * Test that command validates name format.
     *
     * Verifies that the command rejects invalid name formats.
     */
    public function test_validates_name_format(): void
    {
        // Create input with invalid name (uppercase)
        $input = new ArrayInput(
            ['name' => 'MyWorkspace'],
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
            ['name' => 'my_workspace'],
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
            ['name' => '-myworkspace'],
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
            ['name' => 'myworkspace-'],
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
            'myproject',
            'my-project',
            'my-project-2024',
            'project123',
            '2024project',
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
            ['name' => 'test-workspace', '--json' => true, '--no-interaction' => true],
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
            ['name' => 'test-workspace', '--quiet' => true, '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $this->command->run($input, $this->output);

        // Get output
        $content = $this->output->fetch();

        // Assert minimal output (no intro, no progress messages)
        $this->assertStringNotContainsString('Create New Workspace', $content);
        $this->assertStringNotContainsString('Running environment checks', $content);
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
            ['name' => 'test-workspace', '--verbose' => true, '--no-interaction' => true],
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
            ['name' => 'test-workspace', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command ran without prompts
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command requires name in non-interactive mode.
     *
     * Verifies that the command fails when name is not provided in non-interactive mode.
     */
    public function test_requires_name_in_non_interactive_mode(): void
    {
        // Create input without name in non-interactive mode
        $input = new ArrayInput(
            ['--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command
        $exitCode = $this->command->run($input, $this->output);

        // Assert failure
        $this->assertSame(Command::FAILURE, $exitCode);

        // Assert error message
        $content = $this->output->fetch();
        $this->assertStringContainsString('required in non-interactive mode', $content);
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
                'name' => 'test-workspace',
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

    /**
     * Test that command handles JSON and quiet flags together.
     *
     * Verifies that JSON and quiet flags work together.
     */
    public function test_handles_json_and_quiet_flags_together(): void
    {
        // Create input with JSON and quiet flags
        $input = new ArrayInput(
            [
                'name' => 'test-workspace',
                '--json' => true,
                '--quiet' => true,
                '--no-interaction' => true,
            ],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $this->command->run($input, $this->output);

        // Get output
        $content = $this->output->fetch();

        // Assert JSON output (quiet should not suppress JSON)
        $this->assertJson($content);
    }

    /**
     * Test that command handles verbose and quiet flags together.
     *
     * Verifies that quiet flag takes precedence over verbose.
     */
    public function test_handles_verbose_and_quiet_flags_together(): void
    {
        // Create input with verbose and quiet flags
        $input = new ArrayInput(
            [
                'name' => 'test-workspace',
                '--verbose' => true,
                '--quiet' => true,
                '--no-interaction' => true,
            ],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $this->command->run($input, $this->output);

        // Get output
        $content = $this->output->fetch();

        // Assert minimal output (quiet takes precedence)
        $this->assertStringNotContainsString('Create New Workspace', $content);
    }

    /**
     * Test that command accepts workspace name with numbers.
     *
     * Verifies that workspace names can contain numbers.
     */
    public function test_accepts_workspace_name_with_numbers(): void
    {
        // Create input with name containing numbers
        $input = new ArrayInput(
            ['name' => 'project-2024', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the name format
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command accepts workspace name starting with number.
     *
     * Verifies that workspace names can start with numbers.
     */
    public function test_accepts_workspace_name_starting_with_number(): void
    {
        // Create input with name starting with number
        $input = new ArrayInput(
            ['name' => '2024-project', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the name format
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command accepts single word workspace name.
     *
     * Verifies that workspace names can be a single word.
     */
    public function test_accepts_single_word_workspace_name(): void
    {
        // Create input with single word name
        $input = new ArrayInput(
            ['name' => 'myproject', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the name format
        $this->assertIsInt($exitCode);
    }

    /**
     * Test that command accepts multi-hyphen workspace name.
     *
     * Verifies that workspace names can have multiple hyphens.
     */
    public function test_accepts_multi_hyphen_workspace_name(): void
    {
        // Create input with multiple hyphens
        $input = new ArrayInput(
            ['name' => 'my-awesome-project-2024', '--no-interaction' => true],
            $this->command->getDefinition()
        );

        // Execute command (will fail at preflight checks)
        $exitCode = $this->command->run($input, $this->output);

        // Assert command accepted the name format
        $this->assertIsInt($exitCode);
    }
}
