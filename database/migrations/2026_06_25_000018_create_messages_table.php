<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reply_to_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->string('wa_message_id')->nullable();
            $table->string('direction')->nullable();
            $table->string('type')->nullable();
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_caption')->nullable();
            $table->string('media_mime')->nullable();
            $table->integer('media_size')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
