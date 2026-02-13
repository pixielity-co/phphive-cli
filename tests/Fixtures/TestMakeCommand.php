<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Fixtures;

use PhpHive\Cli\Console\Commands\Make\BaseMakeCommand;
use PhpHive\Cli\Support\PreflightResult;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test Make Command.
 *
 * A concrete implementation of BaseMakeCommand for testing purposes.
 * Exposes protected methods as public for unit testing.
 */
final class TestMakeCommand extends BaseMakeCommand
{
    /**
     * Test wrapper for registerSignalHandlers().
     */
    public function testRegisterSignalHandlers(): void
    {
        $this->registerSignalHandlers();
    }

    /**
     * Test wrapper for runPreflightChecks().
     */
    public function testRunPreflightChecks(bool $isQuiet, bool $isJson): PreflightResult
    {
        return $this->runPreflightChecks($isQuiet, $isJson);
    }

    /**
     * Test wrapper for displayPreflightErrors().
     */
    public function testDisplayPreflightErrors(PreflightResult $preflightResult, bool $isQuiet, bool $isJson): void
    {
        $this->displayPreflightErrors($preflightResult, $isQuiet, $isJson);
    }

    /**
     * Test wrapper for checkDirectoryExists().
     */
    public function testCheckDirectoryExists(string $name, string $path, string $type, bool $isQuiet, bool $isJson): bool
    {
        return $this->checkDirectoryExists($name, $path, $type, $isQuiet, $isJson);
    }

    /**
     * Test wrapper for displaySuccessMessage().
     */
    public function testDisplaySuccessMessage(
        string $type,
        string $name,
        string $path,
        float $duration,
        array $nextSteps,
        bool $isQuiet,
        bool $isJson,
        bool $isVerbose
    ): void {
        $this->displaySuccessMessage($type, $name, $path, $duration, $nextSteps, $isQuiet, $isJson, $isVerbose);
    }

    /**
     * Test wrapper for cleanupFailedWorkspace().
     */
    public function testCleanupFailedWorkspace(string $workspacePath, bool $isQuiet, bool $isJson): void
    {
        $this->cleanupFailedWorkspace($workspacePath, $isQuiet, $isJson);
    }

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this->setName('test:make');
    }

    /**
     * Execute the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }
}
