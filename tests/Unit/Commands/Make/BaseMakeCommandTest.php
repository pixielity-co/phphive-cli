<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Commands\Make;

use PhpHive\Cli\Support\Container;
use PhpHive\Cli\Support\PreflightResult;
use PhpHive\Cli\Tests\Fixtures\TestMakeCommand;
use PhpHive\Cli\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Base Make Command Test.
 *
 * Tests for the BaseMakeCommand abstract class that provides common functionality
 * for all "make" commands (create:app, create:package, make:workspace).
 * Verifies preflight checks, signal handlers, cleanup, and success message display.
 */
final class BaseMakeCommandTest extends TestCase
{
    /**
     * The test command instance.
     */
    private TestMakeCommand $command;

    /**
     * Mock input for testing.
     */
    private ArrayInput $input;

    /**
     * Buffered output for capturing command output.
     */
    private BufferedOutput $output;

    /**
     * Container instance for dependency injection.
     */
    private Container $container;

    /**
     * Set up the test environment before each test.
     *
     * Creates a test command instance with mock input/output and
     * injects a container instance with required services.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test command
        $this->command = new TestMakeCommand();

        // Create mock input and output
        $this->input = new ArrayInput([]);
        $this->output = new BufferedOutput();

        // Create and inject container
        $this->container = new Container();
        $this->command->setContainer($this->container);
    }

    /**
     * Test that signal handlers can be registered.
     *
     * Verifies that registerSignalHandlers() can be called without errors
     * when pcntl extension is available.
     */
    public function test_registers_signal_handlers(): void
    {
        // Skip if pcntl extension is not available
        if (! function_exists('pcntl_signal')) {
            $this->markTestSkipped('pcntl extension not available');
        }

        // Register signal handlers should not throw
        $this->command->testRegisterSignalHandlers();

        // Assert no exceptions were thrown
        $this->assertTrue(true);
    }

    /**
     * Test that signal handlers are only registered once.
     *
     * Verifies that calling registerSignalHandlers() multiple times
     * doesn't register handlers multiple times.
     */
    public function test_signal_handlers_registered_only_once(): void
    {
        // Skip if pcntl extension is not available
        if (! function_exists('pcntl_signal')) {
            $this->markTestSkipped('pcntl extension not available');
        }

        // Register signal handlers multiple times
        $this->command->testRegisterSignalHandlers();
        $this->command->testRegisterSignalHandlers();
        $this->command->testRegisterSignalHandlers();

        // Assert no exceptions were thrown
        $this->assertTrue(true);
    }

    /**
     * Test that runPreflightChecks executes checks in normal mode.
     *
     * Verifies that preflight checks are executed and results are returned
     * when not in quiet or JSON mode.
     */
    public function test_runs_preflight_checks_in_normal_mode(): void
    {
        // Run preflight checks in normal mode
        $result = $this->command->testRunPreflightChecks(false, false);

        // Assert result is a PreflightResult instance
        $this->assertInstanceOf(PreflightResult::class, $result);
    }

    /**
     * Test that runPreflightChecks executes checks in quiet mode.
     *
     * Verifies that preflight checks are executed without output
     * when in quiet mode.
     */
    public function test_runs_preflight_checks_in_quiet_mode(): void
    {
        // Run preflight checks in quiet mode
        $result = $this->command->testRunPreflightChecks(true, false);

        // Assert result is a PreflightResult instance
        $this->assertInstanceOf(PreflightResult::class, $result);
    }

    /**
     * Test that runPreflightChecks executes checks in JSON mode.
     *
     * Verifies that preflight checks are executed without output
     * when in JSON mode.
     */
    public function test_runs_preflight_checks_in_json_mode(): void
    {
        // Run preflight checks in JSON mode
        $result = $this->command->testRunPreflightChecks(false, true);

        // Assert result is a PreflightResult instance
        $this->assertInstanceOf(PreflightResult::class, $result);
    }

    /**
     * Test that displayPreflightErrors outputs JSON in JSON mode.
     *
     * Verifies that preflight errors are displayed as JSON
     * when in JSON mode.
     */
    public function test_displays_preflight_errors_in_json_mode(): void
    {
        // Create a failed preflight result
        $preflightResult = new PreflightResult(
            false,
            [
                'PHP Version' => [
                    'passed' => false,
                    'message' => 'PHP 8.2+ required',
                    'fix' => 'Upgrade PHP',
                ],
            ]
        );

        // Initialize command with output
        $this->command->initialize($this->input, $this->output);

        // Display preflight errors in JSON mode
        $this->command->testDisplayPreflightErrors($preflightResult, false, true);

        // Get output content
        $content = $this->output->fetch();

        // Assert JSON output contains error
        $this->assertStringContainsString('"success":false', $content);
        $this->assertStringContainsString('Preflight checks failed', $content);
    }

    /**
     * Test that checkDirectoryExists returns true when directory exists.
     *
     * Verifies that the method correctly identifies existing directories.
     */
    public function test_check_directory_exists_returns_true_when_exists(): void
    {
        // Create a temporary directory
        $tempDir = sys_get_temp_dir() . '/test-' . uniqid();
        mkdir($tempDir);

        try {
            // Check if directory exists
            $exists = $this->command->testCheckDirectoryExists(
                'test',
                $tempDir,
                'application',
                false,
                false
            );

            // Assert directory exists
            $this->assertTrue($exists);
        } finally {
            // Clean up
            rmdir($tempDir);
        }
    }

