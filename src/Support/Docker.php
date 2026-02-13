<?php

declare(strict_types=1);

namespace PhpHive\Cli\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Docker Operations.
 *
 * Provides a clean abstraction for Docker operations with common patterns
 * for container management, image operations, and Docker Compose orchestration.
 * This class wraps Docker commands and provides convenient methods for
 * working with containers and services.
 *
 * All methods throw exceptions with descriptive messages on failure rather
 * than returning false, making error handling more explicit.
 *
 * Example usage:
 * ```php
 * $docker = Docker::make();
 * if ($docker->isInstalled()) {
 *     $docker->composeUp('/path/to/project');
 * }
 * ```
 */
final readonly class Docker
{
    /**
     * Create a new Docker instance.
     *
     * @param Process $process Process service for command execution
     */
    public function __construct(private Process $process) {}

    /**
     * Create a new Docker instance (static factory).
     *
     * Resolves Process dependency via its static factory method to avoid
     * circular dependency with the App container.
     *
     * @return self New Docker instance with Process dependency
     */
    public static function make(): self
    {
        return new self(Process::make());
    }

    /**
     * Check if Docker is installed and available.
     *
     * @return bool True if Docker is available, false otherwise
     */
    public function isInstalled(): bool
    {
        return $this->process->commandExists('docker');
    }

    /**
     * Check if Docker Compose is installed and available.
     *
     * Checks for both 'docker compose' (v2) and 'docker-compose' (v1).
     *
     * @return bool True if Docker Compose is available, false otherwise
     */
    public function isComposeInstalled(): bool
    {
        // Check for Docker Compose v2 (docker compose)
        if ($this->process->succeeds(['docker', 'compose', 'version'])) {
            return true;
        }

        // Check for Docker Compose v1 (docker-compose)
        return $this->process->commandExists('docker-compose');
    }

    /**
     * Check if Docker daemon is running.
     *
     * @return bool True if Docker daemon is running, false otherwise
     */
    public function isRunning(): bool
    {
        return $this->process->succeeds(['docker', 'info']);
    }

    /**
     * Get Docker version.
     *
     * @return string Docker version string
     *
     * @throws RuntimeException If Docker is not installed
     */
    public function getVersion(): string
    {
        $this->ensureDockerInstalled();

        $output = $this->process->run(['docker', '--version']);

        return Str::trim($output);
    }

    /**
     * Get Docker Compose version.
     *
     * @return string Docker Compose version string
     *
     * @throws RuntimeException If Docker Compose is not installed
     */
    public function getComposeVersion(): string
    {
        $this->ensureComposeInstalled();

        // Try Docker Compose v2 first
        if ($this->process->succeeds(['docker', 'compose', 'version'])) {
            $output = $this->process->run(['docker', 'compose', 'version']);
        } else {
            $output = $this->process->run(['docker-compose', '--version']);
        }

        return Str::trim($output);
    }

    /**
     * Start Docker Compose services.
     *
     * Runs 'docker compose up' to start services defined in docker-compose.yml.
     *
     * @param  string             $directory    Directory containing docker-compose.yml
     * @param  bool               $detached     Run in detached mode (default: true)
     * @param  bool               $build        Build images before starting (default: false)
     * @param  array<int, string> $services     Specific services to start (empty = all)
     * @param  array<int, string> $extraOptions Additional Docker Compose options
     * @return string             Command output
     *
     * @throws RuntimeException If Docker Compose is not installed or command fails
     */
    public function composeUp(
        string $directory,
        bool $detached = true,
        bool $build = false,
        array $services = [],
        array $extraOptions = []
    ): string {
        $this->ensureComposeInstalled();

        $command = $this->getComposeCommand();
        $command[] = 'up';

        if ($detached) {
            $command[] = '-d';
        }

        if ($build) {
            $command[] = '--build';
        }

        $command = [...$command, ...$extraOptions, ...$services];

        return $this->process->run($command, $directory);
    }

    /**
     * Stop Docker Compose services.
     *
     * Runs 'docker compose down' to stop and remove containers.
     *
     * @param  string             $directory    Directory containing docker-compose.yml
     * @param  bool               $volumes      Remove volumes (default: false)
     * @param  array<int, string> $extraOptions Additional Docker Compose options
     * @return string             Command output
     *
     * @throws RuntimeException If Docker Compose is not installed or command fails
     */
    public function composeDown(
        string $directory,
        bool $volumes = false,
        array $extraOptions = []
    ): string {
        $this->ensureComposeInstalled();

        $command = $this->getComposeCommand();
        $command[] = 'down';

        if ($volumes) {
            $command[] = '--volumes';
        }

        $command = [...$command, ...$extraOptions];

        return $this->process->run($command, $directory);
    }

    /**
     * Execute a command in a running container.
     *
     * Runs 'docker compose exec' to execute a command in a service container.
     *
     * @param  string             $directory Directory containing docker-compose.yml
     * @param  string             $service   Service name
     * @param  array<int, string> $command   Command to execute
     * @param  bool               $tty       Allocate a pseudo-TTY (default: false)
     * @return string             Command output
     *
     * @throws RuntimeException If Docker Compose is not installed or command fails
     */
    public function composeExec(
        string $directory,
        string $service,
        array $command,
        bool $tty = false
    ): string {
        $this->ensureComposeInstalled();

        $dockerCommand = $this->getComposeCommand();
        $dockerCommand[] = 'exec';

        if (! $tty) {
            $dockerCommand[] = '-T';
        }

        $dockerCommand[] = $service;
        $dockerCommand = [...$dockerCommand, ...$command];

        return $this->process->run($dockerCommand, $directory);
    }

    /**
     * Run a one-off command in a new container.
     *
     * Runs 'docker compose run' to execute a command in a new container.
     *
     * @param  string             $directory Directory containing docker-compose.yml
     * @param  string             $service   Service name
     * @param  array<int, string> $command   Command to execute
     * @param  bool               $rm        Remove container after run (default: true)
     * @return string             Command output
     *
     * @throws RuntimeException If Docker Compose is not installed or command fails
     */
    public function composeRun(
        string $directory,
        string $service,
        array $command,
        bool $rm = true
    ): string {
        $this->ensureComposeInstalled();

        $dockerCommand = $this->getComposeCommand();
        $dockerCommand[] = 'run';

        if ($rm) {
            $dockerCommand[] = '--rm';
        }

        $dockerCommand[] = $service;
        $dockerCommand = [...$dockerCommand, ...$command];

        return $this->process->run($dockerCommand, $directory);
    }

    /**
     * Build or rebuild Docker Compose services.
     *
     * Runs 'docker compose build' to build service images.
     *
     * @param  string             $directory    Directory containing docker-compose.yml
     * @param  array<int, string> $services     Specific services to build (empty = all)
     * @param  bool               $noCache      Build without cache (default: false)
     * @param  array<int, string> $extraOptions Additional Docker Compose options
     * @return string             Command output
     *
     * @throws RuntimeException If Docker Compose is not installed or command fails
     */
    public function composeBuild(
        string $directory,
        array $services = [],
        bool $noCache = false,
        array $extraOptions = []
    ): string {
        $this->ensureComposeInstalled();

        $command = $this->getComposeCommand();
        $command[] = 'build';

        if ($noCache) {
            $command[] = '--no-cache';
        }

        $command = [...$command, ...$extraOptions, ...$services];

        return $this->process->run($command, $directory);
    }

    /**
     * List running containers.
     *
     * Runs 'docker ps' to list running containers.
     *
     * @param  bool   $all Show all containers (default: false)
     * @return string Command output
     *
     * @throws RuntimeException If Docker is not installed or command fails
     */
    public function ps(bool $all = false): string
    {
        $this->ensureDockerInstalled();

        $command = ['docker', 'ps'];

        if ($all) {
            $command[] = '-a';
        }

        return $this->process->run($command);
    }

    /**
     * Run a custom Docker command.
     *
     * Executes an arbitrary Docker command with the given arguments.
     *
     * @param  array<int, string> $arguments Command arguments (e.g., ['images', '-a'])
     * @param  string|null        $directory Directory to run command in
     * @return string             Command output
     *
     * @throws RuntimeException If Docker is not installed or command fails
     */
    public function run(array $arguments, ?string $directory = null): string
    {
        $this->ensureDockerInstalled();

        $command = ['docker', ...$arguments];

        return $this->process->run($command, $directory);
    }

    /**
     * Run a custom Docker Compose command.
     *
     * Executes an arbitrary Docker Compose command with the given arguments.
     *
     * @param  string             $directory Directory containing docker-compose.yml
     * @param  array<int, string> $arguments Command arguments (e.g., ['logs', '-f'])
     * @return string             Command output
     *
     * @throws RuntimeException If Docker Compose is not installed or command fails
     */
    public function composeCommand(string $directory, array $arguments): string
    {
        $this->ensureComposeInstalled();

        $command = [...$this->getComposeCommand(), ...$arguments];

        return $this->process->run($command, $directory);
    }

    /**
     * Get the appropriate Docker Compose command.
     *
     * Returns ['docker', 'compose'] for v2 or ['docker-compose'] for v1.
     *
     * @return array<int, string> Docker Compose command array
     */
    private function getComposeCommand(): array
    {
        // Prefer Docker Compose v2 (docker compose)
        if ($this->process->succeeds(['docker', 'compose', 'version'])) {
            return ['docker', 'compose'];
        }

        // Fall back to Docker Compose v1 (docker-compose)
        return ['docker-compose'];
    }

    /**
     * Ensure Docker is installed.
     *
     * @throws RuntimeException If Docker is not installed
     */
    private function ensureDockerInstalled(): void
    {
        if (! $this->isInstalled()) {
            throw new RuntimeException(
                'Docker is not installed. Please install Docker from https://www.docker.com/'
            );
        }
    }

    /**
     * Ensure Docker Compose is installed.
     *
     * @throws RuntimeException If Docker Compose is not installed
     */
    private function ensureComposeInstalled(): void
    {
        if (! $this->isComposeInstalled()) {
            throw new RuntimeException(
                'Docker Compose is not installed. Please install Docker Compose from https://docs.docker.com/compose/install/'
            );
        }
    }
}
