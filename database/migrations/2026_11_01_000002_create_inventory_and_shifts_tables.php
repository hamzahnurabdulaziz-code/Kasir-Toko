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
        // 5. stock_balances (on_hand, reserved)
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('on_hand', 14, 3)->default(0.000); // Saldo fisik riil
            $table->decimal('reserved', 14, 3)->default(0.000); // Ditahan pending QRIS
            $table->timestamps();

            $table->unique(['store_id', 'product_id']);
        });

        // 6. stock_movements (tanpa actor_id)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type'); // OPENING, PURCHASE_IN, SALE_OUT, RETURN_IN, ADJUSTMENT
            $table->decimal('qty_delta', 14, 3);
            $table->decimal('before_qty', 14, 3);
            $table->decimal('after_qty', 14, 3);
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'product_id', 'created_at']);
        });

        // 9. cashier_shifts (tanpa cashier_id)
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('terminal_label')->default('Kasir-01');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0.00);
            $table->decimal('expected_cash', 15, 2)->default(0.00);
            $table->decimal('actual_cash', 15, 2)->nullable();
            $table->decimal('difference', 15, 2)->nullable();
            $table->string('status')->default('OPEN'); // OPEN, CLOSED
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 20. cash_movements (tanpa actor_id)
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->string('type'); // CASH_IN, CASH_OUT
            $table->decimal('amount', 15, 2);
            $table->string('note');
            $table->timestamps();
        });

        // 16. purchases (tanpa created_by)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('purchase_no')->unique();
            $table->string('status')->default('DRAFT'); // DRAFT, RECEIVED, CANCELLED
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->timestamp('received_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 17. purchase_items
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cashier_shifts');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
    }
};

