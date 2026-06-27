<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_flows', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('chatbot_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_flow_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('label');
            $table->json('training_phrases');
            $table->string('route_action')->default('reply');
            $table->foreignId('target_queue_id')->nullable()->constrained('support_queues')->nullOnDelete();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['chatbot_flow_id', 'slug']);
        });

        Schema::create('chatbot_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_intent_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->default('pt_BR');
            $table->text('body_template');
            $table->json('quick_replies')->nullable();
            $table->timestamps();
        });

        Schema::create('chatbot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['support_conversation_id', 'created_at'], 'chatbot_logs_conv_created_idx');
        });

        Schema::create('conversation_protocol_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_protocol_sequences');
        Schema::dropIfExists('chatbot_logs');
        Schema::dropIfExists('chatbot_responses');
        Schema::dropIfExists('chatbot_intents');
        Schema::dropIfExists('chatbot_flows');
    }
};
