# Deployment Instructions

## System Requirements

- **PHP**: ^8.1
- **Extensions**: `soap`, `dom`, `openssl`, `ssh2` (for SFTP)
- **Framework**: Laravel ^10.0 | ^11.0 | ^12.0
- **Database**: Compatible with Laravel Eloquent (MySQL/PostgreSQL recommended)
- **Dependencies**: `equidna/laravel-toolkit` ^1.0.3

## Installation

1. **Require via Composer**:

   ```bash
   composer require ometra/hela-alize
   ```

2. **Publish Configuration**:

   ```bash
   php artisan vendor:publish --tag=alize-config
   ```

3. **Run Migrations**:
   The package loads migrations automatically.
   ```bash
   php artisan migrate
   ```

## Environment Configuration

Add the following variables to your host application's `.env` file:

```dotenv
# Table Prefix (Default: alize_)
# ALIZE_TABLE_PREFIX=alize_

# Service Provider Identity
ALIZE_IDA_CODE="YOUR_IDA_CODE"

# NUMLEX SOAP Credentials
ALIZE_NUMLEX_USER_ID="YOUR_USER_ID"
ALIZE_NUMLEX_PASSWORD="YOUR_PASSWORD_BASE64"
ALIZE_NUMLEX_ENDPOINT="https://soap.portabilidad.mx/api/np/processmsg"

# TLS Certificates (Optional - for mutual TLS/custom CA)
# Leave blank to use system trust store
ALIZE_TLS_CERT_PATH=""
ALIZE_TLS_KEY_PATH=""
ALIZE_TLS_CA_PATH=""

# SFTP Configuration (For Daily Files)
ALIZE_SFTP_HOST="sftp.portabilidad.mx"
ALIZE_SFTP_USER="YOUR_SFTP_USER"
ALIZE_SFTP_KEY_PATH="/path/to/sftp_private.key"
ALIZE_SFTP_DAILY_PATH="/ftp/<IDA>/outbound/dailyfiles"

# Retry & Circuit Breaker (Recommended)
ALIZE_RETRY_DELAY_MS=1000
ALIZE_CB_FAILURE_THRESHOLD=5
ALIZE_CB_OPEN_SECONDS=60
ALIZE_CB_HALF_OPEN_SUCCESSES=1
```

## Scheduler Configuration

The package registers its own scheduled jobs in `HelaAlizeServiceProvider`:

- **Timer Check**: Every minute (`CheckPortabilityTimers`)
- **Daily Reconciliation**: Daily at 23:00 (`numlex:reconcile`)

Ensure the host application's scheduler is running:

```bash
* * * * * php /path-to-your-project/artisan schedule:run >> /dev/null 2>&1
```

## Verify Installation

Run the connection check command to validate credentials and connectivity:

```bash
php artisan numlex:check-connection
```
