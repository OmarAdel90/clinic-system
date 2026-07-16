<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('ad_account_id')->nullable()->after('name');
            $table->string('objective')->nullable()->after('status');
            $table->string('meta_source')->default('manual')->after('objective');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['ad_account_id', 'objective', 'meta_source']);
        });
    }
};
