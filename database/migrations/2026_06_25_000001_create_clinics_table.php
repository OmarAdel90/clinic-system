<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->boolean('provides_medication')->default(false);
            $table->json('departments')->nullable();
            $table->json('doctors')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
