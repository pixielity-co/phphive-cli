<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit;

use PhpHive\Cli\Application;
use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Unit tests for Application class.
 *
 * Tests the main CLI application functionality including:
 * - Application instantiation and configuration
 * - Command discovery and registration
 * - Boot lifecycle management
 * - Command finding with alternatives
 * - Version and name retrieval
 */
class ApplicationTest extends TestCase
{
    /**
     * Test application can be instantiated.
     */
    public function test_can_instantiate_application(): void
    {
        $app = new Application();

        $this->assertInstanceOf(Application::class, $app);
        $this->assertSame('PhpHive CLI', $app->getName());
    }

    /**
     * Test application has correct version.
     */
    public function test_application_has_correct_version(): void
    {
        $app = new Application();

        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $app->getVersion());
    }

    /**
     * Test application can be created using static factory.
     */
    public function test_can_create_application_using_static_factory(): void
    {
        $app = Application::make();

        $this->assertInstanceOf(Application::class, $app);
    }

    /**
     * Test application boot is idempotent.
     */
    public function test_boot_is_idempotent(): void
    {
        $app = new Application();

        // Boot multiple times
        $app->boot();
        $commandsBefore = $app->all();

        $app->boot();
        $commandsAfter = $app->all();

        // Should have same commands after multiple boots
        $this->assertSame(count($commandsBefore), count($commandsAfter));
    }

    /**
     * Test application has container.
     */
    public function test_application_has_container(): void
    {
        $app = new Application();

        $container = $app->container();

        $this->assertInstanceOf(Container::class, $container);
    }

    /**
     * Test application can resolve services from container.
     */
    public function test_can_resolve_services_from_container(): void
    {
        $app = new Application();

        $filesystem = $app->container()->make(Filesystem::class);

        $this->assertInstanceOf(Filesystem::class, $filesystem);
    }

    /**
     * Test application returns long version string.
     */
    public function test_get_long_version_returns_formatted_string(): void
    {
        $app = new Application();

        $longVersion = $app->getLongVersion();

        $this->assertStringContainsString('PhpHive CLI', $longVersion);
        $this->assertStringContainsString('version', $longVersion);
    }

    /**
     * Test find throws exception for unknown command.
     */
    public function test_find_throws_exception_for_unknown_command(): void
    {
        $app = new Application();
        $app->boot();

        $this->expectException(CommandNotFoundException::class);
        $app->find('nonexistent-command-xyz');
    }

    /**
     * Test application has default command set.
     */
    public function test_application_has_default_command(): void
    {
        $app = new Application();

        $defaultCommand = $app->getDefaultCommand();

        $this->assertSame('list', $defaultCommand);
    }

    /**
     * Test application discovers and registers commands.
     */
    public function test_application_discovers_commands(): void
    {
        $app = Application::make();

        $commands = $app->all();

        // Should have more than just the default commands (help, list, completion)
        $this->assertGreaterThan(3, count($commands));
    }

    /**
     * Test application can find registered commands.
     */
    public function test_can_find_registered_commands(): void
    {
        $app = Application::make();

        // Try to find the list command (always available)
        $command = $app->find('list');

        $this->assertInstanceOf(Command::class, $command);
    }

    /**
     * Test run method boots application automatically.
     */
    public function test_run_boots_application_automatically(): void
    {
        $app = new Application();
        $input = new ArrayInput(['command' => 'list']);
        $output = new BufferedOutput();

        // Run without explicit boot
        $exitCode = $app->run($input, $output);

        // Should succeed (exit code 0)
        $this->assertSame(0, $exitCode);
    }
}
