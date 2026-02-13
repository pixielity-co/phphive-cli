<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Composer;

use PhpHive\Cli\Console\Commands\Composer\UpdateCommand;
use PhpHive\Cli\Tests\TestCase;

/**
 * Update Command Test.
 *
 * Tests for the UpdateCommand that updates Composer dependencies in workspaces.
 * Verifies command configuration, optional package argument, output format options,
 * workspace selection, and execution flow.
 */
final class UpdateCommandTest extends TestCase
{
    /**
     * The command instance under test.
     */
    private UpdateCommand $command;

    /**
     * Set up the test environment before each test.
     *
     * Creates a fresh command instance for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new UpdateCommand();
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('composer:update', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command is accessible via shorthand aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('update', $aliases);
        $this->assertContains('up', $aliases);
        $this->assertContains('upgrade', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Update Composer dependencies in a workspace', $this->command->getDescription());
    }

    /**
     * Test that command has optional package argument.
     *
     * Verifies the package argument is optional for targeted updates.
     */
    public function test_command_has_optional_package_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('package'));

        $argument = $definition->getArgument('package');
        $this->assertFalse($argument->isRequired());
        $this->assertFalse($argument->isArray());
    }

    /**
     * Test that command has json option.
     *
     * Verifies the --json flag is available for machine-readable output.
     */
    public function test_command_has_json_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('json'));

        $option = $definition->getOption('json');
        $this->assertFalse($option->acceptValue());
        $this->assertSame('j', $option->getShortcut());
    }

    /**
     * Test that command has summary option.
     *
     * Verifies the --summary flag is available for table output.
     */
    public function test_command_has_summary_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('summary'));

        $option = $definition->getOption('summary');
        $this->assertFalse($option->acceptValue());
        $this->assertSame('s', $option->getShortcut());
    }

    /**
     * Test that command inherits workspace option.
     *
     * Verifies the --workspace option is available from BaseCommand.
     */
    public function test_command_has_workspace_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('workspace'));
    }

    /**
     * Test that command has help text.
     *
     * Verifies comprehensive help text is provided.
     */
    public function test_command_has_help_text(): void
    {
        $help = $this->command->getHelp();

        $this->assertStringContainsString('update', $help);
        $this->assertStringContainsString('Examples:', $help);
    }

    /**
     * Test that package argument has correct description.
     *
     * Verifies the package argument description explains optional usage.
     */
    public function test_package_argument_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $argument = $definition->getArgument('package');

        $description = $argument->getDescription();
        $this->assertStringContainsString('package', $description);
        $this->assertStringContainsString('optional', $description);
    }

    /**
     * Test that json option has correct description.
     *
     * Verifies the --json option description explains its purpose.
     */
    public function test_json_option_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('json');

        $description = $option->getDescription();
        $this->assertStringContainsString('JSON', $description);
    }

    /**
     * Test that summary option has correct description.
     *
     * Verifies the --summary option description explains its purpose.
     */
    public function test_summary_option_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('summary');

        $description = $option->getDescription();
        $this->assertStringContainsString('table', $description);
    }

    /**
     * Test that command definition is properly configured.
     *
     * Verifies all expected options and arguments are present.
     */
    public function test_command_definition_is_properly_configured(): void
    {
        $definition = $this->command->getDefinition();

        // Check optional argument
        $this->assertTrue($definition->hasArgument('package'));

        // Check command-specific options
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasOption('summary'));

        // Check inherited options from BaseCommand
        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
    }
}
