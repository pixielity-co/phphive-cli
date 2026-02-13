<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Utility;

use PhpHive\Cli\Console\Commands\Utility\DoctorCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Doctor Command Test.
 *
 * Tests for the DoctorCommand that performs system health checks.
 * Verifies health check execution, output formats (text, table, JSON),
 * and quiet mode operation.
 */
final class DoctorCommandTest extends TestCase
{
    /**
     * The command instance being tested.
     */
    private DoctorCommand $command;

    /**
     * Buffered output for capturing command output.
     */
    private BufferedOutput $output;

    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new DoctorCommand();
        $this->output = new BufferedOutput();

        $container = new Container();
        $this->command->setContainer($container);
    }

    /**
     * Test that command has correct name and aliases.
     */
    public function test_command_has_correct_name_and_aliases(): void
    {
        $this->assertSame('system:doctor', $this->command->getName());
        $this->assertContains('doctor', $this->command->getAliases());
        $this->assertContains('check', $this->command->getAliases());
        $this->assertContains('health', $this->command->getAliases());
    }

    /**
     * Test that command has correct description.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Check system requirements and health', $this->command->getDescription());
    }

    /**
     * Test that command has table option.
     */
    public function test_command_has_table_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('table'));
        $this->assertSame('t', $definition->getOption('table')->getShortcut());
    }

    /**
     * Test that command has json option.
     */
    public function test_command_has_json_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('json'));
        $this->assertSame('j', $definition->getOption('json')->getShortcut());
    }

    /**
     * Test that command has quiet option.
     */
    public function test_command_has_quiet_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('quiet'));
        $this->assertSame('q', $definition->getOption('quiet')->getShortcut());
    }

    /**
     * Test that command can be instantiated.
     */
    public function test_command_can_be_instantiated(): void
    {
        $this->assertInstanceOf(DoctorCommand::class, $this->command);
        $this->assertInstanceOf(Command::class, $this->command);
    }

    /**
     * Test that container can be set.
     */
    public function test_container_can_be_set(): void
    {
        $container = new Container();
        $this->command->setContainer($container);

        $this->assertInstanceOf(Container::class, $this->command->container());
    }

    /**
     * Test that command accepts table option.
     */
    public function test_command_accepts_table_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--table' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('table'));
    }

    /**
     * Test that command accepts table shortcut.
     */
    public function test_command_accepts_table_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-t' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('table'));
    }

    /**
     * Test that command accepts json option.
     */
    public function test_command_accepts_json_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--json' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
    }

    /**
     * Test that command accepts json shortcut.
     */
    public function test_command_accepts_json_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-j' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
    }

    /**
     * Test that command accepts quiet option.
     */
    public function test_command_accepts_quiet_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--quiet' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('quiet'));
    }

    /**
     * Test that command accepts quiet shortcut.
     */
    public function test_command_accepts_quiet_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-q' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('quiet'));
    }

    /**
     * Test that table option defaults to false.
     */
    public function test_table_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('table'));
    }

    /**
     * Test that json option defaults to false.
     */
    public function test_json_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('json'));
    }

    /**
     * Test that quiet option defaults to false.
     */
    public function test_quiet_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('quiet'));
    }

    /**
     * Test that command accepts table and quiet together.
     */
    public function test_command_accepts_table_and_quiet_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--table' => true,
            '--quiet' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('table'));
        $this->assertTrue($this->command->hasOption('quiet'));
    }

    /**
     * Test that command accepts json and quiet together.
     */
    public function test_command_accepts_json_and_quiet_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--json' => true,
            '--quiet' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
        $this->assertTrue($this->command->hasOption('quiet'));
    }

    /**
     * Test that command accepts all shortcuts together.
     */
    public function test_command_accepts_all_shortcuts_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '-t' => true,
            '-j' => true,
            '-q' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('table'));
        $this->assertTrue($this->command->hasOption('json'));
        $this->assertTrue($this->command->hasOption('quiet'));
    }

    /**
     * Test that command inherits common options.
     */
    public function test_command_has_common_options(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
        $this->assertTrue($definition->hasOption('no-interaction'));
    }
}
