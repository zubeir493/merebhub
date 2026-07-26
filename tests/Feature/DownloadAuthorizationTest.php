<?php

use App\Enums\LicenseStatus;
use App\Enums\MalwareScanStatus;
use App\Enums\OrderStatus;
use App\Enums\ReleaseStatus;
use App\Models\AppVersion;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

test('only the purchaser can download a clean published version', function () {
    Storage::fake('local');
    config(['filesystems.builds_disk' => 'local']);
    $buyer = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::factory()->published()->create();
    $plan = ProductPlan::factory()->for($product)->create();
    $order = Order::factory()->for($buyer, 'buyer')->for($product)->create([
        'status' => OrderStatus::Fulfilled,
        'paid_at' => now(),
    ]);
    $item = $order->items()->create([
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'quantity' => 1,
        'unit_amount' => '100.00',
        'total' => '100.00',
        'product_name' => $product->name,
        'plan_name' => $plan->name,
        'unit_amount_minor' => 10000,
        'total_minor' => 10000,
        'currency' => 'ETB',
        'update_duration_days' => 365,
    ]);
    $version = AppVersion::factory()->for($product)->create([
        'file_path' => 'builds/app.zip',
        'release_status' => ReleaseStatus::Published,
        'scan_status' => MalwareScanStatus::Clean,
        'published_at' => now(),
    ]);
    Storage::disk('local')->put($version->file_path, 'safe archive');
    $license = License::factory()->for($order)->for($product)->create([
        'order_item_id' => $item->id,
        'customer_id' => $buyer->id,
        'product_plan_id' => $plan->id,
        'status' => LicenseStatus::Active,
    ]);
    $url = URL::temporarySignedRoute('downloads.show', now()->addMinutes(5), [
        'version' => $version,
        'license' => $license,
    ]);

    $this->actingAs($otherUser)->get($url)->assertNotFound();
    $this->actingAs($buyer)->get($url)->assertSuccessful();
    $this->assertDatabaseHas('downloads', [
        'user_id' => $buyer->id,
        'order_item_id' => $item->id,
        'app_version_id' => $version->id,
    ]);
});

test('unscanned versions cannot be downloaded', function () {
    Storage::fake('local');
    config(['filesystems.builds_disk' => 'local']);
    $buyer = User::factory()->create();
    $product = Product::factory()->published()->create();
    $plan = ProductPlan::factory()->for($product)->create();
    $order = Order::factory()->for($buyer, 'buyer')->for($product)->create([
        'status' => OrderStatus::Paid,
        'paid_at' => now(),
    ]);
    $item = $order->items()->create([
        'product_id' => $product->id,
        'product_plan_id' => $plan->id,
        'quantity' => 1,
        'unit_amount' => '100.00',
        'total' => '100.00',
        'product_name' => $product->name,
        'plan_name' => $plan->name,
        'unit_amount_minor' => 10000,
        'total_minor' => 10000,
        'currency' => 'ETB',
    ]);
    $version = AppVersion::factory()->for($product)->create([
        'file_path' => 'builds/pending.zip',
        'release_status' => ReleaseStatus::Published,
        'scan_status' => MalwareScanStatus::Pending,
        'published_at' => now(),
    ]);
    Storage::disk('local')->put($version->file_path, 'pending archive');
    $license = License::factory()->for($order)->for($product)->create([
        'order_item_id' => $item->id,
        'customer_id' => $buyer->id,
        'product_plan_id' => $plan->id,
    ]);

    $this->actingAs($buyer)->get(URL::temporarySignedRoute('downloads.show', now()->addMinutes(5), [
        'version' => $version,
        'license' => $license,
    ]))->assertForbidden();
});
