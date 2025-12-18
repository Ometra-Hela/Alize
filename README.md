# README (Root of the Project)

## Project Overview
Alize is a Laravel package that provides the core building blocks to integrate with Mexico's number portability platform (NUMLEX/ABD). It includes:
- A SOAP HTTP endpoint to receive inbound NPC messages.
- A SOAP client for outbound messages with XSD validation and circuit breaker safeguards.
- SFTP-based daily files reconciliation.
- Orchestrators, jobs, and domain services for end-to-end portability flows.

Primary audience: internal dev teams and external integrators embedding this package into a host Laravel application.

## Project Type & Tech Summary
- Type: Laravel package (library)
- PHP: ^8.1 (tested with PHP 8.4 during development)
- Laravel: 10.x | 11.x | 12.x (via `illuminate/support`)
- Database: Uses the host application's default database connection (migrations included)
- Cache: Uses the host application's cache store (circuit breaker state)
- Queue: Uses the host application's queue driver
- External services:
  - NUMLEX SOAP endpoint (inbound and outbound)
  - NUMLEX SFTP daily files

## Quick Start (High-Level)
1. Install: `composer require ometra/hela-alize`
2. Publish config: `php artisan vendor:publish --tag=alize-config`
3. Run migrations: `php artisan migrate`
4. Configure environment: NUMLEX credentials, SOAP endpoint, optional TLS certs, SFTP settings (see Deployment Instructions)
5. Ensure scheduler and a queue worker are running
6. Verify: `php artisan numlex:check-connection` and hit the SOAP route to test inbound

## Documentation Index
- [Deployment Instructions](doc/deployment-instructions.md)
- [API Documentation](doc/api-documentation.md)
- [Routes Documentation](doc/routes-documentation.md)
- [Artisan Commands](doc/artisan-commands.md)
- [Tests Documentation](doc/tests-documentation.md)
- [Architecture Diagrams](doc/architecture-diagrams.md)
- [Monitoring](doc/monitoring.md)
- [Business Logic & Core Processes](doc/business-logic-and-core-processes.md)
- [Open Questions & Assumptions](doc/open-questions-and-assumptions.md)

## Standards
This documentation follows the project's Coding Standards and PHPDoc Style Guide.
