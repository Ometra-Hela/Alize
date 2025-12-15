# Monitoring

## Logging
The package uses two primary methods for logging:

1. **Standard Laravel Logs**:
   - Errors and exceptions are logged to the default Laravel log channel (`stack`, `daily`, etc.).
   - Connection failures (SOAP/SFTP) are logged here.

2. **Database Logging (`PortabilityLog`)**:
   - Every major state transition and message exchange is recorded in the `alize_portabilities_log` table (or configured table name).
   - **Key Fields**:
     - `description`: Details of the action.
     - `executor_type` / `executor_id`: Who performed the action.
     - `created_at`: Time of occurrence.

## Critical Metrics to Monitor

### 1. Scheduler Health
Ensure the scheduled jobs are running:
- `CheckPortabilityTimers` (Every minute): Critical for enforcing deadlines (T1/T3/T4).
- `numlex:reconcile` (Daily 23:00): Critical for keeping database in sync with ABD.

### 2. SOAP Connectivity
Failures in `numlex:check-connection` indicate issues with:
- VPN/Network tunnel to NUMLEX.
- SSL Certificate validity.
- NUMLEX Endpoint availability.

### 3. Queue Health
If using asynchronous processing (recommended):
- Monitor the `default` queue (or configured queue) for failed jobs.
- Failed `PortabilityFaultNotification` jobs need attention.

## Alerts
Recommended alerts for the host application:

- **High Error Rate** on `SoapController`: Indicates issues receiving callbacks from NUMLEX.
- **Certificate Expiry**: Monitor the `ALIZE_TLS_CERT_PATH` file expiration date.
- **SFTP Sync Failure**: Zero "Processed" files in daily reconciliation for > 24 hours.
