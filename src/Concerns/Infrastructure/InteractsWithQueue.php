<?php

declare(strict_types=1);

namespace PhpHive\Cli\Concerns\Infrastructure;

use PhpHive\Cli\DTOs\Infrastructure\QueueConfig;
use PhpHive\Cli\Enums\QueueDriver;
use PhpHive\Cli\Services\Infrastructure\QueueSetupService;

/**
 * Queue Interaction Trait.
 *
 * This trait provides user interaction and prompting for queue system setup.
 * It focuses solely on collecting user input and delegates all business logic to
 * QueueSetupService for better separation of concerns and testability.
 *
 * Queue systems enable asynchronous job processing, allowing applications to:
 * - Defer time-consuming tasks (email sending, image processing, etc.)
 * - Handle background jobs without blocking user requests
 * - Scale horizontally by adding more workers
 * - Retry failed jobs automatically
 * - Prioritize and schedule jobs
 * - Improve application responsiveness
 *
 * Supported queue backends:
 * - Redis: Lightweight, fast, in-memory data store. Best for simple queues with
 *   moderate throughput. Minimal setup, good for development and small-scale production.
 *   Supports job priorities and delayed jobs.
 *
 * - RabbitMQ: Full-featured message broker with advanced routing, exchanges, and
 *   message acknowledgment. Best for complex workflows, high reliability requirements,
 *   and microservices architecture. Supports multiple protocols (AMQP, MQTT, STOMP).
 *
 * - Amazon SQS: Fully managed cloud queue service. Best for AWS-based applications
 *   requiring high scalability without infrastructure management. Supports standard
 *   and FIFO queues. Requires AWS credentials and internet connectivity.
 *
 * - Sync: No queue (synchronous execution). Jobs run immediately in the same process.
 *   Useful for development, testing, or when async processing is not needed.
 *
 * Architecture:
 * - This trait handles user prompts and input collection
 * - QueueSetupService handles Docker setup and container management
 * - QueueConfig DTO provides type-safe configuration
 * - Each backend has specific configuration requirements
 *
 * Docker-first approach workflow:
 * 1. User selects desired queue backend (Redis, RabbitMQ, SQS, or Sync)
 * 2. Check if Docker is available on the system
 * 3. If yes, offer Docker setup (recommended for isolation and consistency)
 * 4. Collect backend-specific configuration (ports, credentials, regions)
 * 5. Delegate to QueueSetupService for container setup and configuration
 * 6. If Docker fails or unavailable, fall back to local setup instructions
 * 7. Return configuration array for application environment
 *
 * Backend selection considerations:
 * - Redis: Choose when you already use Redis for caching, need simple queues,
 *   or want minimal infrastructure overhead
 * - RabbitMQ: Choose for complex routing, guaranteed delivery, or when building
 *   event-driven microservices
 * - SQS: Choose when running on AWS, need managed service, or require unlimited
 *   scalability without server management
 * - Sync: Choose for development/testing or when all operations can be synchronous
 *
 * Example usage:
 * ```php
 * use PhpHive\Cli\Concerns\Infrastructure\InteractsWithQueue;
 *
 * class MyAppType extends AbstractAppType
 * {
 *     use InteractsWithQueue;
 *
 *     public function collectConfiguration($input, $output): array
 *     {
 *         // Setup queue system for the application
 *         $queueConfig = $this->setupQueue('my-app', '/path/to/app');
 *
 *         return array_merge($config, $queueConfig);
 *     }
 * }
 * ```
 *
 * @see QueueSetupService For queue setup business logic
 * @see QueueConfig For type-safe configuration DTO
 * @see QueueDriver For available queue backend enums
 * @see InteractsWithDocker For Docker availability checks
 * @see InteractsWithPrompts For prompt helper methods
 */
trait InteractsWithQueue
{
    /**
     * Get the QueueSetupService instance.
     *
     * This abstract method must be implemented by the class using this trait
     * to provide access to the QueueSetupService for delegating setup operations.
     *
     * @return QueueSetupService The queue setup service instance
     */
    abstract protected function queueSetupService(): QueueSetupService;

