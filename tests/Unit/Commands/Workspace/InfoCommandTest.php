<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Workspace;

use PhpHive\Cli\Console\Commands\Workspace\InfoCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Info Command Test.
 *
 * Tests for the InfoCommand that displays detailed workspace information.
 * Verifies workspace info display, output formats (text, JSON, table),
 * and path display options.
 */
final class InfoCommandTest extends TestCase
{
    /**
     * The command instance being tested.
     */
    private InfoCommand $command;

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

        $this->command = new InfoCommand();
        $this->output = new BufferedOutput();

        $container = new Container();
        $this->command->setContainer($container);
    }

    /**
     * Test that command has correct name and aliases.
     */
    public function test_command_has_correct_name_and_aliases(): void
    {
        $this->assertSame('workspace:info', $this->command->getName());
        $this->assertContains('info', $this->command->getAliases());
        $this->assertContains('show', $this->command->getAliases());
        $this->assertContains('details', $this->command->getAliases());
    }

    /**
     * Test that command has correct description.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Show detailed workspace information', $this->command->getDescription());
    }

    /**
     * Test that command has workspace argument.
     */
    public function test_command_has_workspace_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('workspace'));
        $this->assertFalse($definition->getArgument('workspace')->isRequired());
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
     * Test that command has format option.
     */
    public function test_command_has_format_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('format'));
        $this->assertSame('f', $definition->getOption('format')->getShortcut());
    }

    /**
     * Test that command has absolute option.
     */
    public function test_command_has_absolute_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('absolute'));
    }

    /**
     * Test that command can be instantiated.
     */
    public function test_command_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InfoCommand::class, $this->command);
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
     * Test that command accepts workspace argument.
     */
    public function test_command_accepts_workspace_argument(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api'], $definition);

        $this->assertSame('api', $input->getArgument('workspace'));
    }

    /**
     * Test that command accepts json option.
     */
    public function test_command_accepts_json_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api', '--json' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
    }

    /**
     * Test that command accepts json shortcut.
     */
    public function test_command_accepts_json_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api', '-j' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
    }

    /**
     * Test that command accepts format option with text.
     */
    public function test_command_accepts_format_option_with_text(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api', '--format' => 'text'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('text', $this->command->option('format'));
    }

    /**
     * Test that command accepts format option with json.
     */
    public function test_command_accepts_format_option_with_json(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api', '--format' => 'json'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('json', $this->command->option('format'));
    }

    /**
     * Test that command accepts format option with table.
     */
    public function test_command_accepts_format_option_with_table(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api', '--format' => 'table'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('table', $this->command->option('format'));
    }

    /**
     * Test that command accepts format shortcut.
     */
    public function test_command_accepts_format_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api', '-f' => 'json'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('json', $this->command->option('format'));
    }

    /**
     * Test that command accepts absolute option.
     */
    public function test_command_accepts_absolute_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api', '--absolute' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('absolute'));
    }

    /**
     * Test that format option defaults to text.
     */
    public function test_format_option_defaults_to_text(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('text', $this->command->option('format'));
    }

    /**
     * Test that json option defaults to false.
     */
    public function test_json_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('json'));
    }

    /**
     * Test that absolute option defaults to false.
     */
    public function test_absolute_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['workspace' => 'api'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('absolute'));
    }

    /**
     * Test that workspace argument defaults to null.
     */
    public function test_workspace_argument_defaults_to_null(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->assertNull($input->getArgument('workspace'));
    }

    /**
     * Test that command accepts json and absolute together.
     */
    public function test_command_accepts_json_and_absolute_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'workspace' => 'api',
            '--json' => true,
            '--absolute' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
        $this->assertTrue($this->command->hasOption('absolute'));
    }

    /**
     * Test that command accepts format and absolute together.
     */
    public function test_command_accepts_format_and_absolute_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'workspace' => 'api',
            '--format' => 'table',
            '--absolute' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('table', $this->command->option('format'));
        $this->assertTrue($this->command->hasOption('absolute'));
    }

    /**
     * Test that command accepts all shortcuts together.
     */
    public function test_command_accepts_all_shortcuts_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'workspace' => 'api',
            '-j' => true,
            '-f' => 'json',
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('json'));
        $this->assertSame('json', $this->command->option('format'));
    }

    /**
     * Test that command accepts different workspace names.
     */
    public function test_command_accepts_different_workspace_names(): void
    {
        $definition = $this->command->getDefinition();

        $workspaces = ['api', 'admin', 'calculator', 'demo-app', 'cli'];

        foreach ($workspaces as $workspace) {
            $input = new ArrayInput(['workspace' => $workspace], $definition);
            $this->assertSame($workspace, $input->getArgument('workspace'));
        }
    }

    /**
     * Test that command has help text.
     */
    public function test_command_has_help_text(): void
    {
        $help = $this->command->getHelp();

        $this->assertStringContainsString('info', $help);
        $this->assertStringContainsString('workspace', $help);
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
