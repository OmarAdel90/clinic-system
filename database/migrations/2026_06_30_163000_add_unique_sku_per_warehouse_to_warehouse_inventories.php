<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_inventories', function (Blueprint $table) {
            $table->unique(['warehouse_id', 'sku'], 'warehouse_inventories_warehouse_id_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_inventories', function (Blueprint $table) {
            $table->dropUnique('warehouse_inventories_warehouse_id_sku_unique');
        });
    }
};
