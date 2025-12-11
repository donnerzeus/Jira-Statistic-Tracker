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

    'jira' => [
        'base_url' => env('JIRA_BASE_URL'),
        'email' => env('JIRA_USER_EMAIL'),
        'api_token' => env('JIRA_API_TOKEN'),
        'service_project_key' => env('SERVICE_PROJECT_KEY', 'ITSM'),
        'demand_project_key' => env('DEMAND_PROJECT_KEY', 'BTID'),
    ],

    'google' => [
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'sheets' => [
            'service_id' => env('SERVICE_KPI_SPREADSHEET_ID'),
            'demand_id' => env('DEMAND_KPI_SPREADSHEET_ID'),
            'combined_id' => env('COMBINED_KPI_SPREADSHEET_ID'),
        ],
    ],

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

];
