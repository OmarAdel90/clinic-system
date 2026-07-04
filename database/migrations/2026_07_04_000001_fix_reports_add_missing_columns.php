<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('reports', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('lead_id');
            }
            if (!Schema::hasColumn('reports', 'cost_known')) {
                $table->boolean('cost_known')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['lead_id', 'user_id', 'cost_known']);
        });
    }
};
