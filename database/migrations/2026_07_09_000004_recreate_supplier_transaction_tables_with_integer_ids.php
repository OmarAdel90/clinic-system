<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('supplier_payment_history');
        Schema::dropIfExists('warehouse_supplier_transactions');

        Schema::create('warehouse_supplier_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->json('items_bought')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_payment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained('warehouse_supplier_transactions')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('total_paid', 12, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_history');
        Schema::dropIfExists('warehouse_supplier_transactions');

        Schema::create('warehouse_supplier_transactions', function (Blueprint $table) {
            $table->string('transaction_id')->primary();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->json('items_bought')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_payment_history', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('batch_id')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('total_paid', 12, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });
    }
};
