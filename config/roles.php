<?php

return [
    'roles' => [
        'admin' => [
            'view dashboard',
            'manage products',
            'manage inventory',
            'manage orders',
            'process sales',
            'manage users',
            'manage cards',
            'view reports',
        ],
        'manager' => [
            'view dashboard',
            'manage products',
            'manage inventory',
            'manage orders',
            'process sales',
            'view reports',
        ],
        'cashier' => [
            'view dashboard',
            'manage orders',
            'process sales',
        ],
    ],
];
