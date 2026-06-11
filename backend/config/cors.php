<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // In production, set ALLOWED_ORIGINS env to specific frontend URL(s), comma-separated.
    // Example: ALLOWED_ORIGINS=https://example.com,https://admin.example.com
    // Wildcard ['*'] with supports_credentials=true is dangerous in production.
    'allowed_origins' => env('ALLOWED_ORIGINS') ? explode(',', env('ALLOWED_ORIGINS')) : ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
