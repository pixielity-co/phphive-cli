# PhpHive CLI Enhancement Plan

## Overview
This document outlines improvements to enhance the UI/UX of all CLI commands with consistent flags, arguments, and common functionality.

## Current State Analysis

### BaseCommand Common Options
Currently provides:
- `-w, --workspace` - Target specific workspace
- `-f, --force` - Force operation by ignoring cache
- `--no-cache` - Disable Turbo cache
- `-n, --no-interaction` - Non-interactive mode

### Missing Common Options
1. **Verbosity Control**
   - `--silent` - Suppress all output except errors
   - `-q, --quiet` - Minimal output
   - `-v, --verbose` - Verbose output
   - `-vv` - Very verbose
   - `-vvv, --debug` - Debug mode

2. **Output Format**
   - `--json` - JSON output format
   - `--format=<format>` - Output format (json, yaml, table, plain)

3. **Filtering & Selection**
   - `--all` - Apply to all workspaces
   - `--filter=<pattern>` - Filter by pattern
   - `--exclude=<pattern>` - Exclude by pattern

4. **Performance**
   - `--parallel` - Enable parallel execution
   - `--concurrency=<n>` - Set concurrency level
   - `--timeout=<seconds>` - Set timeout

5. **Dry Run**
   - `--dry-run` - Show what would happen without executing

## Command-by-Command Analysis

### 1. Composer Commands

#### composer:install (InstallCommand)
**Current**: `-w, -f, --no-cache, -n`
**Add**:
- `--all` - Install in all workspaces
- `--parallel` - Parallel installation
- `--no-dev` - Skip dev dependencies
- `--optimize` - Optimize autoloader

#### composer:require (RequireCommand)
**Current**: `package` (arg), `-w, -d, -f, -n`
**Add**:
- `--all` - Require in all workspaces
- `--version=<constraint>` - Specific version constraint

#### composer:update (UpdateCommand)
**Current**: `package` (optional arg), `-w, -f, -n`
**Add**:
- `--all` - Update all workspaces
- `--dry-run` - Show what would be updated
- `--with-dependencies` - Update with dependencies

### 2. Quality Commands

#### quality:test (TestCommand)
**Current**: `-w, -u, --feature, -c, --filter, -f, -n`
**Add**:
- `--all` - Test all workspaces
- `--parallel` - Parallel testing
- `--stop-on-failure` - Stop on first failure
- `--group=<group>` - Run specific test group

#### quality:lint (LintCommand)
**Current**: `-w, --fix, -f, -n`
**Add**:
- `--all` - Lint all workspaces
- `--parallel` - Parallel linting
- `--format=<format>` - Output format

#### quality:format (FormatCommand)
**Current**: `-w, --check, -f, -n`
**Add**:
- `--all` - Format all workspaces
- `--parallel` - Parallel formatting
- `--diff` - Show diff without applying

#### quality:typecheck (TypecheckCommand)
**Current**: `-w, --level, -f, -n`
**Add**:
- `--all` - Typecheck all workspaces
- `--parallel` - Parallel typechecking
- `--baseline` - Generate/update baseline

#### quality:refactor (RefactorCommand)
**Current**: `-w, --dry-run, --clear-cache, -f, -n`
**Add**:
- `--all` - Refactor all workspaces
- `--parallel` - Parallel refactoring
- `--set=<set>` - Apply specific rule set

#### quality:mutate (MutateCommand)
**Current**: `-w, --min-msi, --min-covered-msi, --threads, --show-mutations, -f, -n`
**Add**:
- `--all` - Mutate all workspaces
- `--parallel` - Parallel mutation testing

### 3. Dev Commands

#### dev:start (DevCommand)
**Current**: `-w, --port, -f, -n`
**Add**:
- `--host=<host>` - Bind to specific host
- `--open` - Open browser automatically
- `--watch` - Watch for changes

#### dev:build (BuildCommand)
**Current**: `-w, -f, -n`
**Add**:
- `--all` - Build all workspaces
- `--parallel` - Parallel building
- `--production` - Production build
- `--watch` - Watch mode

### 4. Framework Commands

#### framework:artisan (ArtisanCommand)
**Current**: `command` (array arg), `-w, -f, -n`
**Add**:
- `--all` - Run in all Laravel apps
- `--parallel` - Parallel execution

#### framework:console (ConsoleCommand)
**Current**: `command` (array arg), `-w, -f, -n`
**Add**:
- `--all` - Run in all Symfony apps
- `--parallel` - Parallel execution

