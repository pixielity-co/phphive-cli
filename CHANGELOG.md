# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-02-11

### Added
- Initial release of Mono CLI
- 24 commands across 9 categories
- Turborepo integration for parallel task execution
- Composer integration for dependency management
- Quality tools integration (PHPUnit, PHPStan, Pint, Rector, Infection)
- Workspace management commands
- Scaffolding commands for packages and apps
- Development and build commands
- Deployment pipeline commands
- System health check command
- Comprehensive test suite (58 tests)
- Production-ready tooling configuration
- Automatic command discovery
- Dependency injection container
- Laravel Prompts integration for interactive CLI
- Beautiful ASCII art banner
- Comprehensive documentation

### Commands
#### Composer Integration
- `composer` - Direct Composer access
- `require` - Add package dependency
- `update` - Update dependencies

#### Deploy
- `deploy` - Run deployment pipeline
- `publish` - Publish packages

#### Development
- `dev` - Start development server
- `build` - Build for production

#### Lifecycle
- `install` - Install all dependencies
- `clean` - Clean caches
- `cleanup` - Deep clean

#### Make
- `create:app` - Create new application
- `create:package` - Create new package

#### Quality
- `test` - Run PHPUnit tests
- `lint` - Check code style
- `format` - Fix code style
- `typecheck` - Run static analysis
- `refactor` - Run Rector refactoring
- `mutate` - Run mutation testing

#### Turborepo
- `turbo` - Direct Turbo command access
- `run` - Run arbitrary Turbo task

#### Workspace
- `list-workspaces` - List all workspaces
- `info` - Show workspace details

#### Utility
- `doctor` - System health check
- `version` - Show version information

### Configuration
- PHPUnit with build directory support
- PHPStan level 5 with strict rules
- Laravel Pint with Laravel preset
- Rector for PHP 8.3
- Infection for mutation testing
- EditorConfig for consistent coding style

### Documentation
- Comprehensive README
- Command structure documentation
- Architecture documentation
- Contributing guidelines
- MIT License

## [Unreleased]

### Planned
- Command implementations (currently stubs)
- Feature tests for end-to-end testing
- Remote caching support
- Plugin system
- Interactive mode enhancements
- CI/CD templates
- Docker support
- Performance monitoring
- Error reporting integration

---

[1.0.0]: https://github.com/mono-php/cli/releases/tag/v1.0.0
