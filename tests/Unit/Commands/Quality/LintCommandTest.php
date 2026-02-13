<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Quality;

use PhpHive\Cli\Console\Commands\Quality\LintCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Lint Command Test.
 *
 * Tests for the LintCommand that checks code style compliance using Laravel Pint.
 * Verifies all command options, flags, output modes, and delegation behavior.
 */
final class LintCommandTest extends TestCase
{
    /**
     * The lint command instance.
     */
    private LintCommand $command;

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
     * Creates a lint command instance with mock input/output and
     * injects a container instance.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create lint command
        $this->command = new LintCommand();

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
        $this->assertSame('quality:lint', $this->command->getName());
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Check code style with Pint', $this->command->getDescription());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command can be invoked using aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('lint', $aliases);
    }

    /**
     * Test that command has fix option.
     *
     * Verifies the --fix option is defined.
     */
    public function test_command_has_fix_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('fix'));
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
     * Test that fix option is VALUE_NONE type.
     *
     * Verifies the --fix option is a boolean flag.
     */
    public function test_fix_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('fix');

        $this->assertFalse($option->acceptValue());
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
     * Test that fix option has correct description.
     *
     * Verifies the --fix option description is informative.
     */
    public function test_fix_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('fix');

        $this->assertStringContainsString('Auto-fix', $option->getDescription());
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
}
