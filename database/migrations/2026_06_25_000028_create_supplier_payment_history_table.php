<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_history');
    }
};
