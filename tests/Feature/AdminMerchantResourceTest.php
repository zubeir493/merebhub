<?php

use App\Domain\Merchants\Enums\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Filament\Admin\Resources\Merchants\Pages\ListMerchants;
use App\Models\Merchant;
use App\Models\Staff;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->staff = Staff::factory()->create(['admin' => true]);

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();
    $this->actingAs($this->staff, 'staff');
});

test('admin panel registers the merchant resource', function (): void {
    expect(Filament::getPanel('lunar')->getResources())
        ->toContain(MerchantResource::class);
});

test('staff can approve a pending merchant from the admin table', function (): void {
    $merchant = Merchant::factory()->create([
        'status' => MerchantStatus::Pending,
        'approved_at' => null,
    ]);

    Livewire::test(ListMerchants::class)
        ->assertCanSeeTableRecords([$merchant])
        ->callTableAction('approve', $merchant);

    $merchant->refresh();

    expect($merchant->status)->toBe(MerchantStatus::Approved)
        ->and($merchant->approved_at)->not->toBeNull();
});

test('staff can suspend an approved merchant from the admin table', function (): void {
    $merchant = Merchant::factory()->create([
        'status' => MerchantStatus::Approved,
        'approved_at' => now(),
    ]);

    Livewire::test(ListMerchants::class)
        ->assertCanSeeTableRecords([$merchant])
        ->callTableAction('suspend', $merchant);

    expect($merchant->refresh()->status)->toBe(MerchantStatus::Suspended);
});
