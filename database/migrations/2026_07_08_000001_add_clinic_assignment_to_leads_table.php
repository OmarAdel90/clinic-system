<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'clinic_id')) {
                $table->foreignId('clinic_id')
                    ->nullable()
                    ->after('campaign_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('leads', 'clinic_assigned_by')) {
                $table->foreignId('clinic_assigned_by')
                    ->nullable()
                    ->after('clinic_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('leads', 'clinic_assigned_at')) {
                $table->timestamp('clinic_assigned_at')
                    ->nullable()
                    ->after('clinic_assigned_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'clinic_assigned_at')) {
                $table->dropColumn('clinic_assigned_at');
            }

            if (Schema::hasColumn('leads', 'clinic_assigned_by')) {
                $table->dropConstrainedForeignId('clinic_assigned_by');
            }

            if (Schema::hasColumn('leads', 'clinic_id')) {
                $table->dropConstrainedForeignId('clinic_id');
            }
        });
    }
};
