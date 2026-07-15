<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('service_name')->nullable()->after('supplies_reserved');
            $table->decimal('service_cost', 12, 2)->nullable()->after('service_name');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['service_name', 'service_cost']);
        });
    }
};
