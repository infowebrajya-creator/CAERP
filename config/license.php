<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebRajya ERP Licensing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for hardware-locked licensing, offline cryptographic
    | signature verification, and AMC (Annual Maintenance Contract) management.
    |
    */

    // Master signature secret for verifying generated license keys
    'secret' => env('LICENSE_SIGNING_SECRET', env('APP_KEY', 'WebRajya-Secure-License-Signer-v1')),

    // Grace period (in days) if online verification or clock validation is needed
    'offline_grace_days' => 30,

    // Allow bypassing license checks in testing environment
    'bypass_in_testing' => env('LICENSE_BYPASS_TESTING', true),

    // Remote license verification server URL (optional, for online heartbeat)
    'server_url' => env('LICENSE_SERVER_URL', null),

    // Default max staff users allowed per standard license
    'default_max_staff' => 25,
];
