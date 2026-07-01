<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmaceuticals', function (Blueprint $table) {
            $table->string('SKU')->primary();
            $table->string('name');
            $table->string('arabic_name')->nullable();
            $table->string('photo')->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('attribute')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmaceuticals');
    }
};
