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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    //lineログイン機能
    'line' => [
        'client_id'=>env('LINE_CHANNEL_ID'),
        'client_secret' =>env('LINE_CHANNEL_SECRET'),
        'redirect'=>env('LINE_REDIRECT'),
    ],
    //楽天API
    'rakuten' => [
        'application_id' => env('RAKUTEN_APPLICATION_ID'),
        'access_key' => env('RAKUTEN_ACCESS_KEY'),
        'affiliate_id' => env('RAKUTEN_AFFILIATE_ID'),
        'origin' => env('RAKUTEN_ORIGIN'),
    ],
    //MQTT (AWS IoT Core)
    'mqtt' => [
        'host'      => env('MQTT_BROKER_HOST', 'localhost'),
        'port'      => env('MQTT_BROKER_PORT', 8883),
        'client_id' => env('MQTT_CLIENT_ID', 'laravel_mqtt_listener'),
        'cert_ca'   => env('MQTT_CERT_CA'),
        'cert_crt'  => env('MQTT_CERT_CRT'),
        'cert_key'  => env('MQTT_CERT_KEY'),
    ],


];
