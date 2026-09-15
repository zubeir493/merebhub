<?php

return [

    'default' => env('PAYMENTS_TYPE', 'chapa'),

    'types' => [
        'chapa' => [
            'driver' => 'chapa',
            'authorized' => 'captured',
        ],
    ],

];
