<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Deploy;

use PhpHive\Cli\Console\Commands\Deploy\PublishCommand;
use PhpHive\Cli\Tests\TestCase;

/**
 * Publish Command Test.
 *
 * Tests for the PublishCommand that publishes packages to registries.
 * Verifies command configuration, tag option, dry-run option, output format options,
 * workspace selection, and execution flow.
 */
final class PublishCommandTest extends TestCase
{
    /**
     * The command instance under test.
     */
    private PublishCommand $command;

    /**
     * Set up the test environment before each test.
     *
     * Creates a fresh command instance for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new PublishCommand();
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('deploy:publish', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command is accessible via shorthand alias.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('publish', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Publish packages to registry', $this->command->getDescription());
    }

    /**
     * Test that command has tag option.
     *
     * Verifies the --tag option is available for version tagging.
     */
    public function test_command_has_tag_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('tag'));

        $option = $definition->getOption('tag');
        $this->assertTrue($option->acceptValue());
        $this->assertSame('t', $option->getShortcut());
    }

    /**
     * Test that command has dry-run option.
     *
     * Verifies the --dry-run flag is available for testing.
     */
    public function test_command_has_dry_run_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));

        $option = $definition->getOption('dry-run');
        $this->assertFalse($option->acceptValue());
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
     * Verifies the --summary flag is available for concise output.
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
     * Test that tag option has correct description.
     *
     * Verifies the --tag option description explains version tags.
     */
    public function test_tag_option_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('tag');

        $description = $option->getDescription();
        $this->assertStringContainsString('tag', $description);
        $this->assertStringContainsString('latest', $description);
    }

    /**
     * Test that dry-run option has correct description.
     *
     * Verifies the --dry-run option description explains test mode.
     */
    public function test_dry_run_option_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('dry-run');

        $description = $option->getDescription();
        $this->assertStringContainsString('Simulate', $description);
        $this->assertStringContainsString('test', $description);
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
        $this->assertStringContainsString('summary', $description);
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
        $this->assertTrue($definition->hasOption('tag'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasOption('summary'));

        // Check inherited options from BaseCommand
        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
        $this->assertTrue($definition->hasOption('no-interaction'));
    }
}
