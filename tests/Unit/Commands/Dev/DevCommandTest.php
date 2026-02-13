<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Dev;

use PhpHive\Cli\Console\Commands\Dev\DevCommand;
use PhpHive\Cli\Tests\TestCase;

/**
 * Dev Command Test.
 *
 * Tests for the DevCommand that starts development servers.
 * Verifies command configuration, port option, workspace selection,
 * and execution flow.
 */
final class DevCommandTest extends TestCase
{
    /**
     * The command instance under test.
     */
    private DevCommand $command;

    /**
     * Set up the test environment before each test.
     *
     * Creates a fresh command instance for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new DevCommand();
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('dev:start', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command is accessible via shorthand alias.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('dev', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Start development server', $this->command->getDescription());
    }

    /**
     * Test that command has port option.
     *
     * Verifies the --port option is available for custom port configuration.
     */
    public function test_command_has_port_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('port'));

        $option = $definition->getOption('port');
        $this->assertTrue($option->acceptValue());
        $this->assertSame('p', $option->getShortcut());
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
     * Test that port option has correct description.
     *
     * Verifies the --port option description explains its purpose.
     */
    public function test_port_option_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('port');

        $description = $option->getDescription();
        $this->assertStringContainsString('port', $description);
    }

    /**
     * Test that port option accepts value.
     *
     * Verifies the --port option can accept a port number.
     */
    public function test_port_option_accepts_value(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('port');

        $this->assertTrue($option->acceptValue());
        $this->assertTrue($option->isValueRequired());
    }

    /**
     * Test that command definition is properly configured.
     *
     * Verifies all expected options are present.
     */
    public function test_command_definition_is_properly_configured(): void
    {
        $definition = $this->command->getDefinition();

        // Check command-specific option
        $this->assertTrue($definition->hasOption('port'));

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
