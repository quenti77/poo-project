<?php

return [
    'title' => 'Error - {type}',
    'main_title' => 'An error has occurred',
    'http_code' => 'HTTP Code: {code} - {label}',
    'show_details' => '🔍 Show technical details',
    'hide_details' => '🔼 Hide technical details',
    'details' => [
        'location' => '📍 Location',
        'file' => 'File:',
        'line' => 'Line:',
        'code_number' => 'Error code:',
        'trace' => "📚 Execution trace",
    ],
    'production' => [
        'title' => 'Oops! Something went wrong',
        'lines' => [
            'An unexpected error occurred while processing your request.',
            'Our team has been notified and is working to resolve the issue.',
            'Please try again in a few moments.',
            'If the problem persists, please contact technical support',
        ]
    ],
    'development' => '<strong>Development mode</strong> - Full details displayed'
];
