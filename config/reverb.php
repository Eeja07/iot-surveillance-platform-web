<?php

return [

  /*
    |--------------------------------------------------------------------------
    | Default Reverb Server
    |--------------------------------------------------------------------------
    */

  'default' => env('REVERB_SERVER', 'reverb'),

  /*
    |--------------------------------------------------------------------------
    | Reverb Servers
    |--------------------------------------------------------------------------
    */

  'servers' => [

    'reverb' => [
      'host' => env('REVERB_HOST', '0.0.0.0'),
      'port' => env('REVERB_PORT', 8080),
      'hostname' => env('REVERB_HOSTNAME'),
      'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10_000),
      'scaling' => [
        'enabled' => env('REVERB_SCALING_ENABLED', false),
        'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
      ],
      'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
      'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
      'tls' => [
        'cert' => env('REVERB_TLS_CERT'),
        'key' => env('REVERB_TLS_KEY'),
        'ca' => env('REVERB_TLS_CA'),
        'verify_peer' => env('REVERB_TLS_VERIFY_PEER', false),
        'allow_self_signed' => env('REVERB_TLS_ALLOW_SELF_SIGNED', false),
      ],
    ],

  ],

  /*
    |--------------------------------------------------------------------------
    | Reverb Applications
    |--------------------------------------------------------------------------
    */

  'apps' => [

    'provider' => 'config',

    'apps' => [
      [
        'app_id' => env('REVERB_APP_ID'),
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        // PERBAIKAN: Menggunakan wildcard ['*'] untuk mengizinkan semua origin
        'allowed_origins' => ['*'],
        'ping_interval' => env('REVERB_PING_INTERVAL', 60),
        'max_message_size' => env('REVERB_MAX_MESSAGE_SIZE', 10_000),
      ],
    ],

  ],

];
