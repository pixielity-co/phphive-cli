<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Lifecycle;

use PhpHive\Cli\Console\Commands\Lifecycle\InstallCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Install Command Test.
 *
 * Tests for the InstallCommand that installs all dependencies across
 * the monorepo. Verifies dependency installation, workspace filtering,
 * force reinstall, and Turbo integration.
 */
final class InstallCommandTest extends TestCase
{
    /**
     * The command instance being tested.
     */
    private InstallCommand $command;

    /**
     * Buffered output for capturing command output.
     */
    private BufferedOutput $output;

    /**
     * Set up the test environment before each test.
     *
     * Creates a command instance with container and output buffer.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new InstallCommand();
        $this->output = new BufferedOutput();

        $container = new Container();
        $this->command->setContainer($container);
    }

    /**
     * Test that command has correct name and aliases.
     *
     * Verifies the command is registered with the expected name
     * and multiple aliases for user convenience.
     */
    public function test_command_has_correct_name_and_aliases(): void
    {
        $this->assertSame('composer:install', $this->command->getName());
        $this->assertContains('install', $this->command->getAliases());
        $this->assertContains('i', $this->command->getAliases());
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set correctly for help output.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Install all dependencies', $this->command->getDescription());
    }

    /**
     * Test that command inherits common options from BaseCommand.
     *
     * Verifies that workspace, force, no-cache, and no-interaction
     * options are available from the parent class.
     */
    public function test_command_has_common_options(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
        $this->assertTrue($definition->hasOption('no-interaction'));
    }

    /**
     * Test that workspace option has correct shortcut.
     *
     * Verifies the -w shortcut is available for the workspace option.
     */
    public function test_workspace_option_has_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('workspace');

        $this->assertSame('w', $option->getShortcut());
    }

    /**
     * Test that force option has correct shortcut.
     *
     * Verifies the -f shortcut is available for the force option.
     */
    public function test_force_option_has_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('force');

        $this->assertSame('f', $option->getShortcut());
    }

    /**
     * Test that no-interaction option has correct shortcut.
     *
     * Verifies the -n shortcut is available for the no-interaction option.
     */
    public function test_no_interaction_option_has_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('no-interaction');

        $this->assertSame('n', $option->getShortcut());
    }

    /**
     * Test that command can be instantiated.
     *
     * Verifies the command object can be created successfully.
     */
    public function test_command_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InstallCommand::class, $this->command);
        $this->assertInstanceOf(Command::class, $this->command);
    }

    /**
     * Test that container can be set and retrieved.
     *
     * Verifies the dependency injection container integration works.
     */
    public function test_container_can_be_set(): void
    {
        $container = new Container();
        $this->command->setContainer($container);

        $this->assertInstanceOf(Container::class, $this->command->container());
    }

    /**
     * Test that command accepts workspace option.
     *
     * Verifies the workspace option can be passed and retrieved.
     */
    public function test_command_accepts_workspace_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--workspace' => 'demo-app'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('demo-app', $this->command->option('workspace'));
    }

    /**
     * Test that command accepts workspace shortcut.
     *
     * Verifies the -w shortcut works for workspace option.
     */
    public function test_command_accepts_workspace_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-w' => 'calculator'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('calculator', $this->command->option('workspace'));
    }

    /**
     * Test that command accepts force option.
     *
     * Verifies the force option can be passed and detected.
     */
    public function test_command_accepts_force_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--force' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('force'));
    }

    /**
     * Test that command accepts force shortcut.
     *
     * Verifies the -f shortcut works for force option.
     */
    public function test_command_accepts_force_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-f' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('force'));
    }

    /**
     * Test that command accepts no-cache option.
     *
     * Verifies the no-cache option can be passed and detected.
     */
    public function test_command_accepts_no_cache_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--no-cache' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('no-cache'));
    }

    /**
     * Test that command accepts no-interaction option.
     *
     * Verifies the no-interaction option can be passed and detected.
     */
    public function test_command_accepts_no_interaction_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--no-interaction' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('no-interaction'));
    }

    /**
     * Test that command accepts no-interaction shortcut.
     *
     * Verifies the -n shortcut works for no-interaction option.
     */
    public function test_command_accepts_no_interaction_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-n' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('no-interaction'));
    }

    /**
     * Test that command accepts multiple options simultaneously.
     *
     * Verifies multiple options can be combined in a single command.
     */
    public function test_command_accepts_multiple_options(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--workspace' => 'api',
            '--force' => true,
            '--no-cache' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('api', $this->command->option('workspace'));
        $this->assertTrue($this->command->hasOption('force'));
        $this->assertTrue($this->command->hasOption('no-cache'));
    }

    /**
     * Test that command accepts shortcut combinations.
     *
     * Verifies multiple shortcuts can be used together.
     */
    public function test_command_accepts_shortcut_combinations(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '-w' => 'admin',
            '-f' => true,
            '-n' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('admin', $this->command->option('workspace'));
        $this->assertTrue($this->command->hasOption('force'));
        $this->assertTrue($this->command->hasOption('no-interaction'));
    }

    /**
     * Test that workspace option defaults to null.
     *
     * Verifies the workspace option is null when not specified.
     */
    public function test_workspace_option_defaults_to_null(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertNull($this->command->option('workspace'));
    }

    /**
     * Test that force option defaults to false.
     *
     * Verifies the force option is false when not specified.
     */
    public function test_force_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('force'));
    }

    /**
     * Test that no-cache option defaults to false.
     *
     * Verifies the no-cache option is false when not specified.
     */
    public function test_no_cache_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('no-cache'));
    }

    /**
     * Test that no-interaction option defaults to false.
     *
     * Verifies the no-interaction option is false when not specified.
     */
    public function test_no_interaction_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('no-interaction'));
    }

    /**
     * Test that force and no-cache can be combined.
     *
     * Verifies that force and no-cache options work together.
     */
    public function test_force_and_no_cache_can_be_combined(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--force' => true,
            '--no-cache' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('force'));
        $this->assertTrue($this->command->hasOption('no-cache'));
    }

    /**
     * Test that workspace and force can be combined.
     *
     * Verifies that workspace and force options work together.
     */
    public function test_workspace_and_force_can_be_combined(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--workspace' => 'demo-app',
            '--force' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('demo-app', $this->command->option('workspace'));
        $this->assertTrue($this->command->hasOption('force'));
    }
}
