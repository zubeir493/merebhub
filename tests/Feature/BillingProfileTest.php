<?php

use App\Models\BillingProfile;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Lunar\Core\Models\Country;
use Lunar\Core\Models\Order;

beforeEach(function (): void {
    $this->seed();
    config()->set('services.chapa.secret_key', 'test-secret');
});

test('customers can save encrypted personal and business billing details', function (): void {
    $customer = User::factory()->create();
    $billingDetails = [
        'billing_type' => 'business',
        'company_name' => 'Acme Ethiopia PLC',
        'tax_identifier' => 'TIN-123456789',
        'first_name' => 'Mimi',
        'last_name' => 'Tesfaye',
        'contact_email' => 'billing@example.test',
        'contact_phone' => '+251911000000',
        'line_one' => 'Bole Road',
        'line_two' => 'Suite 3',
        'city' => 'Addis Ababa',
        'state' => 'Addis Ababa',
        'postcode' => '1000',
        'country_iso3' => 'ETH',
    ];

    $this->actingAs($customer)
        ->from(route('account.billing'))
        ->put(route('account.billing.update'), $billingDetails)
        ->assertRedirect(route('account.billing'))
        ->assertSessionHas('status', 'Billing details saved.');

    $profile = BillingProfile::query()->sole();
    $rawProfile = DB::table('billing_profiles')->where('id', $profile->getKey())->first();

    expect($profile->company_name)->toBe('Acme Ethiopia PLC')
        ->and($profile->tax_identifier)->toBe('TIN-123456789')
        ->and($profile->toArray())->not->toHaveKey('contact_email')
        ->and($rawProfile->tax_identifier)->not->toContain('TIN-123456789')
        ->and($rawProfile->contact_email)->not->toContain('billing@example.test');

    $this->actingAs($customer)
        ->put(route('account.billing.update'), [
            ...$billingDetails,
            'billing_type' => 'personal',
            'company_name' => '',
            'tax_identifier' => '',
        ])
        ->assertRedirect(route('account.billing'));

    expect($profile->fresh()->billing_type)->toBe('personal')
        ->and($profile->fresh()->company_name)->toBeNull();
});

test('business billing profiles require a company name and known country', function (): void {
    $this->actingAs(User::factory()->create())
        ->from(route('account.billing'))
        ->put(route('account.billing.update'), [
            'billing_type' => 'business',
            'first_name' => 'Mimi',
            'contact_email' => 'billing@example.test',
            'line_one' => 'Bole Road',
            'city' => 'Addis Ababa',
            'postcode' => '1000',
            'country_iso3' => 'ZZZ',
        ])
        ->assertSessionHasErrors(['company_name', 'country_iso3']);

    expect(BillingProfile::query()->count())->toBe(0);
    expect(Country::query()->where('iso3', 'ETH')->exists())->toBeTrue();
});

test('billing profiles are only available to authenticated customers', function (): void {
    $this->get(route('account.billing'))->assertRedirect(route('login'));
});

test('headless checkout creates an invoice snapshot from the account identity', function (): void {
    $customer = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();
    $product = Product::published()->with(['defaultUrl', 'variants'])->firstOrFail();

    $this->actingAs($customer)
        ->post(route('cart.store', $product), ['variant_id' => $product->variants->first()->id])
        ->assertRedirect(route('products.show', $product));

    $this->get(route('checkout.show'))->assertRedirect(route('cart.index'));

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/test-payment'],
        ]),
    ]);

    $this->post(route('checkout.store'))->assertRedirect('https://checkout.chapa.co/test-payment');

    $order = Order::query()->sole();
    $transactionReference = (string) data_get($order->meta, 'chapa.tx_ref');
    Http::fake([
        'https://api.chapa.co/v1/transaction/verify/*' => Http::response([
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'tx_ref' => $transactionReference,
                'amount' => number_format($order->total / 100, 2, '.', ''),
                'currency' => 'ETB',
            ],
        ]),
    ]);

    $this->get(route('payments.chapa.return', ['tx_ref' => $transactionReference]))
        ->assertRedirect(route('checkout.complete', $order));

    $order->refresh();
    $snapshot = $order->user->invoiceSnapshots()->sole();

    expect($order->billingAddress()->exists())->toBeFalse()
        ->and($snapshot->billing_snapshot['first_name'])->toBe('Demo')
        ->and($snapshot->billing_snapshot['last_name'])->toBe('Buyer')
        ->and($snapshot->billing_snapshot['contact_email'])->toBe($customer->email)
        ->and($snapshot->billing_snapshot['line_one'])->toBeNull();
});

test('billing details are not shown in account navigation', function (): void {
    $customer = User::query()->where('email', 'buyer@merebhub.test')->firstOrFail();

    $this->actingAs($customer)
        ->get(route('account.settings'))
        ->assertSuccessful()
        ->assertDontSee('Billing details');
});
