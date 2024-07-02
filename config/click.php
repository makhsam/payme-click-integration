<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Click Configuration
    |--------------------------------------------------------------------------
    |
    | Set configurations from https://merchant.click.uz
    |
    */

    'checkout_url' => env('CLICK_CHECKOUT_URL', 'https://my.click.uz/services/pay'),
    'merchant_id' => env('CLICK_MERCHANT_ID', 0),
    'merchant_user_id' => env('CLICK_MERCHANT_USER_ID', 0),
    'service_id' => env('CLICK_SERVICE_ID', 0),
    'secret_key' => env('CLICK_SECRET_KEY', ''),
];
