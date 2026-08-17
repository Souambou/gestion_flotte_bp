<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
     | Valeurs de repli : les cles saisies dans Parametres > Integrations
     | prennent le pas sur celles-ci.
     */
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'),
        'url' => env('SMS_API_URL'),
        'key' => env('SMS_API_KEY'),
        'sender_id' => env('SMS_SENDER_ID', 'BENINPETRO'),
    ],
];
