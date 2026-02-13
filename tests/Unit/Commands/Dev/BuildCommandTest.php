<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Dev;

use PhpHive\Cli\Console\Commands\Dev\BuildCommand;
use PhpHive\Cli\Tests\TestCase;

/**
 * Build Command Test.
 *
 * Tests for the BuildCommand that builds applications and packages for production.
 * Verifies command configuration, output format options, workspace filtering,
 * force and cache options, and execution flow.
 */
final class BuildCommandTest extends TestCase
{
    /**
     * The command instance under test.
     */
    private BuildCommand $command;

    /**
     * Set up the test environment before each test.
     *
     * Creates a fresh command instance for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new BuildCommand();
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('dev:build', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command is accessible via shorthand alias.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('build', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Build for production', $this->command->getDescription());
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
     * Test that command has table option.
     *
     * Verifies the --table flag is available for structured output.
     */
    public function test_command_has_table_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('table'));

        $option = $definition->getOption('table');
        $this->assertFalse($option->acceptValue());
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
        $this->assertStringContainsString('CI/CD', $description);
    }

    /**
     * Test that table option has correct description.
     *
     * Verifies the --table option description explains its purpose.
     */
    public function test_table_option_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('table');

        $description = $option->getDescription();
        $this->assertStringContainsString('table', $description);
    }

    /**
     * Test that command definition is properly configured.
     *
     * Verifies all expected options are present.
     */
    public function test_command_definition_is_properly_configured(): void
    {
        $definition = $this->command->getDefinition();

        // Check command-specific options
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasOption('table'));

        // Check inherited options from BaseCommand
        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
        $this->assertTrue($definition->hasOption('no-interaction'));
    }

    /**
     * Test that force option can bypass cache.
     *
     * Verifies the --force option is properly inherited.
     */
    public function test_force_option_can_bypass_cache(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('force');

        $this->assertFalse($option->acceptValue());
        $this->assertSame('f', $option->getShortcut());
    }

    /**
     * Test that no-cache option can disable cache.
     *
     * Verifies the --no-cache option is properly inherited.
     */
    public function test_no_cache_option_can_disable_cache(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('no-cache');

        $this->assertFalse($option->acceptValue());
    }
}
