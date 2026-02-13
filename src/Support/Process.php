<?php

declare(strict_types=1);

namespace PhpHive\Cli\Support;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Process Operations.
 *
 * Provides a clean abstraction over Symfony Process component with common
 * patterns for executing shell commands. This class makes process execution
 * more testable and provides consistent error handling.
 *
 * All methods throw exceptions with descriptive messages on failure rather
 * than returning false, making error handling more explicit.
 *
 * Example usage:
 * ```php
 * $process = Process::make();
 * $output = $process->run(['ls', '-la']);
 * ```
 */
final class Process
{
    /**
     * Default timeout in seconds (5 minutes).
     */
    public const int DEFAULT_TIMEOUT = 300;

    /**
     * Create a new Process instance (static factory).
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Check if TTY mode is supported on the current system.
     *
     * TTY (teletypewriter) mode enables interactive features like colors,
     * progress bars, and real-time output. This is typically available
     * when running in a terminal but not in CI/CD environments.
     *
     * @return bool True if TTY is supported, false otherwise
     */
    public static function isTtySupported(): bool
    {
        return SymfonyProcess::isTtySupported();
    }

    /**
     * Create a Process from a shell command line.
     *
     * This method creates a Process instance from a shell command string
     * instead of an array. Useful for simple commands or when you need
     * shell features like pipes, redirects, or environment variable expansion.
     *
     * Note: Using array format is generally preferred for security and
     * reliability, but this method is useful for compatibility with
     * existing shell commands.
     *
     * @param  string         $command The shell command line
     * @param  string|null    $cwd     Working directory (null = current directory)
     * @param  int|null       $timeout Timeout in seconds (null = default timeout)
     * @return SymfonyProcess The process instance
     */
    public static function fromShellCommandline(string $command, ?string $cwd = null, ?int $timeout = null): SymfonyProcess
    {
        $process = SymfonyProcess::fromShellCommandline($command, $cwd);
        $process->setTimeout($timeout ?? self::DEFAULT_TIMEOUT);

        return $process;
    }

    /**
     * Run a command and return its output.
     *
     * This method executes a command synchronously and returns the output
     * on success. If the command fails, it throws an exception with details.
     *
     * @param  array<int, string> $command Command and arguments as array
     * @param  string|null        $cwd     Working directory (null = current directory)
     * @param  int|null           $timeout Timeout in seconds (null = default timeout)
     * @return string             Command output (stdout)
     *
     * @throws RuntimeException If command fails
     */
    public function run(array $command, ?string $cwd = null, ?int $timeout = null): string
    {
        $process = $this->createProcess($command, $cwd, $timeout);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $processFailedException) {
            throw new RuntimeException(
                'Command failed: ' . implode(' ', $command) . "\n" . $processFailedException->getMessage(),
                $processFailedException->getCode(),
                $processFailedException
            );
        }

        return $process->getOutput();
    }

    /**
     * Run a command and return the process instance.
     *
     * This method executes a command and returns the Process instance,
     * allowing access to exit code, output, and error output separately.
     *
     * @param  array<int, string> $command Command and arguments as array
     * @param  string|null        $cwd     Working directory (null = current directory)
     * @param  int|null           $timeout Timeout in seconds (null = default timeout)
     * @return SymfonyProcess     The executed process instance
     */
    public function execute(array $command, ?string $cwd = null, ?int $timeout = null): SymfonyProcess
    {
        $process = $this->createProcess($command, $cwd, $timeout);
        $process->run();

        return $process;
    }

    /**
     * Run a command and check if it succeeds.
     *
     * This method executes a command and returns true if it succeeds
     * (exit code 0), false otherwise. Useful for checking command availability
     * or testing conditions.
     *
     * @param  array<int, string> $command Command and arguments as array
     * @param  string|null        $cwd     Working directory (null = current directory)
     * @param  int|null           $timeout Timeout in seconds (null = default timeout)
     * @return bool               True if command succeeds, false otherwise
     */
    public function succeeds(array $command, ?string $cwd = null, ?int $timeout = null): bool
    {
        $process = $this->createProcess($command, $cwd, $timeout);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Run a command with real-time output.
     *
     * This method executes a command and streams output in real-time to
     * stdout/stderr. Useful for long-running commands where you want to
     * see progress as it happens.
     *
     * @param  array<int, string> $command  Command and arguments as array
     * @param  string|null        $cwd      Working directory (null = current directory)
     * @param  int|null           $timeout  Timeout in seconds (null = default timeout)
     * @param  callable|null      $callback Optional callback for output handling
     * @return int                Exit code
     */
    public function runWithOutput(
        array $command,
        ?string $cwd = null,
        ?int $timeout = null,
        ?callable $callback = null
    ): int {
        $process = $this->createProcess($command, $cwd, $timeout);
        $process->setTty(SymfonyProcess::isTtySupported());

        $process->run($callback);

        return $process->getExitCode() ?? 1;
    }

    /**
     * Check if a command exists in the system PATH.
     *
     * This method checks if a command is available by attempting to locate
     * it using 'which' (Unix) or 'where' (Windows).
     *
     * @param  string $command Command name to check
     * @return bool   True if command exists, false otherwise
     */
    public function commandExists(string $command): bool
    {
        // Use 'which' on Unix-like systems, 'where' on Windows
        $checkCommand = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';

        return $this->succeeds([$checkCommand, $command]);
    }

    /**
     * Create a Symfony Process instance with common configuration.
     *
     * This method creates and configures a Process instance with sensible
     * defaults for timeout and TTY support.
     *
     * @param  array<int, string> $command Command and arguments as array
     * @param  string|null        $cwd     Working directory (null = current directory)
     * @param  int|null           $timeout Timeout in seconds (null = default timeout)
     * @return SymfonyProcess     Configured process instance
     */
    private function createProcess(array $command, ?string $cwd = null, ?int $timeout = null): SymfonyProcess
    {
        $process = new SymfonyProcess($command, $cwd);
        $process->setTimeout($timeout ?? self::DEFAULT_TIMEOUT);

        return $process;
    }
}
