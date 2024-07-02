<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paycom Configuration
    |--------------------------------------------------------------------------
    |
    | Set merchant ID and key from https://merchant.payme.uz
    |
    */

    'checkout_url' => env('PAYME_CHECKOUT_URL', 'https://checkout.paycom.uz/'),
    'merchant_id' => env('PAYME_MERCHANT_ID', ''),
    'login' => env('PAYME_LOGIN', 'Paycom'),
    'key' => env('PAYME_KEY', ''),
];
