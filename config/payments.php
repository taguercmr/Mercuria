<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default payment provider
    |--------------------------------------------------------------------------
    |
    | Primary: fapshi — Secondary adapter: cinetpay
    |
    */
    'default' => env('PAYMENT_PROVIDER', 'fapshi'),

    'currency' => env('PAYMENT_CURRENCY', 'XAF'),

    'fapshi' => [
        'apiuser' => env('FAPSHI_APIUSER'),
        'apikey' => env('FAPSHI_APIKEY'),
        'base_url' => env('FAPSHI_BASE_URL', 'https://sandbox.fapshi.com'),
        'webhook_secret' => env('FAPSHI_WEBHOOK_SECRET'),
    ],

    'cinetpay' => [
        'apikey' => env('CINETPAY_APIKEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        'base_url' => env('CINETPAY_BASE_URL', 'https://api-checkout.cinetpay.com/v2'),
        'notify_secret' => env('CINETPAY_NOTIFY_SECRET'),
    ],

];
