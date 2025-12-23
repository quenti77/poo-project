<?php

return [
    'app.name' => env('APP_NAME', 'tuto-poo'),
    'app.env' => env('APP_ENV', 'local'),
    'app.debug' => (bool) env('APP_DEBUG', true),
    'app.url' => env('APP_URL', 'http://localhost'),

    'path.router' => ROOT . '/routes',
    'path.database' => ROOT . '/database',
];
