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

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'from' => env('SMS_FROM'),

        'kavenegar' => [
            'api_key' => env('KAVENEGAR_API_KEY'),
            'sender' => env('KAVENEGAR_SENDER'),
        ],

        'smsir' => [
            'api_key' => env('SMS_IR_API_KEY'),
            'verify_template_id' => (int) env('SMS_IR_VERIFY_TEMPLATE_ID', 0),
            'line_number' => env('SMS_IR_LINE_NUMBER'),
            'parameter_name' => env('SMS_IR_PARAMETER_NAME', 'Code'),
            'base_url' => env('SMS_IR_BASE_URL', 'https://api.sms.ir'),
        ],
    ],

    'otp' => [
        'length' => (int) env('OTP_LENGTH', 6),
        'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 120),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
        'password_reset_token_ttl_seconds' => (int) env('PASSWORD_RESET_TOKEN_TTL_SECONDS', 600),
        // Universal bypass code for development. When set, any caller of
        // verify() can pass this value and it will short-circuit to success
        // — useful while real SMS credentials are pending. Unset (env to
        // empty/null) in production to disable the bypass.
        'master_code' => env('OTP_MASTER_CODE', '111111'),
    ],

];
