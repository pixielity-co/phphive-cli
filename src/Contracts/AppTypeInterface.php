<?php

declare(strict_types=1);

namespace PhpHive\Cli\Contracts;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for different application types.
 *
 * Each app type (Laravel, Symfony, Magento, etc.) implements this interface
 * to define its specific scaffolding behavior, questions, and installation commands.
 */
interface AppTypeInterface
{
    /**
     * Get the display name of the app type.
     */
    public function getName(): string;

    /**
     * Get a description of the app type.
     */
    public function getDescription(): string;

    /**
     * Ask all necessary questions and collect configuration.
     *
     * @return array<string, mixed> Configuration array
     */
    public function collectConfiguration(InputInterface $input, OutputInterface $output): array;

    /**
     * Get the installation command for this app type.
     *
     * @param array<string, mixed> $config Configuration from collectConfiguration
     */
    public function getInstallCommand(array $config): string;

    /**
     * Get commands to run before installation.
     *
     * @param  array<string, mixed> $config Configuration from collectConfiguration
     * @return array<string>        Array of commands to execute before installation
     */
    public function getPreInstallCommands(array $config): array;

    /**
     * Get additional setup commands to run after installation.
     *
     * @param  array<string, mixed> $config Configuration from collectConfiguration
     * @return array<string>        Array of commands to execute
     */
    public function getPostInstallCommands(array $config): array;

    /**
     * Get the stub directory path for this app type.
     */
    public function getStubPath(): string;

    /**
     * Get variables to replace in stub files.
     *
     * @param  array<string, mixed>  $config Configuration from collectConfiguration
     * @return array<string, string> Key-value pairs for stub replacement
     */
    public function getStubVariables(array $config): array;
}
