<?php

use App\Models\Staff;

return [
    'guard' => 'staff',
    'provider' => 'staff',
    'model' => Staff::class,
    'register_guard' => true,
];