    /**
     * Orchestrate queue setup with Docker-first approach.
     *
     * This is the main entry point for queue system setup. It prompts the user
     * to select a queue backend and orchestrates the setup process based on their
     * choice and system capabilities.
     *
     * Workflow:
     * 1. Present user with queue backend options (Redis, RabbitMQ, SQS, Sync)
     * 2. If user selects Sync or none, return minimal config (no queue)
     * 3. Based on selection, delegate to backend-specific setup method:
     *    - Redis: Check for existing Redis, offer Docker setup
     *    - RabbitMQ: Collect credentials, offer Docker setup
     *    - SQS: Collect AWS configuration (no Docker needed)
     * 4. Return configuration array for application environment
     *
     * Backend differences:
     * - Redis: Can reuse existing Redis instance from cache setup
     * - RabbitMQ: Always requires dedicated setup with credentials
     * - SQS: Cloud-based, only needs AWS credentials and region
     * - Sync: No setup needed, jobs run synchronously
     *
     * @param  string $appName Application name used for defaults (e.g., queue prefixes)
     * @param  string $appPath Absolute path to application directory for Docker Compose files
     * @return array  Queue configuration array with keys varying by backend:
     *                Redis: queue_driver, queue_connection
     *                RabbitMQ: queue_driver, host, port, user, password, vhost, using_docker
     *                SQS: queue_driver, region, prefix
     *                Sync: queue_driver only
     */
    protected function setupQueue(string $appName, string $appPath): array
    {
        // Prompt user to select their preferred queue backend
        // Options include Redis, RabbitMQ, SQS, and Sync (no queue)
        $queueDriver = $this->select(
            label: 'Queue backend',
            options: QueueDriver::choices(),
            default: QueueDriver::SYNC->value
        );

        // If user selected Sync or none, no queue setup is needed
        // Return minimal configuration for synchronous job execution
        if ($queueDriver === QueueDriver::SYNC->value || $queueDriver === 'none') {
            return ['queue_driver' => QueueDriver::SYNC->value];
        }

        // Delegate to backend-specific setup method based on user selection
        // Each method handles its own prompts, Docker setup, and configuration
        return match ($queueDriver) {
            QueueDriver::REDIS->value => $this->setupRedisQueue($appName, $appPath),
            QueueDriver::RABBITMQ->value => $this->setupRabbitMQQueue($appName, $appPath),
            QueueDriver::SQS->value => $this->setupSQSQueue($appName),
            default => ['queue_driver' => QueueDriver::SYNC->value],
        };
    }

    /**
     * Set up Redis as queue backend.
     *
     * Redis is a popular choice for queues due to its speed and simplicity.
     * This method checks if Redis is already configured (e.g., from cache setup)
     * and offers to reuse it, or sets up a new Redis instance.
     *
     * Workflow:
     * 1. Check if Redis is already configured in the application
     * 2. If yes, offer to reuse existing Redis connection for queues
     * 3. If no or user declines:
     *    - Check Docker availability
     *    - Prompt user to use Docker (recommended)
     *    - Create QueueConfig with Redis settings
     *    - Delegate to QueueSetupService for setup
     * 4. Return configuration array
     *
     * Redis queue features:
     * - Fast in-memory operations
     * - Supports job priorities
     * - Delayed job execution
     * - Job retry mechanisms
     * - Atomic operations for reliability
     *
     * When called:
     * - User selects Redis as queue backend in setupQueue()
     * - Application needs simple, fast queue without complex routing
     *
     * @param  string $appName Application name (unused but kept for interface consistency)
     * @param  string $appPath Absolute path to application directory for Docker Compose
     * @return array  Redis queue configuration array with keys:
     *                - queue_driver: 'redis'
     *                - queue_connection: Connection name (if reusing existing)
     *                - host: Redis server host (if new setup)
     *                - port: Redis server port (if new setup)
     *                - using_docker: Whether Docker is being used
     */
    protected function setupRedisQueue(string $appName, string $appPath): array
    {
        // Inform user about Redis queue setup
        $this->info('Setting up Redis for queue...');

        // Check if Redis is already configured (e.g., from cache setup)
        // This avoids duplicate Redis instances and simplifies configuration
        $useExisting = $this->confirm(
            label: 'Redis is already configured. Use it for queues too?',
            default: true
        );

        // If user wants to reuse existing Redis, return minimal config
        // The application will use the existing Redis connection for queues
        if ($useExisting) {
            return [
                'queue_driver' => QueueDriver::REDIS->value,
                'queue_connection' => 'default',  // Use default Redis connection
            ];
        }

        // User wants a separate Redis instance for queues
        // Determine if Docker should be used for isolation
        $usingDocker = false;
        if ($this->isDockerAvailable()) {
            $usingDocker = $this->confirm(
                label: 'Use Docker for Redis queue?',
                default: true
            );
        }

        // Create type-safe configuration object for Redis queue
        // Using standard Redis port 6379 and localhost
        $queueConfig = new QueueConfig(
            driver: QueueDriver::REDIS,
            host: 'localhost',
            port: 6379,
            usingDocker: $usingDocker
        );

        // Delegate to QueueSetupService for actual setup
        // Show spinner during setup process for better UX
        $result = $this->spin(
            callback: fn (): QueueConfig => $this->queueSetupService()->setup($queueConfig, $appPath),
            message: 'Setting up Redis queue...'
        );

        // Inform user of successful setup
        $this->info('✓ Redis queue setup complete!');

        // Convert QueueConfig DTO to array format for application configuration
        return $result->toArray();
    }

