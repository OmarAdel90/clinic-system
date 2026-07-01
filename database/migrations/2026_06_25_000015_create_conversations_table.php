<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_message_time')->nullable();
            $table->timestamp('last_message_time')->nullable();
            $table->string('platform')->nullable();
            $table->string('status')->nullable();
            $table->string('lead_status')->nullable();
            $table->integer('unread_amount')->default(0);
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
