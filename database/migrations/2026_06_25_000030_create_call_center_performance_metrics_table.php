<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_center_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('average_response_time', 10, 2)->nullable();
            $table->integer('total_number_of_leads')->default(0);
            $table->integer('total_converted_leads')->default(0);
            $table->integer('total_reminders')->default(0);
            $table->integer('total_customer_attendance')->default(0);
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center_performance_metrics');
    }
};
