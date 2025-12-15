# Tests Documentation

## Overview
The package uses **PHPUnit** for testing. The test suite is located in the `tests/` directory.

## Structure
```
tests/
└── Unit/           # Unit tests for individual classes (Parsers, Builders, Models)
```

> **Note**: Feature tests simulating the full SOAP flow are currently pending integration or handled via manual testing tools (`numlex:test-full-flow`).

## Running Tests
To run the tests from the host application (if configured) or within the package development environment:

```bash
# Using Composer script
composer test

# Or directly via PHPUnit
vendor/bin/phpunit
```

## Coverage
Current testing focuses on:
- XML Builders (Validation of NUMLEX schema compliance)
- Response Parsers
- Model logic

## Adding Tests
When complying with the Coding Standards:
1. Place unit tests in `tests/Unit`.
2. Use the `Ometra\HelaAlize\Tests` namespace.
3. Extend `Ometra\HelaAlize\Tests\TestCase`.
