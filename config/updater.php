<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebRajya ERP Auto-Updater Configuration
    |--------------------------------------------------------------------------
    */

    'current_version' => '1.0.0',

    // Remote endpoint where version.json is hosted
    'manifest_url' => env(
        'UPDATE_MANIFEST_URL',
        'https://raw.githubusercontent.com/infowebrajya-creator/CAERP/main/version.json'
    ),

    // Enable automatic background checks
    'enable_checks' => env('UPDATE_CHECK_ENABLED', true),

    // Cache duration for update checks (in minutes)
    'cache_ttl_minutes' => 60,

    // Require valid AMC for receiving updates
    'require_valid_amc' => true,
];

