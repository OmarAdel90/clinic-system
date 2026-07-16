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

    'meta_whatsapp' => [
        'access_token'     => env('META_WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id'  => env('META_PHONE_NUMBER_ID', env('META_WABA_ID')),
        'waba_id'          => env('META_WABA_ID'),
        'api_version'      => env('META_API_VERSION', 'v20.0'),
        'verify_token'     => env('META_WHATSAPP_VERIFY_TOKEN', 'clinic_verify'),
    ],

    'meta_facebook' => [
        'page_id'           => env('META_FACEBOOK_PAGE_ID'),
        'page_access_token' => env('META_FACEBOOK_PAGE_ACCESS_TOKEN'),
        'instagram_access_token' => env('META_INSTAGRAM_ACCESS_TOKEN'),
        'ads_access_token'  => env('META_ADS_ACCESS_TOKEN'),
        'ad_account_id'     => env('META_AD_ACCOUNT_ID'),
        'app_id'            => env('META_APP_ID'),
        'api_version'       => env('META_API_VERSION', 'v20.0'),
        'verify_token'      => env('META_FACEBOOK_VERIFY_TOKEN', 'clinic_verify'),
    ],

    'meta_app_secret' => env('META_APP_SECRET'),

];
