<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Quality;

use PhpHive\Cli\Console\Commands\Quality\TestCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Test Command Test.
 *
 * Tests for the TestCommand that runs PHPUnit tests across workspaces.
 * Verifies all command options, flags, test type filtering, and output modes.
 */
final class TestCommandTest extends TestCase
{
    /**
     * The test command instance.
     */
    private TestCommand $command;

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
     * Creates a test command instance with mock input/output and
     * injects a container instance.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test command
        $this->command = new TestCommand();

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
        $this->assertSame('quality:test', $this->command->getName());
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run PHPUnit tests', $this->command->getDescription());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command can be invoked using aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('test', $aliases);
        $this->assertContains('t', $aliases);
        $this->assertContains('phpunit', $aliases);
    }

    /**
     * Test that command has unit option.
     *
     * Verifies the --unit/-u option is defined.
     */
    public function test_command_has_unit_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('unit'));

        $option = $definition->getOption('unit');
        $this->assertSame('u', $option->getShortcut());
    }

    /**
     * Test that command has feature option.
     *
     * Verifies the --feature option is defined.
     */
    public function test_command_has_feature_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('feature'));
    }

    /**
     * Test that command has coverage option.
     *
     * Verifies the --coverage/-c option is defined.
     */
    public function test_command_has_coverage_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('coverage'));

        $option = $definition->getOption('coverage');
        $this->assertSame('c', $option->getShortcut());
    }

    /**
     * Test that command has filter option.
     *
     * Verifies the --filter option is defined.
     */
    public function test_command_has_filter_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('filter'));
    }

    /**
     * Test that command has json option.
     *
     * Verifies the --json/-j option is defined.
     */
    public function test_command_has_json_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('json'));

        $option = $definition->getOption('json');
        $this->assertSame('j', $option->getShortcut());
    }

    /**
     * Test that command has table option.
     *
     * Verifies the --table option is defined.
     */
    public function test_command_has_table_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('table'));
    }

    /**
     * Test that command has summary option.
     *
     * Verifies the --summary/-s option is defined.
     */
    public function test_command_has_summary_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('summary'));

        $option = $definition->getOption('summary');
        $this->assertSame('s', $option->getShortcut());
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
     * Test that unit option is VALUE_NONE type.
     *
     * Verifies the --unit option is a boolean flag.
     */
    public function test_unit_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('unit');

        $this->assertFalse($option->acceptValue());
    }

    /**
     * Test that feature option is VALUE_NONE type.
     *
     * Verifies the --feature option is a boolean flag.
     */
    public function test_feature_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('feature');

        $this->assertFalse($option->acceptValue());
    }

    /**
     * Test that coverage option is VALUE_NONE type.
     *
     * Verifies the --coverage option is a boolean flag.
     */
    public function test_coverage_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('coverage');

        $this->assertFalse($option->acceptValue());
    }

    /**
     * Test that filter option is VALUE_REQUIRED type.
     *
     * Verifies the --filter option requires a value.
     */
    public function test_filter_option_is_value_required(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('filter');

        $this->assertTrue($option->isValueRequired());
    }

    /**
     * Test that json option is VALUE_NONE type.
     *
     * Verifies the --json option is a boolean flag.
     */
    public function test_json_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('json');

        $this->assertFalse($option->acceptValue());
    }

    /**
     * Test that table option is VALUE_NONE type.
     *
     * Verifies the --table option is a boolean flag.
     */
    public function test_table_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('table');

        $this->assertFalse($option->acceptValue());
    }

    /**
     * Test that summary option is VALUE_NONE type.
     *
     * Verifies the --summary option is a boolean flag.
     */
    public function test_summary_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('summary');

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
     * Test that unit option has correct description.
     *
     * Verifies the --unit option description is informative.
     */
    public function test_unit_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('unit');

        $this->assertStringContainsString('unit tests', $option->getDescription());
    }

    /**
     * Test that feature option has correct description.
     *
     * Verifies the --feature option description is informative.
     */
    public function test_feature_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('feature');

        $this->assertStringContainsString('feature tests', $option->getDescription());
    }

    /**
     * Test that coverage option has correct description.
     *
     * Verifies the --coverage option description is informative.
     */
    public function test_coverage_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('coverage');

        $this->assertStringContainsString('coverage', $option->getDescription());
    }

    /**
     * Test that filter option has correct description.
     *
     * Verifies the --filter option description is informative.
     */
    public function test_filter_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('filter');

        $this->assertStringContainsString('Filter', $option->getDescription());
    }

    /**
     * Test that json option has correct description.
     *
     * Verifies the --json option description mentions CI/CD integration.
     */
    public function test_json_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('json');

        $this->assertStringContainsString('JSON', $option->getDescription());
    }

    /**
     * Test that table option has correct description.
     *
     * Verifies the --table option description mentions table format.
     */
    public function test_table_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('table');

        $this->assertStringContainsString('table', $option->getDescription());
    }

    /**
     * Test that summary option has correct description.
     *
     * Verifies the --summary option description is informative.
     */
    public function test_summary_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('summary');

        $this->assertStringContainsString('overview', $option->getDescription());
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
        $this->assertStringContainsString('test', $help);
    }
}
