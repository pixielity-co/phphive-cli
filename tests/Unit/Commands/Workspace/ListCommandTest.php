<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Workspace;

use PhpHive\Cli\Console\Commands\Workspace\ListCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * List Command Test.
 *
 * Tests for the ListCommand that lists all workspaces in the monorepo.
 * Verifies workspace listing, filtering (apps/packages), output formats
 * (table, JSON, compact), sorting, and column customization.
 */
final class ListCommandTest extends TestCase
{
    /**
     * The command instance being tested.
     */
    private ListCommand $command;

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

        $this->command = new ListCommand();
        $this->output = new BufferedOutput();

        $container = new Container();
        $this->command->setContainer($container);
    }

    /**
     * Test that command has correct name and aliases.
     */
    public function test_command_has_correct_name_and_aliases(): void
    {
        $this->assertSame('workspace:list', $this->command->getName());
        $this->assertContains('list-workspaces', $this->command->getAliases());
        $this->assertContains('ls', $this->command->getAliases());
        $this->assertContains('workspaces', $this->command->getAliases());
    }

    /**
     * Test that command has correct description.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('List all workspaces', $this->command->getDescription());
    }

    /**
     * Test that command has apps option.
     */
    public function test_command_has_apps_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('apps'));
        $this->assertSame('a', $definition->getOption('apps')->getShortcut());
    }

    /**
     * Test that command has packages option.
     */
    public function test_command_has_packages_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('packages'));
        $this->assertSame('p', $definition->getOption('packages')->getShortcut());
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
     * Test that command has compact option.
     */
    public function test_command_has_compact_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('compact'));
        $this->assertSame('c', $definition->getOption('compact')->getShortcut());
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
     * Test that command has sort option.
     */
    public function test_command_has_sort_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('sort'));
        $this->assertSame('s', $definition->getOption('sort')->getShortcut());
    }

    /**
     * Test that command has columns option.
     */
    public function test_command_has_columns_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('columns'));
    }

    /**
     * Test that command can be instantiated.
     */
    public function test_command_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ListCommand::class, $this->command);
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
     * Test that command accepts apps option.
     */
    public function test_command_accepts_apps_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--apps' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('apps'));
    }

    /**
     * Test that command accepts apps shortcut.
     */
    public function test_command_accepts_apps_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-a' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('apps'));
    }

    /**
     * Test that command accepts packages option.
     */
    public function test_command_accepts_packages_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--packages' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('packages'));
    }

    /**
     * Test that command accepts packages shortcut.
     */
    public function test_command_accepts_packages_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-p' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('packages'));
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
     * Test that command accepts compact option.
     */
    public function test_command_accepts_compact_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--compact' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('compact'));
    }

    /**
     * Test that command accepts compact shortcut.
     */
    public function test_command_accepts_compact_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-c' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('compact'));
    }

    /**
     * Test that command accepts absolute option.
     */
    public function test_command_accepts_absolute_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--absolute' => true], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('absolute'));
    }

    /**
     * Test that command accepts sort option with name.
     */
    public function test_command_accepts_sort_option_with_name(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--sort' => 'name'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('name', $this->command->option('sort'));
    }

    /**
     * Test that command accepts sort option with type.
     */
    public function test_command_accepts_sort_option_with_type(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--sort' => 'type'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('type', $this->command->option('sort'));
    }

    /**
     * Test that command accepts sort option with package.
     */
    public function test_command_accepts_sort_option_with_package(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--sort' => 'package'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('package', $this->command->option('sort'));
    }

    /**
     * Test that command accepts sort shortcut.
     */
    public function test_command_accepts_sort_shortcut(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['-s' => 'type'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('type', $this->command->option('sort'));
    }

    /**
     * Test that command accepts columns option.
     */
    public function test_command_accepts_columns_option(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput(['--columns' => 'name,type,path'], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('name,type,path', $this->command->option('columns'));
    }

    /**
     * Test that sort option defaults to name.
     */
    public function test_sort_option_defaults_to_name(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('name', $this->command->option('sort'));
    }

    /**
     * Test that columns option has default value.
     */
    public function test_columns_option_has_default_value(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertSame('name,type,package,version,composer,path', $this->command->option('columns'));
    }

    /**
     * Test that apps option defaults to false.
     */
    public function test_apps_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('apps'));
    }

    /**
     * Test that packages option defaults to false.
     */
    public function test_packages_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('packages'));
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
     * Test that compact option defaults to false.
     */
    public function test_compact_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('compact'));
    }

    /**
     * Test that absolute option defaults to false.
     */
    public function test_absolute_option_defaults_to_false(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertFalse($this->command->hasOption('absolute'));
    }

    /**
     * Test that command accepts apps and json together.
     */
    public function test_command_accepts_apps_and_json_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--apps' => true,
            '--json' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('apps'));
        $this->assertTrue($this->command->hasOption('json'));
    }

    /**
     * Test that command accepts packages and compact together.
     */
    public function test_command_accepts_packages_and_compact_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--packages' => true,
            '--compact' => true,
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('packages'));
        $this->assertTrue($this->command->hasOption('compact'));
    }

    /**
     * Test that command accepts multiple options.
     */
    public function test_command_accepts_multiple_options(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '--apps' => true,
            '--json' => true,
            '--sort' => 'type',
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('apps'));
        $this->assertTrue($this->command->hasOption('json'));
        $this->assertSame('type', $this->command->option('sort'));
    }

    /**
     * Test that command accepts all shortcuts together.
     */
    public function test_command_accepts_all_shortcuts_together(): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput([
            '-a' => true,
            '-j' => true,
            '-s' => 'name',
        ], $definition);

        $this->command->initialize($input, $this->output);

        $this->assertTrue($this->command->hasOption('apps'));
        $this->assertTrue($this->command->hasOption('json'));
        $this->assertSame('name', $this->command->option('sort'));
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
