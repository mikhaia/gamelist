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

    'rawg' => [
        'key' => env('RAWG_API_KEY'),
        'base_url' => env('RAWG_API_URL', 'https://api.rawg.io/api'),
        'sync_ttl_days' => (int) env('RAWG_SYNC_TTL_DAYS', 30),
        'search_ttl_hours' => (int) env('RAWG_SEARCH_TTL_HOURS', 6),
    ],

    'steam' => [
        'openid_url' => env('STEAM_OPENID_URL', 'https://steamcommunity.com/openid/login'),
        'realm' => env('STEAM_REALM'),
        'return_url' => env('STEAM_RETURN_URL'),
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

];