#### framework:magento (MagentoCommand)
**Current**: `command` (array arg), `-w, -f, -n`
**Add**:
- `--all` - Run in all Magento apps
- `--parallel` - Parallel execution

### 5. Make Commands

#### make:workspace (MakeWorkspaceCommand)
**Current**: `name` (optional arg), `-n`
**Add**:
- `--template=<url>` - Custom template URL
- `--branch=<branch>` - Template branch
- `--no-git` - Skip git initialization

#### make:app (CreateAppCommand)
**Current**: `name` (arg), `--type, -n`
**Add**:
- `--template=<template>` - Use custom template
- `--no-install` - Skip dependency installation

#### make:package (CreatePackageCommand)
**Current**: `name` (arg), `-n`
**Add**:
- `--template=<template>` - Use custom template
- `--no-install` - Skip dependency installation

### 6. Workspace Commands

#### workspace:list (ListCommand)
**Current**: `-a, -p, -j, -n`
**Add**:
- `--format=<format>` - Output format (json, table, plain)
- `--filter=<pattern>` - Filter by pattern

#### workspace:info (InfoCommand)
**Current**: `workspace` (optional arg), `-n`
**Add**:
- `--json` - JSON output
- `--format=<format>` - Output format

### 7. Turbo Commands

#### turbo:run (RunCommand)
**Current**: `task` (arg), `-w, --parallel, --continue, -f, -n`
**Add**:
- `--all` - Run in all workspaces
- `--concurrency=<n>` - Set concurrency level
- `--graph` - Show task graph

#### turbo:exec (TurboCommand)
**Current**: `command` (array arg), `--filter, --parallel, --continue, -f, -n`
**Add**:
- `--all` - Run in all workspaces
- `--concurrency=<n>` - Set concurrency level

### 8. Deploy Commands

#### deploy:run (DeployCommand)
**Current**: `-w, --skip-tests, -f, -n`
**Add**:
- `--all` - Deploy all workspaces
- `--parallel` - Parallel deployment
- `--environment=<env>` - Target environment

#### deploy:publish (PublishCommand)
**Current**: `-w, --tag, --dry-run, -f, -n`
**Add**:
- `--all` - Publish all packages
- `--parallel` - Parallel publishing
- `--registry=<url>` - Custom registry

### 9. Lifecycle Commands

#### clean:cache (CleanCommand)
**Current**: `-w, -f, -n`
**Add**:
- `--all` - Clean all workspaces
- `--parallel` - Parallel cleaning

#### clean:all (CleanupCommand)
**Current**: `-w, -f, -n`
**Add**:
- `--all` - Clean all workspaces
- `--parallel` - Parallel cleaning
- `--confirm` - Require confirmation

### 10. Utility Commands

#### system:doctor (DoctorCommand)
**Current**: `-n`
**Add**:
- `--json` - JSON output
- `--fix` - Auto-fix issues

#### system:version (VersionCommand)
**Current**: `-n`
**Add**:
- `--json` - JSON output
- `--check-updates` - Check for updates

## Implementation Plan

### Phase 1: BaseCommand Enhancements
1. Add common verbosity methods
2. Add output format helpers
3. Add workspace selection helpers
4. Add parallel execution helpers

### Phase 2: Command Updates
1. Update all commands to use new BaseCommand features
2. Add missing flags systematically
3. Ensure consistent behavior across commands

### Phase 3: Helper Methods
Add to BaseCommand:
```php
// Workspace selection
protected function selectWorkspace(string $prompt): string
protected function selectWorkspaces(string $prompt): array
protected function getAllWorkspaces(): array
protected function shouldRunOnAll(): bool

// Output formatting
protected function outputJson(array $data): void
protected function outputTable(array $headers, array $rows): void
protected function outputFormat(array $data, string $format): void

// Parallel execution
protected function runParallel(array $tasks): array
protected function getConcurrency(): int

// Confirmation
protected function confirmDestructive(string $message): bool
```

### Phase 4: Testing
1. Test all commands with new flags
2. Ensure backward compatibility
3. Update documentation

## Benefits

1. **Consistency**: All commands follow same patterns
2. **Discoverability**: Users can predict available options
3. **Flexibility**: More control over command behavior
4. **Performance**: Parallel execution where applicable
5. **Automation**: Better support for CI/CD with `--all`, `--json`, etc.

## Breaking Changes

None - all additions are backward compatible.

## Documentation Updates

1. Update command help text
2. Update README examples
3. Update GitBook documentation
4. Add examples for new flags
