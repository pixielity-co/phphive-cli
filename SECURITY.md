# Security Policy

## Supported Versions

We release patches for security vulnerabilities in the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take the security of Mono CLI seriously. If you discover a security vulnerability, please follow these steps:

### 1. Do Not Disclose Publicly

Please do not open a public issue or discuss the vulnerability in public forums, pull requests, or social media.

### 2. Report Privately

Send a detailed report to the maintainers via:

- **Email**: security@mono-php.dev (if available)
- **GitHub Security Advisory**: Use the "Security" tab in the repository to create a private security advisory

### 3. Include Details

Your report should include:

- Description of the vulnerability
- Steps to reproduce the issue
- Potential impact and severity
- Suggested fix (if you have one)
- Your contact information

### Example Report

```
Subject: [SECURITY] Command Injection in Composer Integration

Description:
The ComposerCommand does not properly sanitize user input before passing
it to shell commands, allowing potential command injection.

Steps to Reproduce:
1. Run: mono composer require "package; malicious-command"
2. The malicious command is executed

Impact:
Arbitrary command execution with the privileges of the user running the CLI

Suggested Fix:
Use Symfony Process component's argument escaping or validate input against
a whitelist of allowed characters.

Reporter: John Doe (john@example.com)
```

## Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Timeline**: Depends on severity
  - Critical: Within 7 days
  - High: Within 14 days
  - Medium: Within 30 days
  - Low: Next regular release

## Security Update Process

1. **Acknowledgment**: We'll acknowledge receipt of your report
2. **Investigation**: We'll investigate and validate the vulnerability
3. **Fix Development**: We'll develop and test a fix
4. **Disclosure**: We'll coordinate disclosure with you
5. **Release**: We'll release a security patch
6. **Credit**: We'll credit you in the release notes (unless you prefer to remain anonymous)

## Security Best Practices

When using Mono CLI:

### 1. Keep Updated

Always use the latest version:

```bash
composer update mono-php/cli
```

### 2. Validate Input

When extending or integrating with Mono CLI, always validate and sanitize user input.

### 3. Least Privilege

Run CLI commands with the minimum necessary privileges. Avoid running as root unless absolutely necessary.

### 4. Review Dependencies

Regularly audit dependencies for known vulnerabilities:

```bash
composer audit
```

### 5. Secure Configuration

- Don't commit sensitive data (API keys, passwords) to version control
- Use environment variables for sensitive configuration
- Restrict file permissions on configuration files

### 6. Command Execution

Be cautious when using commands that execute shell commands:

- `mono composer` - Executes Composer commands
- `mono turbo` - Executes Turborepo commands
- `mono run` - Executes arbitrary tasks

Always validate input and avoid passing untrusted data to these commands.

## Known Security Considerations

### Command Injection

Commands that execute shell processes use Symfony Process component with proper argument escaping. However, always validate input when using:

- Custom scripts in turbo.json
- Composer scripts
- Shell commands in hooks

### File System Access

The CLI has access to the file system with the user's privileges. Be cautious with:

- `mono clean` - Deletes cache directories
- `mono cleanup` - Performs destructive cleanup
- `mono create:*` - Creates files and directories

### Dependency Vulnerabilities

We regularly update dependencies to address security vulnerabilities. Run `composer audit` to check for known vulnerabilities in dependencies.

## Security Features

### Input Validation

- Command arguments are validated
- File paths are sanitized
- Workspace names are validated against allowed patterns

### Safe Defaults

- Destructive operations require confirmation
- `--force` flag required for potentially dangerous operations
- `--no-interaction` mode available for CI/CD

### Process Isolation

- External commands run in isolated processes
- Environment variables are controlled
- Working directory is explicitly set

## Disclosure Policy

When a security vulnerability is fixed:

1. We'll release a patch version
2. We'll publish a security advisory
3. We'll update CHANGELOG.md with security notice
4. We'll credit the reporter (unless anonymous)

## Security Hall of Fame

We appreciate security researchers who help keep Mono CLI secure. Contributors will be listed here:

- (No vulnerabilities reported yet)

## Questions?

For security-related questions that are not vulnerabilities, you can:

- Open a discussion in the repository
- Contact the maintainers
- Review the documentation

Thank you for helping keep Mono CLI secure!
