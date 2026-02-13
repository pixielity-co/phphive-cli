<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Composer;

use PhpHive\Cli\Console\Commands\Composer\ComposerCommand;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;

/**
 * Composer Command Test.
 *
 * Tests for the ComposerCommand that runs arbitrary Composer commands
 * in workspace contexts. Verifies command configuration, argument handling,
 * workspace selection, and execution flow.
 */
final class ComposerCommandTest extends TestCase
{
    /**
     * The command instance under test.
     */
    private ComposerCommand $command;

    /**
     * Set up the test environment before each test.
     *
     * Creates a fresh command instance for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ComposerCommand();
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('composer:run', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command is accessible via shorthand aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('composer', $aliases);
        $this->assertContains('comp', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run Composer command in a workspace', $this->command->getDescription());
    }

    /**
     * Test that command requires command argument.
     *
     * Verifies the command argument is defined and required.
     */
    public function test_command_requires_command_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('command'));

        $argument = $definition->getArgument('command');
        $this->assertTrue($argument->isRequired());
        $this->assertTrue($argument->isArray());
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

        $this->assertStringContainsString('composer', $help);
        $this->assertStringContainsString('Examples:', $help);
    }

    /**
     * Test that command accepts array of arguments.
     *
     * Verifies the command can accept multiple arguments for passthrough.
     */
    public function test_command_accepts_array_of_arguments(): void
    {
        $definition = $this->command->getDefinition();
        $argument = $definition->getArgument('command');

        $this->assertTrue($argument->isArray());
        $this->assertSame('The Composer command to run', $argument->getDescription());
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
        $this->assertTrue($definition->hasArgument('command'));

        // Check inherited options from BaseCommand
        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
    }
}
