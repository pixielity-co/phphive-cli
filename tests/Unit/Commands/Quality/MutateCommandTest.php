<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Quality;

use PhpHive\Cli\Console\Commands\Quality\MutateCommand;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Mutate Command Test.
 *
 * Tests for the MutateCommand that runs Infection mutation testing.
 * Verifies all command options, flags, MSI thresholds, and thread configuration.
 */
final class MutateCommandTest extends TestCase
{
    /**
     * The mutate command instance.
     */
    private MutateCommand $command;

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
     * Creates a mutate command instance with mock input/output and
     * injects a container instance.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mutate command
        $this->command = new MutateCommand();

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
        $this->assertSame('quality:mutate', $this->command->getName());
    }

    /**
     * Test that command has correct description.
     *
     * Verifies the command description is set properly.
     */
    public function test_command_has_correct_description(): void
    {
        $this->assertSame('Run Infection mutation testing', $this->command->getDescription());
    }

    /**
     * Test that command has correct aliases.
     *
     * Verifies the command can be invoked using aliases.
     */
    public function test_command_has_correct_aliases(): void
    {
        $aliases = $this->command->getAliases();

        $this->assertContains('mutate', $aliases);
        $this->assertContains('infection', $aliases);
        $this->assertContains('mutation', $aliases);
    }

    /**
     * Test that command has min-msi option.
     *
     * Verifies the --min-msi option is defined.
     */
    public function test_command_has_min_msi_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('min-msi'));
    }

    /**
     * Test that command has min-covered-msi option.
     *
     * Verifies the --min-covered-msi option is defined.
     */
    public function test_command_has_min_covered_msi_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('min-covered-msi'));
    }

    /**
     * Test that command has threads option.
     *
     * Verifies the --threads/-t option is defined.
     */
    public function test_command_has_threads_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('threads'));

        $option = $definition->getOption('threads');
        $this->assertSame('t', $option->getShortcut());
    }

    /**
     * Test that command has show-mutations option.
     *
     * Verifies the --show-mutations option is defined.
     */
    public function test_command_has_show_mutations_option(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('show-mutations'));
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
     * Test that min-msi option is VALUE_REQUIRED type.
     *
     * Verifies the --min-msi option requires a value.
     */
    public function test_min_msi_option_is_value_required(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('min-msi');

        $this->assertTrue($option->isValueRequired());
    }

    /**
     * Test that min-msi option has default value.
     *
     * Verifies the --min-msi option defaults to 80.
     */
    public function test_min_msi_option_has_default_value(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('min-msi');

        $this->assertSame('80', $option->getDefault());
    }

    /**
     * Test that min-covered-msi option is VALUE_REQUIRED type.
     *
     * Verifies the --min-covered-msi option requires a value.
     */
    public function test_min_covered_msi_option_is_value_required(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('min-covered-msi');

        $this->assertTrue($option->isValueRequired());
    }

    /**
     * Test that min-covered-msi option has default value.
     *
     * Verifies the --min-covered-msi option defaults to 85.
     */
    public function test_min_covered_msi_option_has_default_value(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('min-covered-msi');

        $this->assertSame('85', $option->getDefault());
    }

    /**
     * Test that threads option is VALUE_REQUIRED type.
     *
     * Verifies the --threads option requires a value.
     */
    public function test_threads_option_is_value_required(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('threads');

        $this->assertTrue($option->isValueRequired());
    }

    /**
     * Test that threads option has default value.
     *
     * Verifies the --threads option defaults to 4.
     */
    public function test_threads_option_has_default_value(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('threads');

        $this->assertSame('4', $option->getDefault());
    }

    /**
     * Test that show-mutations option is VALUE_NONE type.
     *
     * Verifies the --show-mutations option is a boolean flag.
     */
    public function test_show_mutations_option_is_value_none(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('show-mutations');

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
     * Test that min-msi option has correct description.
     *
     * Verifies the --min-msi option description mentions MSI threshold.
     */
    public function test_min_msi_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('min-msi');

        $this->assertStringContainsString('Mutation Score Indicator', $option->getDescription());
    }

    /**
     * Test that min-covered-msi option has correct description.
     *
     * Verifies the --min-covered-msi option description mentions covered code MSI.
     */
    public function test_min_covered_msi_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('min-covered-msi');

        $this->assertStringContainsString('Covered Code MSI', $option->getDescription());
    }

    /**
     * Test that threads option has correct description.
     *
     * Verifies the --threads option description mentions thread count.
     */
    public function test_threads_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('threads');

        $this->assertStringContainsString('threads', $option->getDescription());
    }

    /**
     * Test that show-mutations option has correct description.
     *
     * Verifies the --show-mutations option description is informative.
     */
    public function test_show_mutations_option_has_description(): void
    {
        $definition = $this->command->getDefinition();
        $option = $definition->getOption('show-mutations');

        $this->assertStringContainsString('Show all mutations', $option->getDescription());
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
        $this->assertStringContainsString('mutate', $help);
        $this->assertStringContainsString('Infection', $help);
    }
}
