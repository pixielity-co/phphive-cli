<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Quality;

use PhpHive\Cli\Console\Commands\Quality\TypecheckCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Typecheck Command Test.
 *
 * Tests for the TypecheckCommand that runs static analysis with PHPStan.
 * Verifies all command options, flags, level configuration, and output modes.
 */
final class TypecheckCommandTest extends TestCase
{
    /**
     * The typecheck command instance.
     */
    private TypecheckCommand $command;

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
     * Creates a typecheck command instance with mock input/output and
     * injects a container instance.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create typecheck command
        $this->command = new TypecheckCommand();

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
        $this->assertSame('quality:typecheck', $this->command->getName());
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run static analysis with PHPStan', $this->command->getDescription());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command can be invoked using aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('typecheck', $aliases);
        $this->assertContains('tc', $aliases);
        $this->assertContains('phpstan', $aliases);
    }

    /**
     * Test that command has level option.
     *
     * Verifies the --level/-l option is defined.
     */
    public function test_command_has_level_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('level'));

        $option = $definition->getOption('level');
        $this->assertSame('l', $option->getShortcut());
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
     * Test that level option is VALUE_REQUIRED type.
     *
     * Verifies the --level option requires a value.
     */
    public function test_level_option_is_value_required(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('level');

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
     * Test that level option has correct description.
     *
     * Verifies the --level option description mentions PHPStan levels.
     */
    public function test_level_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('level');

        $this->assertStringContainsString('PHPStan level', $option->getDescription());
        $this->assertStringContainsString('0-9', $option->getDescription());
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
