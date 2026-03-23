<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Carrier Profiles
    |--------------------------------------------------------------------------
    |
    | Register additional carrier profiles beyond the built-in ones.
    | Each entry maps a carrier code to a class implementing CarrierProfileInterface.
    |
    | 'HLL'  => \App\EdiLink\Carriers\HllCarrierProfile::class,
    | 'KMTC' => \App\EdiLink\Carriers\KmtcCarrierProfile::class,
    |
    */
    'carriers' => [
        // 'HLL' => \App\EdiLink\Carriers\HllCarrierProfile::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Output Format
    |--------------------------------------------------------------------------
    | 'text'  — fixed-width EDI file format
    | 'array' — structured array rows for Excel / OVA / API
    |
    */
    'default_format' => env('EDILINK_FORMAT', 'text'),

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    | Where generated EDI files are written by the Artisan command.
    |
    */
    'output_path' => env('EDILINK_OUTPUT_PATH', storage_path('app/edilink')),

];
