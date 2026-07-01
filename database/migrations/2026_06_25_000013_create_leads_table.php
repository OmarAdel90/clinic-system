<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform')->nullable();
            $table->string('whatsapp_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            $table->string('profile_name')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('lead_status_id')->nullable()->constrained('lead_status')->nullOnDelete();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
