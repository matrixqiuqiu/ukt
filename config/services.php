<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'vantb' => [
        'url_base' => env('URL_VANTB', ''),
        'user_id' => env('USER_ID', ''),
        'user_secret' => env('USER_SECRET', ''),
        'id_mitra' => env('ID_MITRA', ''),
        'id_produk' => env('ID_PRODUK', ''),
        'nama_mitra' => env('NAMA_MITRA', ''),
    ],

    'siakad' => [
        'base_url' => env('BASE_API_SIAKAD'),
        'get_mahasiswa' => env('BASE_API_SIAKAD_GET_MAHASISWA'),
        'get_mahasiswa_angkatan' => env('BASE_API_SIAKAD_GET_MAHASISWA_ANGKATAN'),
        'srfcookie' => env('BASE_API_SIAKAD_SRFCOOKIE'),
        'login' => env('BASE_API_SIAKAD_LOGIN'),
        'login_mahasiswa' => env('BASE_API_SIAKAD_LOGIN_MAHASISWA'),
        'mahasiswa_nim' => env('BASE_API_SIAKAD_MAHASISWA_NIM'),
        'current_user' => env('BASE_API_SIAKAD_CURRENT_USER'),
        'username' => env('USERNAME_SIAKAD'),
        'password' => env('PASSWORD_SIAKAD'),
    ],

];
