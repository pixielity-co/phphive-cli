<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Turbo;

use PhpHive\Cli\Console\Commands\Turbo\RunCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Run Command Test.
 *
 * Tests for the RunCommand that executes arbitrary Turborepo tasks
 * across workspaces. Verifies task execution, workspace filtering,
 * parallel execution, and continue-on-error options.
 */
final class RunCommandTest extends TestCase
{
    /**
     * The command instance being tested.
     */
    private RunCommand $command;

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

        $this->command = new RunCommand();
        $this->output = new BufferedOutput();

        $container = new Container();
        $this->command->setContainer($container);
    }

    /**
     * Test that command has correct name and aliases.
     */
    public function test_command_has_correct_name_and_aliases(): void
    {
        $this->assertSame('turbo:run', $this->command->getName());
        $this->assertContains('run', $this->command->getAliases());
        $this->assertContains('exec', $this->command->getAliases());
        $this->assertContains('execute', $this->command->getAliases());
    }

    /**
     * Test that command has correct description.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run a Turbo task', $this->command->getDescription());
    }

    /**
     * Test that command has task argument.
     */
    public function test_command_has_task_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('task'));
        $this->assertTrue($definition->getArgument('task')->isRequired());
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

        $this->assertTrue($definition->hasOption('workspace'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('no-cache'));
        $this->assertTrue($definition->hasOption('no-interaction'));
    }

    /**
     * Test that command can be instantiated.
     */
    public function test_command_can_be_instantiated(): void
    {
        $this->assertInstanceOf(RunCommand::class, $this->command);
        $this->assertInstanceOf(Command::class, $this->command);
    }

    /**
     * Test that command accepts task argument.
     */
    public function test_command_accepts_task_argument(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'build'], $definition);

        $this->assertSame('build', $input->getArgument('task'));
    }

    /**
     * Test that command accepts parallel option.
     */
    public function test_command_accepts_parallel_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'test', '--parallel' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('parallel'));
    }

    /**
     * Test that command accepts parallel shortcut.
     */
    public function test_command_accepts_parallel_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'test', '-p' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('parallel'));
    }

    /**
     * Test that command accepts continue option.
     */
    public function test_command_accepts_continue_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'lint', '--continue' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('continue'));
    }

    /**
     * Test that command accepts workspace option with task.
     */
    public function test_command_accepts_workspace_option_with_task(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'build', '--workspace' => 'api'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('api', $this->command->option('workspace'));
    }

    /**
     * Test that command accepts force option with task.
     */
    public function test_command_accepts_force_option_with_task(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'test', '--force' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('force'));
    }

    /**
     * Test that command accepts no-cache option with task.
     */
    public function test_command_accepts_no_cache_option_with_task(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'build', '--no-cache' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('no-cache'));
    }

    /**
     * Test that command accepts multiple options with task.
     */
    public function test_command_accepts_multiple_options_with_task(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            'task' => 'build',
            '--workspace' => 'api',
            '--force' => true,
            '--parallel' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('api', $this->command->option('workspace'));
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
            'task' => 'test',
            '--workspace' => 'admin',
            '--force' => true,
            '--no-cache' => true,
            '--parallel' => true,
            '--continue' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('admin', $this->command->option('workspace'));
        $this->assertTrue($this->command->hasOption('force'));
        $this->assertTrue($this->command->hasOption('no-cache'));
        $this->assertTrue($this->command->hasOption('parallel'));
        $this->assertTrue($this->command->hasOption('continue'));
    }

    /**
     * Test that parallel option defaults to false.
     */
    public function test_parallel_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'build'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('parallel'));
    }

    /**
     * Test that continue option defaults to false.
     */
    public function test_continue_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'test'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('continue'));
    }

    /**
     * Test that command accepts different task names.
     */
    public function test_command_accepts_different_task_names(): void
    {
        $definition = $this->command->getDefinition();

        $tasks = ['build', 'test', 'lint', 'typecheck', 'format', 'clean', 'deploy'];

        foreach ($tasks as $task) {
            $input = new ArrayInput(['task' => $task], $definition);
            $this->assertSame($task, $input->getArgument('task'));
        }
    }

    /**
     * Test that command accepts custom task names.
     */
    public function test_command_accepts_custom_task_names(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['task' => 'custom-task'], $definition);

        $this->assertSame('custom-task', $input->getArgument('task'));
    }

    /**
     * Test that command has help text.
     */
    public function test_command_has_help_text(): void
    {
        $help = $this->command->getHelp();

        $this->assertStringContainsString('run', $help);
        $this->assertStringContainsString('task', $help);
    }
}
