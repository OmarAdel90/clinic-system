<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['visit_id']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
        });
    }
};
