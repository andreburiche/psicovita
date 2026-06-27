<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        if (Schema::hasTable('support_messages_legacy')) {
            Schema::drop('support_messages');
            Schema::rename('support_messages_legacy', 'support_messages');
        }

        if ($this->foreignKeyTargetsLegacy('support_messages', 'support_conversation_id')) {
            $this->rebuildSupportMessagesTable();
        }

        if (Schema::hasTable('chatbot_logs')
            && $this->foreignKeyTargetsLegacy('chatbot_logs', 'support_conversation_id')) {
            $this->rebuildChatbotLogsTable();
        }

        if (Schema::hasTable('support_conversations_legacy')) {
            Schema::drop('support_conversations_legacy');
        }
    }

    public function down(): void
    {
        // Reparo de integridade; não há rollback seguro.
    }

    private function foreignKeyTargetsLegacy(string $table, string $column): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach (DB::select("PRAGMA foreign_key_list({$table})") as $foreignKey) {
            if (($foreignKey->from ?? null) === $column
                && ($foreignKey->table ?? null) === 'support_conversations_legacy') {
                return true;
            }
        }

        return false;
    }

    private function rebuildSupportMessagesTable(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('support_messages')) {
            Schema::rename('support_messages', 'support_messages_legacy');
        }

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type');
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('body');
            $table->string('intent_slug')->nullable();
            $table->json('metadata')->nullable();
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->index(['support_conversation_id', 'created_at'], 'sm_conversation_created_idx');
            $table->unique('external_id', 'sm_external_id_unique');
        });

        if (Schema::hasTable('support_messages_legacy')) {
            DB::statement('
                INSERT INTO support_messages (
                    id, support_conversation_id, sender_type, sender_user_id, body,
                    intent_slug, metadata, external_id, created_at, updated_at
                )
                SELECT
                    id, support_conversation_id, sender_type, sender_user_id, body,
                    intent_slug, metadata, external_id, created_at, updated_at
                FROM support_messages_legacy
            ');

            Schema::drop('support_messages_legacy');
        }

        Schema::enableForeignKeyConstraints();
    }

    private function rebuildChatbotLogsTable(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('chatbot_logs', 'chatbot_logs_legacy');

        Schema::create('chatbot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['support_conversation_id', 'created_at'], 'cl_conversation_created_idx');
        });

        if (Schema::hasTable('chatbot_logs_legacy')) {
            DB::statement('
                INSERT INTO chatbot_logs (id, support_conversation_id, event, payload, created_at)
                SELECT id, support_conversation_id, event, payload, created_at
                FROM chatbot_logs_legacy
            ');

            Schema::drop('chatbot_logs_legacy');
        }

        Schema::enableForeignKeyConstraints();
    }
};
