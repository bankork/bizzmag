<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business defaults
    |--------------------------------------------------------------------------
    |
    | The business timezone and currency are stored per business in the
    | database from Phase 1 onwards. These values are only the defaults used
    | when a business is created and by code that runs without a business.
    |
    */

    'default_timezone' => env('BUSINESS_DEFAULT_TIMEZONE', 'Africa/Lagos'),

    'currency' => 'NGN',

    /*
    |--------------------------------------------------------------------------
    | Frontend origin
    |--------------------------------------------------------------------------
    |
    | The full origin of the web app, for example https://app.example.com.
    | Used for CORS, Sanctum stateful domains and links in emails.
    |
    */

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    */

    'idempotency' => [
        'header' => 'Idempotency-Key',
        'retention_days' => (int) env('IDEMPOTENCY_RETENTION_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler and queue (cPanel friendly)
    |--------------------------------------------------------------------------
    |
    | On shared hosting there is no process supervisor, so the scheduler
    | (run by cron every minute) also starts a short-lived queue worker.
    | On a VPS with a supervised worker, set QUEUE_WORK_VIA_SCHEDULER=false.
    |
    */

    'scheduler' => [
        'heartbeat_cache_key' => 'bizzmag:scheduler:heartbeat',
        'heartbeat_max_age_minutes' => 5,
        'queue_work_via_scheduler' => (bool) env('QUEUE_WORK_VIA_SCHEDULER', true),
        'heartbeat_url' => env('HEARTBEAT_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform minimums (checked by `php artisan app:preflight`)
    |--------------------------------------------------------------------------
    */

    'minimums' => [
        'php' => '8.3.0',
        'mysql' => '8.0.16',
        'mariadb' => '10.6.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    */

    'version' => env('APP_VERSION', 'dev'),

];
