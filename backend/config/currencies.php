<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies (وجهتك)
    |--------------------------------------------------------------------------
    |
    | Data-driven currency catalogue used by validation, API responses,
    | Flutter dropdowns and the admin dashboard. The first entry is the
    | default for new properties.
    |
    */

    'default' => env('CURRENCY_DEFAULT', 'YER'),

    'supported' => [
        'YER' => [
            'code' => 'YER',
            'name_ar' => 'ريال يمني',
            'name_en' => 'Yemeni Rial',
            'symbol_ar' => 'ر.ي',
            'symbol_en' => 'YR',
            'flag' => '🇾🇪',
            'decimals' => 0,
        ],
        'SAR' => [
            'code' => 'SAR',
            'name_ar' => 'ريال سعودي',
            'name_en' => 'Saudi Riyal',
            'symbol_ar' => 'ر.س',
            'symbol_en' => 'SR',
            'flag' => '🇸🇦',
            'decimals' => 0,
        ],
        'USD' => [
            'code' => 'USD',
            'name_ar' => 'دولار أمريكي',
            'name_en' => 'US Dollar',
            'symbol_ar' => '$',
            'symbol_en' => '$',
            'flag' => '🇺🇸',
            'decimals' => 2,
        ],
    ],

];
