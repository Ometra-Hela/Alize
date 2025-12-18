# Artisan Commands

The package provides several console commands for diagnostics, maintenance, and testing.

> **Note**: These commands are registered automatically when the package is installed.

## Operational Commands

### 1. Check Connection
Diagnostics tool to verify connectivity to NUMLEX.

- **Signature**: `numlex:check-connection`
- **Purpose**: Checks TCP connectivity, optional SSL certificates, and SOAP client initialization.
- **Usage**:
  ```bash
  php artisan numlex:check-connection
  ```
- **Exit Codes**: `0` (Success), `1` (Failure)

### 2. Reconcile Daily Files
Manually triggers the daily file download and reconciliation process from NUMLEX SFTP.

- **Signature**: `numlex:reconcile {date?}`
- **Arguments**:
  - `date` (optional): Date to process in `YYYYMMDD` format. Defaults to yesterday.
- **Usage**:
  ```bash
  # Reconcile yesterday's files
  php artisan numlex:reconcile

  # Reconcile specific date
  php artisan numlex:reconcile 20250101
  ```
- **Scheduling**: This command should be scheduled to run daily (default: `23:00` in service provider).

---

## Testing Tools

### 3. Test Full Flow
Interactive tool to simulate various portability scenarios (NIP, Reversion, Cancellation) and inject mock inbound messages.

- **Signature**: `numlex:test-full-flow`
- **Purpose**: Development and QA testing without needing real triggers.
- **Interactive Menu**:
  1. Request NIP (2001)
  2. Initiate Portability (1001)
  3. Cancel Portability (3001)
  4. Request Reversion (4001)
  5. Simulate Inbound Message (Mock SOAP 2002/1005/etc.)

### 4. Test Initiate
Interactive wizard to start a real Portability Request (1001).

- **Signature**: `numlex:test-initiate`
- **Purpose**: Quick way to fire a 1001 message with valid structure.
