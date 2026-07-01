<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('SSN')->nullable();
            $table->string('location')->nullable();
            $table->string('phone_number')->nullable();
            $table->decimal('basic_salary', 10, 2)->nullable();
            $table->decimal('commission', 10, 2)->nullable();
            $table->timestamp('hired_at')->nullable();
            $table->json('accessible_clinics')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center');
    }
};
