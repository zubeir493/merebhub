<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\MerchantPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    MerchantPanelProvider::class,
    HorizonServiceProvider::class,
];
