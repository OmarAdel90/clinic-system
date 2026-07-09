<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_supplier_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_supplier_transactions', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
        });

        Schema::table('supplier_payment_history', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payment_history', 'batch_id')) {
                $table->dropColumn('batch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_supplier_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouse_supplier_transactions', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('supplier_id');
            }
        });

        Schema::table('supplier_payment_history', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_payment_history', 'batch_id')) {
                $table->string('batch_id')->nullable()->after('supplier_id');
            }
        });
    }
};
