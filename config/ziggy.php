<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Filtered Routes
    |--------------------------------------------------------------------------
    |
    | By default, Ziggy includes all routes. You can filter the routes that
    | are shared with the frontend by defining an 'except' or 'only' array.
    |
    */

    // 'only' => ['dashboard', 'login', 'login.store', 'logout', 'home', 'passkey.*', 'system.*', 'master.*', 'packages.*', 'promotions.*', 'product.*', 'ingredient.*', 'item.*', 'finance.*', 'menu.*', 'profile', 'account.*', 'public.table.*', 'password.*', 'otp.*', 'login.google', 'api.cashier.*', 'kitchen.*', 'orders.*', 'tables.*', 'language.switch'],

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    |
    | You can also define groups of routes to be shared with different parts
    | of your application.
    |
    */

    'groups' => [
        'staff' => ['login', 'home'],
    ],
];
