<?php

return [
    /*
    |--------------------------------------------------------------------------
    | InstaPay Phone Number
    |--------------------------------------------------------------------------
    |
    | The phone number customers should send their InstaPay payments to.
    |
    */
    'phone' => env('INSTAPAY_PHONE', '01012345678'),

    /*
    |--------------------------------------------------------------------------
    | Account Holder Name
    |--------------------------------------------------------------------------
    |
    | The name displayed on the payment page so customers can verify
    | they are sending to the correct InstaPay account.
    |
    */
    'account_name' => env('INSTAPAY_ACCOUNT_NAME', 'SwiftPOS Store'),

    /*
    |--------------------------------------------------------------------------
    | Order Expiration (minutes)
    |--------------------------------------------------------------------------
    |
    | If set, unpaid InstaPay orders will be marked as expired after this
    | number of minutes. Leave empty to disable expiration.
    |
    */
    'order_expiration_minutes' => env('INSTAPAY_ORDER_EXPIRATION_MINUTES'),

    /*
    |--------------------------------------------------------------------------
    | Deep Link Base URL
    |--------------------------------------------------------------------------
    |
    | The base scheme used to open the InstaPay mobile application.
    |
    */
    'deeplink_base' => env('INSTAPAY_DEEPLINK', 'instapay://transfer'),
];
