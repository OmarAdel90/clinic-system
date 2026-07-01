<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visit_number')->nullable();
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('actual_date')->nullable();
            $table->string('status')->default('scheduled');
            $table->json('supplies_reserved')->nullable();
            $table->decimal('services_cost', 12, 2)->nullable();
            $table->decimal('supplies_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
