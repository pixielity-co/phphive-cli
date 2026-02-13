<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Framework;

use PhpHive\Cli\Console\Commands\Framework\ArtisanCommand;
use PhpHive\Cli\Tests\TestCase;

/**
 * Artisan Command Test.
 *
 * Tests for the ArtisanCommand that runs Laravel Artisan commands in workspaces.
 * Verifies command configuration, argument handling, workspace selection,
 * and execution flow.
 */
final class ArtisanCommandTest extends TestCase
{
    /**
     * The command instance under test.
     */
    private ArtisanCommand $command;

    /**
     * Set up the test environment before each test.
     *
     * Creates a fresh command instance for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ArtisanCommand();
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('framework:artisan', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command is accessible via shorthand aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('artisan', $aliases);
        $this->assertContains('art', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run Laravel Artisan command in a workspace', $this->command->getDescription());
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
     * Test that command inherits force option.
     *
     * Verifies the --force option is available from BaseCommand.
     */
    public function test_command_has_force_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('force'));
    }

    /**
     * Test that command inherits no-cache option.
     *
     * Verifies the --no-cache option is available from BaseCommand.
     */
    public function test_command_has_no_cache_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('no-cache'));
    }

    /**
     * Test that command inherits no-interaction option.
     *
     * Verifies the --no-interaction option is available from BaseCommand.
     */
    public function test_command_has_no_interaction_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('no-interaction'));
    }

    /**
     * Test that command has help text.
     *
     * Verifies comprehensive help text is provided.
     */
    public function test_command_has_help_text(): void
    {
        $help = $this->command->getHelp();

        $this->assertStringContainsString('artisan', $help);
        $this->assertStringContainsString('Examples:', $help);
        $this->assertStringContainsString('migrate', $help);
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
    }

    /**
     * Test that command argument has correct description.
     *
     * Verifies the command argument description includes examples.
     */
    public function test_command_argument_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $argument = $definition->getArgument('command');

        $description = $argument->getDescription();
        $this->assertStringContainsString('Artisan', $description);
        $this->assertStringContainsString('migrate', $description);
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
        $this->assertTrue($definition->hasOption('no-interaction'));
    }

    /**
     * Test that workspace option has correct shortcut.
     *
     * Verifies the --workspace option has -w shortcut.
     */
    public function test_workspace_option_has_correct_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('workspace');

        $this->assertSame('w', $option->getShortcut());
    }

    /**
     * Test that force option has correct shortcut.
     *
     * Verifies the --force option has -f shortcut.
     */
    public function test_force_option_has_correct_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('force');

        $this->assertSame('f', $option->getShortcut());
    }
}
