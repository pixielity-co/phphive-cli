<?php

declare(strict_types=1);

namespace PhpHive\Cli\AppTypes\Symfony\Concerns;

use PhpHive\Cli\Contracts\AppTypeInterface;
use PhpHive\Cli\Enums\SymfonyProjectType;

/**
 * Collects Symfony project type configuration.
 *
 * This trait prompts the user to select the type of Symfony project they want
 * to create. The project type determines which packages and features are installed.
 *
 * Project types:
 *
 * 1. Web Application (Full-featured):
 *    - Installs symfony/webapp-pack
 *    - Includes Twig templating engine
 *    - Includes form component
 *    - Includes security component
 *    - Includes asset management
 *    - Includes translation
 *    - Best for traditional web applications with server-side rendering
 *
 * 2. Microservice/API (Minimal):
 *    - Uses symfony/skeleton only
 *    - Minimal dependencies
 *    - No Twig, forms, or frontend features
 *    - Best for REST APIs, microservices, and backend services
 *    - Smaller footprint and faster performance
 *
 * The selection affects:
 * - Which packages are installed during post-installation
 * - Available features and components
 * - Application size and performance
 * - Development workflow
 *
 * Most traditional web applications should choose "webapp", while APIs and
 * microservices should choose "skeleton".
 */
trait CollectsProjectTypeConfiguration
{
    /**
     * Collect project type selection.
     *
     * Prompts the user to choose between a full-featured web application
     * or a minimal microservice/API setup. Defaults to "webapp" as it's
     * the most common use case for Symfony.
     *
     * The selection determines whether symfony/webapp-pack is installed
     * during post-installation commands.
     *
     * @return array<string, mixed> Configuration array with CONFIG_PROJECT_TYPE key
     */
    protected function collectProjectTypeConfig(): array
    {
        return [
            // Project type selection - determines which packages to install
            // 'webapp' = full-featured with Twig, forms, security, etc.
            // 'skeleton' = minimal for APIs and microservices
            AppTypeInterface::CONFIG_PROJECT_TYPE => $this->select(
                label: 'Project type',
                options: SymfonyProjectType::choices(),
                default: SymfonyProjectType::default()->value
            ),
        ];
    }
}
