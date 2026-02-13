<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Quality;

use PhpHive\Cli\Console\Commands\Quality\RefactorCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Refactor Command Test.
 *
 * Tests for the RefactorCommand that runs Rector for automated refactoring.
 * Verifies all command options, flags, dry-run mode, and cache management.
 */
final class RefactorCommandTest extends TestCase
{
    /**
     * The refactor command instance.
     */
    private RefactorCommand $command;

    /**
     * Mock input for testing.
     */
    private ArrayInput $input;

    /**
     * Buffered output for capturing command output.
     */
    private BufferedOutput $output;

    /**
     * Set up the test environment before each test.
     *
     * Creates a refactor command instance with mock input/output and
     * injects a container instance.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create refactor command
        $this->command = new RefactorCommand();

        // Create mock input and output
        $this->input = new ArrayInput([]);
        $this->output = new BufferedOutput();

        // Inject container
        $container = new Container();
        $this->command->setContainer($container);
    }

    /**
     * Test that command has correct name.
     *
     * Verifies the command is registered with the expected name.
     */
    public function test_command_has_correct_name(): void
    {
        $this->assertSame('quality:refactor', $this->command->getName());
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run Rector for automated refactoring', $this->command->getDescription());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command can be invoked using aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('refactor', $aliases);
        $this->assertContains('rector', $aliases);
    }

    /**
     * Test that command has dry-run option.
     *
     * Verifies the --dry-run option is defined.
     */
    public function test_command_has_dry_run_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));
    }

    /**
     * Test that command has clear-cache option.
     *
     * Verifies the --clear-cache option is defined.
     */
    public function test_command_has_clear_cache_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('clear-cache'));
    }

    /**
     * Test that command inherits workspace option from BaseCommand.
     *
     * Verifies the --workspace/-w option is available.
     */
    public function test_command_has_workspace_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('workspace'));

        $option = $definition->getOption('workspace');
        $this->assertSame('w', $option->getShortcut());
    }

    /**
     * Test that command inherits force option from BaseCommand.
     *
     * Verifies the --force/-f option is available.
     */
    public function test_command_has_force_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('force'));

        $option = $definition->getOption('force');
        $this->assertSame('f', $option->getShortcut());
    }

    /**
     * Test that command inherits no-cache option from BaseCommand.
     *
     * Verifies the --no-cache option is available.
     */
    public function test_command_has_no_cache_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('no-cache'));
    }

    /**
     * Test that dry-run option is VALUE_NONE type.
     *
     * Verifies the --dry-run option is a boolean flag.
     */
    public function test_dry_run_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('dry-run');

        $this->assertFalse($option->acceptValue());
    }

    /**
     * Test that clear-cache option is VALUE_NONE type.
     *
     * Verifies the --clear-cache option is a boolean flag.
     */
    public function test_clear_cache_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('clear-cache');

        $this->assertFalse($option->acceptValue());
    }

    /**
     * Test that workspace option is VALUE_REQUIRED type.
     *
     * Verifies the --workspace option requires a value.
     */
    public function test_workspace_option_is_value_required(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('workspace');

        $this->assertTrue($option->isValueRequired());
    }

    /**
     * Test that dry-run option has correct description.
     *
     * Verifies the --dry-run option description mentions preview mode.
     */
    public function test_dry_run_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('dry-run');

        $this->assertStringContainsString('Show changes without applying', $option->getDescription());
    }

    /**
     * Test that clear-cache option has correct description.
     *
     * Verifies the --clear-cache option description mentions cache clearing.
     */
    public function test_clear_cache_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('clear-cache');

        $this->assertStringContainsString('Clear Rector cache', $option->getDescription());
    }

    /**
     * Test that command has help text.
     *
     * Verifies the command provides detailed help information.
     */
    public function test_command_has_help_text(): void
    {
        $help = $this->command->getHelp();

        $this->assertNotEmpty($help);
        $this->assertStringContainsString('refactor', $help);
        $this->assertStringContainsString('Rector', $help);
    }
}
