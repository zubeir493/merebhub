<?php

use App\Domain\Billing\Actions\EnsureInvoiceSnapshotAction;
use App\Models\InvoiceSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Lunar\Core\Models\Country;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderAddress;
use Lunar\Core\Models\OrderLine;

beforeEach(function (): void {
    $this->seed();
});

function createInvoiceTestOrder(User $customer): Order
{
    $order = Order::factory()->placed()->create([
        'user_id' => $customer->getKey(),
        'currency_code' => 'ETB',
        'sub_total' => 12500,
        'discount_total' => 500,
        'tax_total' => 0,
        'shipping_total' => 0,
        'total' => 12000,
        'payment_status' => 'paid',
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'type' => 'digital',
        'description' => 'License for Product Alpha',
        'option' => 'Pro',
        'identifier' => 'product-alpha-pro',
        'unit_price' => 12500,
        'sub_total' => 12500,
        'discount_total' => 500,
        'tax_total' => 0,
        'total' => 12000,
    ]);

    OrderAddress::factory()->create([
        'order_id' => $order->getKey(),
        'country_id' => Country::query()->where('iso3', 'ETH')->value('id'),
        'type' => 'billing',
        'first_name' => 'Mimi',
        'last_name' => 'Tesfaye',
        'company_name' => 'Acme Ethiopia PLC',
        'tax_identifier' => 'TIN-123456789',
        'contact_email' => $customer->email,
        'line_one' => 'Bole Road',
        'city' => 'Addis Ababa',
        'postcode' => '1000',
    ]);

    return $order;
}

test('a placed order receives one immutable invoice snapshot for its customer', function (): void {
    $customer = User::factory()->create();
    $order = createInvoiceTestOrder($customer);
    $snapshotAction = app(EnsureInvoiceSnapshotAction::class);

    $invoice = $snapshotAction->handle($order);
    $order->forceFill(['total' => 1, 'sub_total' => 1])->save();
    $order->billingAddress()->update(['line_one' => 'Changed after checkout']);
    $sameInvoice = $snapshotAction->handle($order->fresh());

    expect($sameInvoice->is($invoice))->toBeTrue()
        ->and($sameInvoice->total)->toBe(12000)
        ->and($sameInvoice->billing_snapshot['line_one'])->toBe('Bole Road')
        ->and($sameInvoice->line_items[0]['description'])->toBe('License for Product Alpha');

    $rawBillingSnapshot = DB::table('invoice_snapshots')->where('id', $invoice->getKey())->value('billing_snapshot');
    expect($rawBillingSnapshot)->not->toContain('Acme Ethiopia PLC')
        ->and($rawBillingSnapshot)->not->toContain('TIN-123456789');

    $this->assertDatabaseCount('invoice_snapshots', 1);
});

test('customers can view their invoices but cannot access another customer order', function (): void {
    $owner = User::factory()->create();
    $order = createInvoiceTestOrder($owner);
    $otherCustomer = User::factory()->create();
    $invoice = app(EnsureInvoiceSnapshotAction::class)->handle($order);

    expect(Order::query()->where('public_id', $order->public_id)->exists())->toBeTrue()
        ->and((int) $order->user_id)->toBe((int) $owner->getKey())
        ->and($owner->orders()->where('public_id', $order->public_id)->exists())->toBeTrue()
        ->and($order->placed_at)->not->toBeNull()
        ->and(Gate::forUser($owner)->allows('view', $invoice))->toBeTrue()
        ->and(str_ends_with(route('account.invoices.show', ['invoiceOrder' => $order->public_id]), $order->public_id))->toBeTrue();

    $this->actingAs($owner)
        ->get(route('account.invoices.show', ['invoiceOrder' => $order->public_id]))
        ->assertSuccessful()
        ->assertSee('License for Product Alpha')
        ->assertSee('Acme Ethiopia PLC')
        ->assertSee($order->reference);

    $this->actingAs($otherCustomer)
        ->get(route('account.invoices.show', ['invoiceOrder' => $order->public_id]))
        ->assertNotFound();

    expect(InvoiceSnapshot::query()->count())->toBe(1);
});

test('draft orders cannot generate customer invoices', function (): void {
    $order = Order::factory()->create(['user_id' => User::factory()]);

    expect(fn () => app(EnsureInvoiceSnapshotAction::class)->handle($order))
        ->toThrow(LogicException::class);

    $this->assertDatabaseCount('invoice_snapshots', 0);
});
