<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_conversations')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->upgradeSqlite();

            return;
        }

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function upgradeSqlite(): void
    {
        if (! Schema::hasTable('support_conversations')) {
            return;
        }

        $column = collect(DB::select('PRAGMA table_info(support_conversations)'))
            ->firstWhere('name', 'user_id');

        if ($column !== null && (int) $column->notnull === 0) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::rename('support_conversations', 'support_conversations_legacy');

        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('support_queue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');
            $table->string('protocol_number')->unique();
            $table->string('source_channel')->default('web_widget');
            $table->string('whatsapp_phone_hash', 64)->nullable();
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
            $table->index('whatsapp_phone_hash', 'sc_whatsapp_hash_idx');
        });

        DB::statement('
            INSERT INTO support_conversations (
                id, user_id, support_queue_id, assigned_agent_id, status, protocol_number,
                source_channel, whatsapp_phone_hash, bot_active, bot_context, ai_summary,
                sentiment_score, first_response_at, resolved_at, priority, created_at, updated_at
            )
            SELECT
                id, user_id, support_queue_id, assigned_agent_id, status, protocol_number,
                source_channel, whatsapp_phone_hash, bot_active, bot_context, ai_summary,
                sentiment_score, first_response_at, resolved_at, priority, created_at, updated_at
            FROM support_conversations_legacy
        ');

        Schema::drop('support_conversations_legacy');
        Schema::enableForeignKeyConstraints();
    }
};
