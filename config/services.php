<?php

return [
    'admin_panel_username' => env('ADMIN_PANEL_USERNAME'),
    'admin_panel_password_hash' => env('ADMIN_PANEL_PASSWORD_HASH'),
    'admin_api_key' => env('ADMIN_API_KEY'),
    'iot_api_key' => env('IOT_API_KEY'),
    'bulletins' => env('LOCAL_BULLETINS_JSON', '[]'),
];
