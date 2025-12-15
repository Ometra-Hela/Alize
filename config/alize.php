<?php

/**
 * HELA Alize module configuration.
 *
 * Configuration for mobile number portability SOAP endpoint, SFTP synchronization,
 * business calendar settings, and attachment restrictions.
 * PHP 8.1+
 *
 * @package Config
 * @author  HELA Development Team
 * @license MIT
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be added to all database tables created by the package.
    |
    */
    'table_prefix' => 'alize_',

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for the package routes.
    |
    */
    'route_prefix' => 'alize',

    /*
    |--------------------------------------------------------------------------
    | Mobile Only Mode
    |--------------------------------------------------------------------------
    |
    | When true, only mobile portabilities are supported (MVNO Altán).
    | Fixed portability branching logic is disabled.
    |
    */
    'mobile_only' => true,

    /*
    |--------------------------------------------------------------------------
    | IDA Configuration
    |--------------------------------------------------------------------------
    |
    | Identification code for this service provider.
    |
    */
    'ida' => env('ALIZE_IDA_CODE', 'XXX'),

    /*
    |--------------------------------------------------------------------------
    | XSD Schemas
    |--------------------------------------------------------------------------
    |
    | Paths to NUMLEX XSD schema files for validation.
    |
    */
    'xsd_path' => env('ALIZE_XSD_PATH', storage_path('numlex/schemas')),

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | Default timezone for portability operations in Mexico.
    |
    */
    'timezone' => 'America/Mexico_City',

    /*
    |--------------------------------------------------------------------------
    | SOAP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for NUMLEX SOAP endpoint handling.
    |
    */
    'soap' => [
        'server_route' => '/soap/npws',
        'user_id' => env('ALIZE_NUMLEX_USER_ID', ''),
        'password_b64' => env('ALIZE_NUMLEX_PASSWORD', ''),
        'client_endpoint' => env('ALIZE_NUMLEX_ENDPOINT', 'https://soap.portabilidad.mx/api/np/processmsg'),

        'tls' => [
            'cert_path' => env('ALIZE_TLS_CERT_PATH', ''),
            'key_path' => env('ALIZE_TLS_KEY_PATH', ''),
            'ca_path' => env('ALIZE_TLS_CA_PATH', ''),
        ],

        'timeout' => 30,
        'retries' => 3,
        'retry_delay_ms' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PDF attachments (Personas Morales only).
    | Maximum total size and allowed MIME types.
    |
    */
    'attachments' => [
        'max_total_bytes' => 4194304, // 4 MB
        'allowed_mime' => ['application/pdf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | SFTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for daily files synchronization from NUMLEX.
    |
    */
    'sftp' => [
        'host' => env('ALIZE_SFTP_HOST', ''),
        'port' => env('ALIZE_SFTP_PORT', 22),
        'user' => env('ALIZE_SFTP_USER', ''),
        'key_path' => env('ALIZE_SFTP_KEY_PATH', ''),
        'daily_path' => env('ALIZE_SFTP_DAILY_PATH', '/ftp/<IDO>/outbound/dailyfiles'),
        'window_start' => '22:05',
        'available_by' => '22:59',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Hours
    |--------------------------------------------------------------------------
    |
    | Working window for portability operations in Mexico timezone.
    |
    */
    'business_hours' => [
        'start' => '11:00',
        'end' => '17:00',
    ],

];
