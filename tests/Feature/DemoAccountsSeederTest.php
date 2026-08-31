<?php

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('database seeder creates local admin and merchant accounts', function () {
    $this->seed();

    $admin = Staff::query()->where('email', 'admin@merebhub.test')->first();
    $merchant = User::query()->where('email', 'merchant@merebhub.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->admin)->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeTrue()
        ->and($merchant)->not->toBeNull()
        ->and($merchant->merchant_access)->toBeTrue()
        ->and(Hash::check('password', $merchant->password))->toBeTrue();
});
