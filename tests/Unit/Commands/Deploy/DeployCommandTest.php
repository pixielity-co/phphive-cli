<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Deploy;

use PhpHive\Cli\Console\Commands\Deploy\DeployCommand;
use PhpHive\Cli\Tests\TestCase;

/**
 * Deploy Command Test.
 *
 * Tests for the DeployCommand that runs the full deployment pipeline.
 * Verifies command configuration, skip-tests option, output format options,
 * workspace filtering, and execution flow.
 */
final class DeployCommandTest extends TestCase
{
    /**
     * The command instance under test.
     */
    private DeployCommand $command;

    /**
     * Set up the test environment before each test.
     *
     * Creates a fresh command instance for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new DeployCommand();
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('deploy:run', $this->command->getName());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command is accessible via shorthand alias.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('deploy', $aliases);
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run full deployment pipeline', $this->command->getDescription());
    }

    /**
     * Test that command has skip-tests option.
     *
     * Verifies the --skip-tests flag is available.
     */
    public function test_command_has_skip_tests_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('skip-tests'));

        $option = $definition->getOption('skip-tests');
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
     * Test that skip-tests option has correct description.
     *
     * Verifies the --skip-tests option description warns about risks.
     */
    public function test_skip_tests_option_has_correct_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('skip-tests');

        $description = $option->getDescription();
        $this->assertStringContainsString('test', $description);
        $this->assertStringContainsString('not recommended', $description);
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
        $this->assertTrue($definition->hasOption('skip-tests'));
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasOption('table'));
        $this->assertTrue($definition->hasOption('summary'));

        // Check inherited options from BaseCommand
        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
        $this->assertTrue($definition->hasOption('no-interaction'));
    }
}
