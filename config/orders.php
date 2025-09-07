<?php

return [
    // Base URL of the remote admin (fallback to APP_URL)
    'base_url' => env('ORDERS_BASE_URL', env('APP_URL')),

    // Retry strategy for network/detail fetches
    'retry' => [
        'attempts' => env('ORDERS_RETRY_ATTEMPTS', 3),
        'base_delay_ms' => env('ORDERS_RETRY_BASE_DELAY_MS', 200),
    ],

    // Full import limits
    'full_import' => [
        'max_pages' => env('ORDERS_FULL_IMPORT_MAX_PAGES', 30),
    ],

    // Reconcile defaults
    'reconcile' => [
        'pages' => env('ORDERS_RECONCILE_PAGES', 5),
    ],

    // Developer/demo fallback: when true and credentials absent, commands will seed fake sample orders
    'fake_mode' => (bool)env('ORDERS_FAKE_MODE', false),
];