    /**
     * Set up RabbitMQ as queue backend.
     *
     * RabbitMQ is a robust message broker that provides advanced features like
     * message routing, exchanges, and guaranteed delivery. It's ideal for complex
     * workflows and microservices architectures.
     *
     * Workflow:
     * 1. Check Docker availability
     * 2. Prompt user to use Docker (recommended for easy setup)
     * 3. Collect RabbitMQ credentials:
     *    - Username (default: guest)
     *    - Password (secure input, required for Docker)
     *    - Virtual host (default: /, used for isolation)
     * 4. Create QueueConfig with RabbitMQ settings
     * 5. Delegate to QueueSetupService for setup
     * 6. Display management UI URL if using Docker
     * 7. Return configuration array
     *
     * RabbitMQ features:
     * - AMQP protocol support
     * - Message acknowledgment and persistence
     * - Flexible routing with exchanges
     * - Dead letter queues for failed messages
     * - Management UI for monitoring (port 15672)
     * - Clustering and high availability
     *
     * Virtual hosts (vhosts):
     * - Provide logical separation within RabbitMQ
     * - Allow multiple applications to share one RabbitMQ instance
     * - Each vhost has its own queues, exchanges, and permissions
     * - Default vhost is "/" (root)
     *
     * When called:
     * - User selects RabbitMQ as queue backend in setupQueue()
     * - Application needs advanced routing or guaranteed delivery
     * - Building microservices with event-driven architecture
     *
     * @param  string $appName Application name (unused but kept for interface consistency)
     * @param  string $appPath Absolute path to application directory for Docker Compose
     * @return array  RabbitMQ queue configuration array with keys:
     *                - queue_driver: 'rabbitmq'
     *                - host: RabbitMQ server host
     *                - port: RabbitMQ server port (5672 for AMQP)
     *                - user: Authentication username
     *                - password: Authentication password
     *                - vhost: Virtual host for isolation
     *                - using_docker: Whether Docker is being used
     */
    protected function setupRabbitMQQueue(string $appName, string $appPath): array
    {
        // Inform user about RabbitMQ setup
        $this->info('Setting up RabbitMQ...');

        // Determine if Docker should be used for RabbitMQ
        // Docker is recommended as it includes management UI and simplifies setup
        $usingDocker = false;
        if ($this->isDockerAvailable()) {
            $usingDocker = $this->confirm(
                label: 'Use Docker for RabbitMQ?',
                default: true
            );
        }

        // Prompt for RabbitMQ username
        // Default is 'guest' which is RabbitMQ's default user
        $user = $this->text(
            label: 'RabbitMQ username',
            default: 'guest',
            required: true
        );

        // Prompt for password with secure input (hidden characters)
        // For Docker, provide hint to use a secure password
        // For local, assume user has existing RabbitMQ with known password
        $password = $usingDocker
            ? $this->password(label: 'RabbitMQ password', required: true, hint: 'Enter a secure password')
            : $this->password(label: 'RabbitMQ password', required: true);

        // Prompt for virtual host (vhost)
        // Vhosts provide logical separation within RabbitMQ
        // Default is '/' which is the root vhost
        $vhost = $this->text(
            label: 'RabbitMQ virtual host',
            default: '/',
            required: true,
            hint: 'Virtual host for isolation'
        );

        // Create type-safe configuration object for RabbitMQ
        // Port 5672 is the standard AMQP port
        $queueConfig = new QueueConfig(
            driver: QueueDriver::RABBITMQ,
            host: 'localhost',
            port: 5672,
            user: $user,
            password: $password,
            vhost: $vhost,
            usingDocker: $usingDocker
        );

        // Delegate to QueueSetupService for actual setup
        // This handles Docker container creation or local configuration
        // Show spinner during setup process for better UX
        $result = $this->spin(
            callback: fn (): QueueConfig => $this->queueSetupService()->setup($queueConfig, $appPath),
            message: 'Setting up RabbitMQ...'
        );

        // Display different success messages based on setup type
        if ($usingDocker) {
            // Docker setup includes management UI
            $this->info('✓ RabbitMQ setup complete!');
            $this->info('Management UI: http://localhost:15672');
            $this->info("Login with username: {$user}");
        } else {
            // Local setup just confirms configuration
            $this->info('✓ RabbitMQ configuration complete!');
        }

        // Convert QueueConfig DTO to array format for application configuration
        return $result->toArray();
    }

