<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('assigned_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->json('medical_history')->nullable();
            $table->integer('visit_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
