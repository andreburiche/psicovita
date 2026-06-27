<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type');
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('body');
            $table->string('intent_slug')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['support_conversation_id', 'created_at'], 'support_msgs_conv_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
