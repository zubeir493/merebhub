<?php

return [

    'default' => env('PAYMENTS_TYPE', 'cash-in-hand'),

    'types' => [
        'cash-in-hand' => [
            'driver' => 'offline',
            'authorized' => 'captured',
        ],
        'chapa' => [
            'driver' => 'chapa',
            'authorized' => 'captured',
        ],
    ],

];
