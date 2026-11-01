<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 10. sales (tanpa cashier_id)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('invoice_no')->unique();
            $table->string('idempotency_key')->unique();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method')->default('QRIS'); // QRIS, CASH
            $table->string('status')->default('PENDING_PAYMENT'); // PENDING_PAYMENT, COMPLETED, EXPIRED, CANCELLED, PARTIALLY_REFUNDED, REFUNDED
            $table->timestamp('sold_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'sold_at']);
            $table->index(['store_id', 'status']);
        });

        // 11. sale_items
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('quantity', 14, 3);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        // 12. stock_reservations
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignId('stock_balance_id')->constrained('stock_balances')->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('status')->default('RESERVED'); // RESERVED, COMMITTED, RELEASED
            $table->timestamps();
        });

        // 13. payment_intents (QRIS dinamis)
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('provider')->default('QRIS_GATEWAY');
            $table->decimal('amount', 15, 2);
            $table->text('qr_payload');
            $table->string('status')->default('PENDING'); // CREATED, PENDING, PAID, EXPIRED, FAILED, CANCELLED
            $table->string('provider_reference')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // 14. payment_transactions
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_intent_id')->constrained('payment_intents')->cascadeOnDelete();
            $table->string('provider_transaction_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->timestamp('paid_at');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        // 15. payment_webhook_events (deduplikasi webhook)
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_intent_id')->nullable()->constrained('payment_intents')->nullOnDelete();
            $table->string('event_type');
            $table->string('event_dedupe_key')->unique();
            $table->json('payload');
            $table->boolean('signature_valid')->default(true);
            $table->string('status')->default('PROCESSED'); // PROCESSED, FAILED, IGNORED
            $table->timestamps();
        });

        // 18. refunds (tanpa approved_by)
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('refund_no')->unique();
            $table->decimal('amount', 15, 2);
            $table->text('reason');
            $table->string('refund_method')->default('QRIS'); // QRIS, CASH, TRANSFER
            $table->timestamps();
        });

        // 19. refund_items
        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained('refunds')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('amount', 15, 2);
            $table->boolean('restore_stock')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_intents');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};

