<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('support_queue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');
            $table->string('protocol_number')->unique();
            $table->string('source_channel')->default('web_widget');
            $table->boolean('bot_active')->default(true);
            $table->json('bot_context')->nullable();
            $table->text('ai_summary')->nullable();
            $table->decimal('sentiment_score', 4, 3)->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedTinyInteger('priority')->default(3);
            $table->timestamps();

            $table->index(['user_id', 'status'], 'sc_user_status_idx');
            $table->index(['support_queue_id', 'status'], 'sc_queue_status_idx');
            $table->index(['assigned_agent_id', 'status'], 'sc_agent_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_conversations');
    }
};
