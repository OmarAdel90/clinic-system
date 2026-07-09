<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        if (Schema::hasColumn('messages', 'contact_id') && !Schema::hasColumn('messages', 'lead_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->renameColumn('contact_id', 'lead_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        if (Schema::hasColumn('messages', 'lead_id') && !Schema::hasColumn('messages', 'contact_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->renameColumn('lead_id', 'contact_id');
            });
        }
    }
};
