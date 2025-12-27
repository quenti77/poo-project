<?php

return [
    // Path to the maintenance file (enable when the file exist)
    'maintenance.file' => env('MAINTENANCE_FILE', 'storage/framework/down.json'),

    // Cookie to save bypass
    'maintenance.cookie_name' => env('MAINTENANCE_COOKIE_NAME', 'maintenance_bypass'),

     // Cookie time
    'maintenance.cookie_duration' => (int) env('MAINTENANCE_COOKIE_DURATION', 2 * 3600),

    // Used with query string "maintenance_pass"
    'maintenance.secret' => env('MAINTENANCE_SECRET', 'change_me'),

    // Add header Retry-After
    'maintenance.retry' => (int) env('MAINTENANCE_RETRY', 3600),

    // IP can be access without pass
    'maintenance.allowed_ips' => [
         '127.0.0.1',
         '::1',
    ],
];
