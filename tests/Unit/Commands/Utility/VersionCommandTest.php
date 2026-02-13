<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Utility;

use PhpHive\Cli\Console\Commands\Utility\VersionCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Version Command Test.
 *
 * Tests for the VersionCommand that displays version information.
 * Verifies version display, output formats (text, JSON, short),
 * and tool-specific version queries.
 */
final class VersionCommandTest extends TestCase
{
    /**
     * The command instance being tested.
     */
    private VersionCommand $command;

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

        $this->command = new VersionCommand();
        $this->output = new BufferedOutput();

        $container = new Container();
        $this->command->setContainer($container);
    }

    /**
     * Test that command has correct name and aliases.
     */
    public function test_command_has_correct_name_and_aliases(): void
    {
        $this->assertSame('system:version', $this->command->getName());
        $this->assertContains('version', $this->command->getAliases());
        $this->assertContains('ver', $this->command->getAliases());
        $this->assertContains('v', $this->command->getAliases());
    }

    /**
     * Test that command has correct description.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Show version information', $this->command->getDescription());
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
     * Test that command has short option.
     */
    public function test_command_has_short_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('short'));
        $this->assertSame('s', $definition->getOption('short')->getShortcut());
    }

    /**
     * Test that command has tool option.
     */
    public function test_command_has_tool_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('tool'));
    }

    /**
     * Test that command can be instantiated.
     */
    public function test_command_can_be_instantiated(): void
    {
        $this->assertInstanceOf(VersionCommand::class, $this->command);
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
     * Test that command accepts short option.
     */
    public function test_command_accepts_short_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--short' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('short'));
    }

    /**
     * Test that command accepts short shortcut.
     */
    public function test_command_accepts_short_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-s' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('short'));
    }

    /**
     * Test that command accepts tool option with cli.
     */
    public function test_command_accepts_tool_option_with_cli(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--tool' => 'cli'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('cli', $this->command->option('tool'));
    }

    /**
     * Test that command accepts tool option with php.
     */
    public function test_command_accepts_tool_option_with_php(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--tool' => 'php'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('php', $this->command->option('tool'));
    }

    /**
     * Test that command accepts tool option with composer.
     */
    public function test_command_accepts_tool_option_with_composer(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--tool' => 'composer'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('composer', $this->command->option('tool'));
    }

    /**
     * Test that command accepts tool option with turbo.
     */
    public function test_command_accepts_tool_option_with_turbo(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--tool' => 'turbo'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('turbo', $this->command->option('tool'));
    }

    /**
     * Test that command accepts tool option with node.
     */
    public function test_command_accepts_tool_option_with_node(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--tool' => 'node'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('node', $this->command->option('tool'));
    }

    /**
     * Test that command accepts tool option with pnpm.
     */
    public function test_command_accepts_tool_option_with_pnpm(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--tool' => 'pnpm'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('pnpm', $this->command->option('tool'));
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
     * Test that short option defaults to false.
     */
    public function test_short_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('short'));
    }

    /**
     * Test that tool option defaults to null.
     */
    public function test_tool_option_defaults_to_null(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertNull($this->command->option('tool'));
    }

    /**
     * Test that command accepts json and short together.
     */
    public function test_command_accepts_json_and_short_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--json' => true,
            '--short' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
        $this->assertTrue($this->command->hasOption('short'));
    }

    /**
     * Test that command accepts tool with json.
     */
    public function test_command_accepts_tool_with_json(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--tool' => 'cli',
            '--json' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('cli', $this->command->option('tool'));
        $this->assertTrue($this->command->hasOption('json'));
    }

    /**
     * Test that command accepts tool with short.
     */
    public function test_command_accepts_tool_with_short(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--tool' => 'php',
            '--short' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('php', $this->command->option('tool'));
        $this->assertTrue($this->command->hasOption('short'));
    }

    /**
     * Test that command accepts all shortcuts together.
     */
    public function test_command_accepts_all_shortcuts_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '-j' => true,
            '-s' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
        $this->assertTrue($this->command->hasOption('short'));
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
