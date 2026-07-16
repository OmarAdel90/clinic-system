<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('ad_account_name')->nullable()->after('ad_account_id');
            $table->decimal('spend', 14, 2)->nullable()->after('meta_source');
            $table->unsignedBigInteger('impressions')->nullable()->after('spend');
            $table->unsignedBigInteger('clicks')->nullable()->after('impressions');
            $table->decimal('ctr', 10, 4)->nullable()->after('clicks');
            $table->decimal('cpc', 14, 4)->nullable()->after('ctr');
            $table->decimal('results', 14, 2)->nullable()->after('cpc');
            $table->string('result_label')->nullable()->after('results');
            $table->json('ad_sets')->nullable()->after('result_label');
            $table->timestamp('metrics_synced_at')->nullable()->after('ad_sets');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'ad_account_name',
                'spend',
                'impressions',
                'clicks',
                'ctr',
                'cpc',
                'results',
                'result_label',
                'ad_sets',
                'metrics_synced_at',
            ]);
        });
    }
};
