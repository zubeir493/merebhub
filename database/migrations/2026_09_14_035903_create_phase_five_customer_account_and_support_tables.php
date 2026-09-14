<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $lunarPrefix = (string) config('lunar.database.table_prefix', 'lunar_');

        Schema::create('billing_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('billing_type', 20)->default('personal');
            $table->text('company_name')->nullable();
            $table->text('tax_identifier')->nullable();
            $table->text('first_name');
            $table->text('last_name')->nullable();
            $table->text('contact_email');
            $table->text('contact_phone')->nullable();
            $table->text('line_one');
            $table->text('line_two')->nullable();
            $table->text('city');
            $table->text('state')->nullable();
            $table->text('postcode');
            $table->string('country_iso3', 3);
            $table->timestamps();
        });

        Schema::create('invoice_snapshots', function (Blueprint $table) use ($lunarPrefix): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained($lunarPrefix.'orders')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('currency_code', 3);
            $table->unsignedInteger('currency_factor')->default(100);
            $table->unsignedTinyInteger('currency_decimal_places')->default(2);
            $table->string('payment_status')->default('pending');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('shipping_total')->default(0);
            $table->unsignedBigInteger('total');
            $table->text('billing_snapshot');
            $table->json('line_items');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['user_id', 'issued_at']);
        });

        Schema::create('user_sessions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('session_hash', 64)->unique();
            $table->text('session_id');
            $table->string('device_label', 160);
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('last_active_at')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at', 'last_active_at']);
        });

        Schema::create('support_tickets', function (Blueprint $table) use ($lunarPrefix): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()
                ->constrained($lunarPrefix.'staff')->nullOnDelete();
            $table->string('subject', 160);
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
            $table->index(['assigned_staff_id', 'status']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) use ($lunarPrefix): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained($lunarPrefix.'staff')->nullOnDelete();
            $table->boolean('is_internal')->default(false);
            $table->text('body');
            $table->timestamps();

            $table->index(['support_ticket_id', 'is_internal', 'created_at']);
        });

        Schema::create('support_ticket_attachments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('support_ticket_message_id')
                ->constrained()->cascadeOnDelete();
            $table->string('disk', 40)->default('private');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->char('checksum', 64);
            $table->string('scan_status', 20)->default('pending')->index();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('invoice_snapshots');
        Schema::dropIfExists('billing_profiles');
    }
};
