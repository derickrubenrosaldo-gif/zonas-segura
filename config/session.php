<?php

return [
    'driver' => env('SESSION_DRIVER', env('ADMIN_PANEL_PASSWORD_HASH') && (env('ZONA_SEGURA_SUPABASE_URL') || env('DATABASE_URL')) ? 'database' : 'cookie'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => 'sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'betanzo_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => true,
    'http_only' => true,
    'same_site' => 'lax',
];