    /**
     * Set up Amazon SQS as queue backend.
     *
     * Amazon SQS (Simple Queue Service) is a fully managed message queuing service
     * that eliminates the complexity of managing message-oriented middleware. It's
     * ideal for cloud-native applications running on AWS.
     *
     * Workflow:
     * 1. Display information about AWS credentials requirement
     * 2. Prompt for AWS region (e.g., us-east-1, eu-west-1)
     * 3. Prompt for queue prefix (optional, defaults to app name)
     * 4. Display instructions for configuring AWS credentials in .env
     * 5. Create QueueConfig with SQS settings
     * 6. Return configuration array (no Docker setup needed)
     *
     * SQS features:
     * - Fully managed (no servers to maintain)
     * - Unlimited scalability
     * - Standard queues (best-effort ordering, at-least-once delivery)
     * - FIFO queues (guaranteed ordering, exactly-once processing)
     * - Dead letter queues for failed messages
     * - Message retention up to 14 days
     * - Server-side encryption
     * - Integration with AWS services (Lambda, SNS, etc.)
     *
     * AWS credentials:
     * - Access Key ID: Identifies the AWS account
     * - Secret Access Key: Authenticates API requests
     * - Region: Geographic location of SQS queues
     * - These must be configured in .env file or AWS credentials file
     *
     * Queue prefix:
     * - Prepended to all queue names
     * - Helps organize queues by application or environment
     * - Example: "myapp-" results in queues like "myapp-default", "myapp-emails"
     *
     * When called:
     * - User selects SQS as queue backend in setupQueue()
     * - Application is deployed on AWS
     * - Need managed service without infrastructure maintenance
     * - Require unlimited scalability
     *
     * Differences from Redis/RabbitMQ:
     * - No Docker setup (cloud-based service)
     * - Requires AWS account and credentials
     * - Requires internet connectivity
     * - Pay-per-use pricing model
     * - Higher latency than local queues
     *
     * @param  string $appName Application name used as default queue prefix
     * @return array  SQS queue configuration array with keys:
     *                - queue_driver: 'sqs'
     *                - region: AWS region where queues are located
     *                - prefix: Queue name prefix for organization
     *                - using_docker: false (SQS is cloud-based)
     */
    protected function setupSQSQueue(string $appName): array
    {
        // Inform user about SQS setup
        $this->info('Setting up Amazon SQS...');

        // Display important note about AWS credentials requirement
        // SQS requires valid AWS credentials to function
        $this->note(
            'Amazon SQS requires AWS credentials. You can configure them in your .env file.',
            'AWS Configuration'
        );

        // Prompt for AWS region where SQS queues will be created
        // Region affects latency and data residency
        // Default to us-east-1 (most common AWS region)
        $region = $this->text(
            label: 'AWS Region',
            placeholder: 'us-east-1',
            default: 'us-east-1',
            required: true,
            hint: 'AWS region where your SQS queues are located'
        );

        // Prompt for queue prefix (optional)
        // Prefix helps organize queues and avoid naming conflicts
        // Default to application name for consistency
        $queuePrefix = $this->text(
            label: 'Queue prefix (optional)',
            placeholder: $appName,
            default: $appName,
            required: false,
            hint: 'Prefix for queue names'
        );

        // Display detailed instructions for configuring AWS credentials
        // These environment variables are required for SQS to work
        $this->note(
            "Configure AWS credentials in your .env file:\n\n" .
            "AWS_ACCESS_KEY_ID=your-access-key\n" .
            "AWS_SECRET_ACCESS_KEY=your-secret-key\n" .
            "AWS_DEFAULT_REGION={$region}\n" .
            "SQS_PREFIX={$queuePrefix}",
            'Environment Variables'
        );

        // Create type-safe configuration object for SQS
        // Note: No host/port needed as SQS is cloud-based
        // Docker is not applicable for cloud services
        $queueConfig = new QueueConfig(
            driver: QueueDriver::SQS,
            usingDocker: false,
            region: $region,
            prefix: $queuePrefix
        );

        // Convert QueueConfig DTO to array format for application configuration
        // No service setup needed as SQS is managed by AWS
        return $queueConfig->toArray();
    }
}
