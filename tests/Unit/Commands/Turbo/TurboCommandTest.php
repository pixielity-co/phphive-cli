<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Turbo;

use PhpHive\Cli\Console\Commands\Turbo\TurboCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Turbo Command Test.
 *
 * Tests for the TurboCommand that provides direct access to Turborepo CLI.
 * Verifies command passthrough, filtering, parallel execution, and all
 * Turbo command options.
 */
final class TurboCommandTest extends TestCase
{
    /**
     * The command instance being tested.
     */
    private TurboCommand $command;

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

        $this->command = new TurboCommand();
        $this->output = new BufferedOutput();

        $container = new Container();
        $this->command->setContainer($container);
    }

    /**
     * Test that command has correct name and aliases.
     */
    public function test_command_has_correct_name_and_aliases(): void
    {
        $this->assertSame('turbo:exec', $this->command->getName());
        $this->assertContains('turbo', $this->command->getAliases());
        $this->assertContains('tb', $this->command->getAliases());
    }

    /**
     * Test that command has correct description.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run Turborepo command directly', $this->command->getDescription());
    }

    /**
     * Test that command has command argument.
     */
    public function test_command_has_command_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('command'));
        $this->assertTrue($definition->getArgument('command')->isRequired());
        $this->assertTrue($definition->getArgument('command')->isArray());
    }

    /**
     * Test that command has filter option.
     */
    public function test_command_has_filter_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('filter'));
    }

    /**
     * Test that command has parallel option.
     */
    public function test_command_has_parallel_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('parallel'));
        $this->assertSame('p', $definition->getOption('parallel')->getShortcut());
    }

    /**
     * Test that command has continue option.
     */
    public function test_command_has_continue_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('continue'));
    }

    /**
     * Test that command inherits common options.
     */
    public function test_command_has_common_options(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
        $this->assertTrue($definition->hasOption('no-interaction'));
    }

    /**
     * Test that command can be instantiated.
     */
    public function test_command_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TurboCommand::class, $this->command);
        $this->assertInstanceOf(Command::class, $this->command);
    }

    /**
     * Test that command accepts single word command.
     */
    public function test_command_accepts_single_word_command(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['command' => ['run']], $definition);

        $this->assertSame(['run'], $input->getArgument('command'));
    }

    /**
     * Test that command accepts multi-word command.
     */
    public function test_command_accepts_multi_word_command(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['command' => ['daemon', 'start']], $definition);

        $this->assertSame(['daemon', 'start'], $input->getArgument('command'));
    }

    /**
     * Test that command accepts filter option.
     */
    public function test_command_accepts_filter_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'command' => ['run', 'build'],
            '--filter' => 'api',
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('api', $this->command->option('filter'));
    }

    /**
     * Test that command accepts parallel option.
     */
    public function test_command_accepts_parallel_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'command' => ['run', 'test'],
            '--parallel' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('parallel'));
    }

    /**
     * Test that command accepts parallel shortcut.
     */
    public function test_command_accepts_parallel_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'command' => ['run', 'build'],
            '-p' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('parallel'));
    }

    /**
     * Test that command accepts continue option.
     */
    public function test_command_accepts_continue_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'command' => ['run', 'lint'],
            '--continue' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('continue'));
    }

    /**
     * Test that command accepts force option.
     */
    public function test_command_accepts_force_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'command' => ['run', 'build'],
            '--force' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('force'));
    }

    /**
     * Test that command accepts no-cache option.
     */
    public function test_command_accepts_no_cache_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'command' => ['run', 'test'],
            '--no-cache' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('no-cache'));
    }

    /**
     * Test that command accepts multiple options.
     */
    public function test_command_accepts_multiple_options(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'command' => ['run', 'build'],
            '--filter' => 'api',
            '--force' => true,
            '--parallel' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('api', $this->command->option('filter'));
        $this->assertTrue($this->command->hasOption('force'));
        $this->assertTrue($this->command->hasOption('parallel'));
    }

    /**
     * Test that command accepts all options combined.
     */
    public function test_command_accepts_all_options_combined(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'command' => ['run', 'test'],
            '--filter' => 'admin',
            '--force' => true,
            '--no-cache' => true,
            '--parallel' => true,
            '--continue' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('admin', $this->command->option('filter'));
        $this->assertTrue($this->command->hasOption('force'));
        $this->assertTrue($this->command->hasOption('no-cache'));
        $this->assertTrue($this->command->hasOption('parallel'));
        $this->assertTrue($this->command->hasOption('continue'));
    }

    /**
     * Test that command accepts run command.
     */
    public function test_command_accepts_run_command(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['command' => ['run', 'build']], $definition);

        $this->assertSame(['run', 'build'], $input->getArgument('command'));
    }

    /**
     * Test that command accepts prune command.
     */
    public function test_command_accepts_prune_command(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['command' => ['prune', '--scope=api']], $definition);

        $this->assertSame(['prune', '--scope=api'], $input->getArgument('command'));
    }

    /**
     * Test that command accepts daemon commands.
     */
    public function test_command_accepts_daemon_commands(): void
    {
        $definition = $this->command->getDefinition();

        $daemonCommands = [
            ['daemon', 'start'],
            ['daemon', 'stop'],
            ['daemon', 'status'],
        ];

        foreach ($daemonCommands as $daemonCommand) {
            $input = new ArrayInput(['command' => $daemonCommand], $definition);
            $this->assertSame($daemonCommand, $input->getArgument('command'));
        }
    }

    /**
     * Test that filter option defaults to null.
     */
    public function test_filter_option_defaults_to_null(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['command' => ['run', 'build']], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertNull($this->command->option('filter'));
    }

    /**
     * Test that parallel option defaults to false.
     */
    public function test_parallel_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['command' => ['run', 'test']], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('parallel'));
    }

    /**
     * Test that continue option defaults to false.
     */
    public function test_continue_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['command' => ['run', 'lint']], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('continue'));
    }

    /**
     * Test that command has help text.
     */
    public function test_command_has_help_text(): void
    {
        $help = $this->command->getHelp();

        $this->assertStringContainsString('turbo', $help);
        $this->assertStringContainsString('command', $help);
    }
}
