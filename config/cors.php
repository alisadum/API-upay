<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_origins' => [
        'http://127.0.0.1:8001', // FE pakai php artisan serve
        'http://localhost:8001',

        'http://127.0.0.1:5173', // FE pakai vite (npm run dev)
        'http://localhost:5173',
        "https://44cead3eb117.ngrok-free.app",

    ],

    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
