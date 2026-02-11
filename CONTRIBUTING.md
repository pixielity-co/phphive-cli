# Contributing to Mono CLI

Thank you for considering contributing to Mono CLI! This document outlines the process and guidelines for contributing.

## Code of Conduct

Be respectful, inclusive, and professional in all interactions.

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/mono-php.git`
3. Create a feature branch: `git checkout -b feature/your-feature-name`
4. Install dependencies: `cd cli && composer install`

## Development Workflow

### Before Making Changes

1. Ensure all tests pass: `composer test`
2. Check code quality: `composer check`

### Making Changes

1. Write clean, well-documented code
2. Follow PSR-12 coding standards
3. Add comprehensive docblocks to all classes and methods
4. Write unit tests for new functionality
5. Update documentation as needed

### Code Style

We use Laravel Pint for code formatting:

```bash
# Check code style
composer lint

# Fix code style automatically
composer format
```

### Static Analysis

We use PHPStan at level 8 for static analysis:

```bash
# Run static analysis
composer typecheck
```

### Refactoring

We use Rector for automated refactoring:

```bash
# Preview refactoring changes
composer refactor:dry

# Apply refactoring
composer refactor
```

### Testing

Write comprehensive tests for all new features:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage
```

Test requirements:
- Unit tests for all new classes and methods
- Feature tests for command workflows
- Minimum 80% code coverage for new code
- All tests must pass before submitting PR

### Quality Checks

Run all quality checks before committing:

```bash
# Run all checks (test, lint, typecheck, refactor)
composer check

# Fix all auto-fixable issues
composer fix
```

## Commit Guidelines

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples

```
feat(commands): add workspace initialization command

Add new make:workspace command that creates a new monorepo workspace
with interactive prompts for name and type selection.

Closes #123
```

```
fix(composer): handle missing composer.json gracefully

Check if composer.json exists before attempting to parse it.
Display helpful error message when file is missing.
```

## Pull Request Process

1. Update documentation for any changed functionality
2. Add tests for new features
3. Ensure all quality checks pass: `composer check`
4. Update CHANGELOG.md with your changes
5. Submit PR with clear description of changes
6. Link related issues in PR description
7. Wait for code review and address feedback

### PR Title Format

Use the same format as commit messages:

```
feat(commands): add workspace initialization command
```

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] All tests passing
- [ ] Manual testing completed

## Checklist
- [ ] Code follows PSR-12 standards
- [ ] Docblocks added/updated
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] All quality checks pass
```

## Project Structure

```
cli/
├── bin/                  # Executable scripts
├── src/
│   ├── Application.php   # Main application class
│   ├── Commands/         # Command classes
│   │   ├── BaseCommand.php
│   │   ├── Composer/     # Composer integration
│   │   ├── Deploy/       # Deployment commands
│   │   ├── Dev/          # Development commands
│   │   ├── Lifecycle/    # Installation & cleanup
│   │   ├── Make/         # Scaffolding commands
│   │   ├── Quality/      # Testing & quality
│   │   ├── Turbo/        # Turborepo commands
│   │   ├── Utility/      # Utility commands
│   │   └── Workspace/    # Workspace management
│   ├── Concerns/         # Reusable traits
│   └── Support/          # Helper classes
├── tests/
│   ├── Unit/             # Unit tests
│   ├── Feature/          # Feature tests
│   ├── Fixtures/         # Test fixtures
│   └── TestCase.php      # Base test case
├── build/                # Build artifacts (gitignored)
└── vendor/               # Dependencies (gitignored)
```

## Adding New Commands

1. Create command class in appropriate directory under `src/Commands/`
2. Extend `BaseCommand` class
3. Implement `configure()` and `execute()` methods
4. Add comprehensive docblocks
5. Call `parent::configure()` to inherit common options
6. Register command in `Application.php` if not auto-discovered
7. Write unit tests in `tests/Unit/Commands/`
8. Write feature tests in `tests/Feature/`
9. Update documentation

### Command Template

```php
<?php

declare(strict_types=1);

namespace MonoPhp\Cli\Commands\Category;

use MonoPhp\Cli\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Brief description of what the command does.
 *
 * Detailed explanation of the command's purpose and behavior.
 *
 * @package MonoPhp\Cli\Commands\Category
 */
class YourCommand extends BaseCommand
{
    /**
     * Configure the command.
     *
     * Sets up command name, description, arguments, and options.
     * Inherits common options from BaseCommand (--workspace, --force, etc.).
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('your:command')
            ->setDescription('Brief description')
            ->setHelp('Detailed help text');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input  Command input
     * @param OutputInterface $output Command output
     *
     * @return int Exit code (0 for success, non-zero for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Implementation here

        return self::SUCCESS;
    }
}
```

## Documentation

- Update README.md for user-facing changes
- Update inline code documentation (docblocks)
- Add examples for new features
- Update CHANGELOG.md

## Questions?

- Open an issue for bugs or feature requests
- Start a discussion for questions or ideas
- Check existing issues before creating new ones

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
