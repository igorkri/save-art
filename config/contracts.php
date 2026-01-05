<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contract Template Version
    |--------------------------------------------------------------------------
    |
    | The current version of the contract template. When this changes,
    | users will need to sign a new contract.
    |
    */
    'template_version' => env('CONTRACT_TEMPLATE_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Contract Template Path
    |--------------------------------------------------------------------------
    |
    | The path to the contract template PDF file in storage.
    |
    */
    'template_path' => env('CONTRACT_TEMPLATE_PATH', 'templates/contract_template.pdf'),

    /*
    |--------------------------------------------------------------------------
    | Contract Expiration Days
    |--------------------------------------------------------------------------
    |
    | The number of days before an unsigned contract expires.
    |
    */
    'expires_days' => env('CONTRACT_EXPIRES_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Available Sign Services
    |--------------------------------------------------------------------------
    |
    | The list of available signing services for contracts.
    |
    */
    'sign_services' => [
        'diia' => [
            'enabled' => env('CONTRACT_SIGN_DIIA_ENABLED', true),
            'name' => 'Дія',
        ],
        'vchasno' => [
            'enabled' => env('CONTRACT_SIGN_VCHASNO_ENABLED', false),
            'name' => 'Вчасно',
        ],
        'iit' => [
            'enabled' => env('CONTRACT_SIGN_IIT_ENABLED', false),
            'name' => 'ІІТ',
        ],
        'manual' => [
            'enabled' => env('CONTRACT_SIGN_MANUAL_ENABLED', true),
            'name' => 'Ручний підпис',
        ],
    ],
];
