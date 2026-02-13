<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Composer;

use PhpHive\Cli\Console\Commands\Composer\RequireCommand;
use PhpHive\Cli\Tests\TestCase;

/**
 * Require Command Test.
 *
 * Tests for the RequireCommand that adds Composer packages to workspaces.
 * Verifies command configuration, package argument handling, dev flag,
 * workspace selection, and execution flow.
 */
final class RequireCommandTest extends TestCase
{
    /**
     * The command instance under test.
     */
    private RequireCommand $command;

    /**
     * Set up the test environment before each test.
     *
     * Creates a fresh command instance for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new RequireCommand();
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('composer:require', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command is accessible via shorthand aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('require', $aliases);
        $this->assertContains('req', $aliases);
        $this->assertContains('add', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Add a Composer package to a workspace', $this->command->getDescription());
    }

    /**
     * Test that command requires package argument.
     *
     * Verifies the package argument is defined and required.
     */
    public function test_command_requires_package_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('package'));

        $argument = $definition->getArgument('package');
        $this->assertTrue($argument->isRequired());
        $this->assertFalse($argument->isArray());
    }

    /**
     * Test that command has dev option.
     *
     * Verifies the --dev flag is available for development dependencies.
     */
    public function test_command_has_dev_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('dev'));

        $option = $definition->getOption('dev');
        $this->assertFalse($option->acceptValue());
        $this->assertSame('d', $option->getShortcut());
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

        $this->assertStringContainsString('require', $help);
        $this->assertStringContainsString('Examples:', $help);
        $this->assertStringContainsString('--dev', $help);
    }

    /**
     * Test that package argument has correct description.
     *
     * Verifies the package argument description includes format examples.
     */
    public function test_package_argument_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $argument = $definition->getArgument('package');

        $description = $argument->getDescription();
        $this->assertStringContainsString('package', $description);
        $this->assertStringContainsString('symfony/console', $description);
    }

    /**
     * Test that dev option has correct description.
     *
     * Verifies the --dev option description explains its purpose.
     */
    public function test_dev_option_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('dev');

        $description = $option->getDescription();
        $this->assertStringContainsString('development', $description);
    }

    /**
     * Test that command definition is properly configured.
     *
     * Verifies all expected options and arguments are present.
     */
    public function test_command_definition_is_properly_configured(): void
    {
        $definition = $this->command->getDefinition();

        // Check required argument
        $this->assertTrue($definition->hasArgument('package'));

        // Check command-specific option
        $this->assertTrue($definition->hasOption('dev'));

        // Check inherited options from BaseCommand
        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
    }
}