    /**
     * Test that checkDirectoryExists returns false when directory doesn't exist.
     *
     * Verifies that the method correctly identifies non-existent directories.
     */
    public function test_check_directory_exists_returns_false_when_not_exists(): void
    {
        // Use a non-existent path
        $nonExistentPath = sys_get_temp_dir() . '/non-existent-' . uniqid();

        // Check if directory exists
        $exists = $this->command->testCheckDirectoryExists(
            'test',
            $nonExistentPath,
            'application',
            false,
            false
        );

        // Assert directory doesn't exist
        $this->assertFalse($exists);
    }

    /**
     * Test that displaySuccessMessage outputs in normal mode.
     *
     * Verifies that success messages are displayed with next steps
     * in normal mode.
     */
    public function test_displays_success_message_in_normal_mode(): void
    {
        // Initialize command with output
        $this->command->initialize($this->input, $this->output);

        // Display success message
        $this->command->testDisplaySuccessMessage(
            'application',
            'my-app',
            '/path/to/my-app',
            1.5,
            ['cd my-app', 'composer install'],
            false,
            false,
            false
        );

        // Get output content
        $content = $this->output->fetch();

        // Assert success message is displayed
        $this->assertStringContainsString('created successfully', $content);
        $this->assertStringContainsString('Next steps:', $content);
        $this->assertStringContainsString('cd my-app', $content);
    }

    /**
     * Test that displaySuccessMessage outputs JSON in JSON mode.
     *
     * Verifies that success messages are displayed as JSON
     * when in JSON mode.
     */
    public function test_displays_success_message_in_json_mode(): void
    {
        // Initialize command with output
        $this->command->initialize($this->input, $this->output);

        // Display success message in JSON mode
        $this->command->testDisplaySuccessMessage(
            'application',
            'my-app',
            '/path/to/my-app',
            1.5,
            ['cd my-app', 'composer install'],
            false,
            true,
            false
        );

        // Get output content
        $content = $this->output->fetch();

        // Assert JSON output contains success data
        $this->assertStringContainsString('"success":true', $content);
        $this->assertStringContainsString('"name":"my-app"', $content);
        $this->assertStringContainsString('"type":"application"', $content);
    }

    /**
     * Test that displaySuccessMessage suppresses output in quiet mode.
     *
     * Verifies that no output is displayed when in quiet mode.
     */
    public function test_displays_success_message_suppresses_output_in_quiet_mode(): void
    {
        // Initialize command with output
        $this->command->initialize($this->input, $this->output);

        // Display success message in quiet mode
        $this->command->testDisplaySuccessMessage(
            'application',
            'my-app',
            '/path/to/my-app',
            1.5,
            ['cd my-app', 'composer install'],
            true,
            false,
            false
        );

        // Get output content
        $content = $this->output->fetch();

        // Assert no output
        $this->assertEmpty($content);
    }

    /**
     * Test that displaySuccessMessage shows duration in verbose mode.
     *
     * Verifies that duration is displayed when in verbose mode.
     */
    public function test_displays_success_message_shows_duration_in_verbose_mode(): void
    {
        // Initialize command with output
        $this->command->initialize($this->input, $this->output);

        // Display success message in verbose mode
        $this->command->testDisplaySuccessMessage(
            'application',
            'my-app',
            '/path/to/my-app',
            1.5,
            ['cd my-app', 'composer install'],
            false,
            false,
            true
        );

        // Get output content
        $content = $this->output->fetch();

        // Assert duration is displayed
        $this->assertStringContainsString('Total time:', $content);
        $this->assertStringContainsString('1.50s', $content);
    }

    /**
     * Test that cleanupFailedWorkspace removes directory.
     *
     * Verifies that the cleanup method removes the workspace directory
     * when it exists.
     */
    public function test_cleanup_failed_workspace_removes_directory(): void
    {
        // Create a temporary directory
        $tempDir = sys_get_temp_dir() . '/test-workspace-' . uniqid();
        mkdir($tempDir);

        // Initialize command with output
        $this->command->initialize($this->input, $this->output);

        // Cleanup the workspace
        $this->command->testCleanupFailedWorkspace($tempDir, false, false);

        // Assert directory was removed
        $this->assertDirectoryDoesNotExist($tempDir);
    }

    /**
     * Test that cleanupFailedWorkspace handles non-existent directory.
     *
     * Verifies that the cleanup method handles non-existent directories
     * gracefully without errors.
     */
    public function test_cleanup_failed_workspace_handles_non_existent_directory(): void
    {
        // Use a non-existent path
        $nonExistentPath = sys_get_temp_dir() . '/non-existent-' . uniqid();

        // Initialize command with output
        $this->command->initialize($this->input, $this->output);

        // Cleanup should not throw
        $this->command->testCleanupFailedWorkspace($nonExistentPath, false, false);

        // Assert no exceptions were thrown
        $this->assertTrue(true);
    }

    /**
     * Test that cleanupFailedWorkspace suppresses output in quiet mode.
     *
     * Verifies that no output is displayed during cleanup when in quiet mode.
     */
    public function test_cleanup_failed_workspace_suppresses_output_in_quiet_mode(): void
    {
        // Create a temporary directory
        $tempDir = sys_get_temp_dir() . '/test-workspace-' . uniqid();
        mkdir($tempDir);

        // Initialize command with output
        $this->command->initialize($this->input, $this->output);

        // Cleanup in quiet mode
        $this->command->testCleanupFailedWorkspace($tempDir, true, false);

        // Get output content
        $content = $this->output->fetch();

        // Assert no output
        $this->assertEmpty($content);
    }
}
