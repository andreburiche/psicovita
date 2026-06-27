<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('channel')->default('internal')->after('body');
            $table->string('external_id')->nullable()->after('channel');
            $table->json('metadata')->nullable()->after('external_id');

            $table->index(['conversation_id', 'created_at']);
            $table->unique('external_id');
        });

        if (Message::query()->exists()) {
            app(ConversationService::class)->backfillFromLegacyMessages();
        }
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversation_id');
            $table->dropColumn(['channel', 'external_id', 'metadata']);
        });
    }
};
