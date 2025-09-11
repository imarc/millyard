# Mill Yard

[![Tests](https://github.com/imarc/millyard/actions/workflows/tests.yml/badge.svg)](https://github.com/imarc/millyard/actions/workflows/tests.yml)
[![Code Quality](https://github.com/imarc/millyard/actions/workflows/code-quality.yml/badge.svg)](https://github.com/imarc/millyard/actions/workflows/code-quality.yml)

Millyard is the foundational engine behind [Mill 4, our modern WordPress starter theme](https://github.com/imarc/mill-4). While Mill 4 provides developers with a clean, opinionated starting point for building sites, Millyard operates behind the scenes as its core dependency, supplying the essential base classes, scaffolding tools, and shared services that power the theme's structure and capabilities. It's designed to bring consistency, flexibility, and maintainability to every project built with Mill 4. Together, they're provisioned and deployed via a custom Pantheon upstream, ensuring that all new projects begin with a robust and unified development environment from the first commit.

## Testing

This project includes a comprehensive test suite.

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only integration tests
composer test:integration

# Generate code coverage report (requires Xdebug or PCOV)
composer test:coverage

# View coverage as text
composer test:coverage-text
```

## Code Quality

```bash
# Check code style (dry run)
composer cs-check

# Fix code style issues
composer cs-fix
```
