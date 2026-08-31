<?php

use App\Models\Merchant;
use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;

test('database seeder creates local admin and merchant accounts', function () {
    $this->seed();

    $admin = Staff::query()->where('email', 'admin@merebhub.test')->first();
    $merchant = User::query()->where('email', 'merchant@merebhub.test')->first();
    $merchantProfile = Merchant::query()->where('slug', 'demo-merchant')->first();
    $demoProduct = Product::query()->whereBelongsTo($merchantProfile)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->admin)->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeTrue()
        ->and($merchant)->not->toBeNull()
        ->and($merchant->merchant_access)->toBeTrue()
        ->and(Hash::check('password', $merchant->password))->toBeTrue()
        ->and($merchantProfile)->not->toBeNull()
        ->and($merchantProfile->users()->whereKey($merchant->getKey())->exists())->toBeTrue()
        ->and($demoProduct)->not->toBeNull()
        ->and($admin->canAccessPanel(Filament::getPanel('lunar')))->toBeTrue()
        ->and($merchant->canAccessPanel(Filament::getPanel('merchant')))->toBeTrue();
});
