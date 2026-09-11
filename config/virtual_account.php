<?php

/*
|--------------------------------------------------------------------------
| Virtual Account Providers (BTN SNAP VA)
|--------------------------------------------------------------------------
| Dibaca oleh App\Services\Integrasi\BtnVaService.
| Path endpoint bisa dioverride via ENV bila portal BTN memberi path lain.
*/

return [
    'btn' => [
        'production' => env('BTN_VA_PRODUCTION', false),
        'base_url' => env('BTN_VA_BASE_URL', ''),
        'origin' => env('BTN_VA_ORIGIN', ''),
        'timeout_seconds' => env('BTN_VA_TIMEOUT_SECONDS', 30),
        'default_expired_days' => env('BTN_VA_DEFAULT_EXPIRED_DAYS', 7),
        'credentials' => [
            'oauth_id' => env('BTN_VA_CLIENT_ID', ''),
            'client_key' => env('BTN_VA_CLIENT_KEY', ''),
            'apikey_id' => env('BTN_VA_PARTNER_ID', ''),
            'apikey_secret' => env('BTN_VA_CLIENT_SECRET', ''),
            'partner_service_id' => env('BTN_VA_PARTNER_SERVICE_ID', ''),
            'channel_id' => env('BTN_VA_CHANNEL_ID', ''),
            'private_rsa_key_base64' => env('BTN_VA_PRIVATE_RSA_KEY_BASE64', ''),
            'current_account_no' => env('BTN_VA_CURRENT_ACCOUNT_NO', ''),
        ],
        'endpoints' => [
            'token' => env('BTN_VA_PATH_TOKEN', '/snap/v1.0/access-token/b2b'),
            'create' => env('BTN_VA_PATH_CREATE', '/snap/v1.0/transfer-va/create-va'),
            'update' => env('BTN_VA_PATH_UPDATE', '/snap/v1.0/transfer-va/update-va'),
            'inquiry' => env('BTN_VA_PATH_INQUIRY', '/snap/v1.0/transfer-va/inquiry-va'),
            'status' => env('BTN_VA_PATH_STATUS', '/snap/v1.0/transfer-va/inquiry-status'),
            'delete' => env('BTN_VA_PATH_DELETE', '/snap/v1.0/transfer-va/delete-va'),
            'report' => env('BTN_VA_PATH_REPORT', '/snap/v1.0/transfer-va/report'),
        ],
    ],
];
