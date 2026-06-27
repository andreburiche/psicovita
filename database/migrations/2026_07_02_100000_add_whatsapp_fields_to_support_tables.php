<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->string('whatsapp_phone_hash', 64)->nullable()->after('source_channel');
            $table->index('whatsapp_phone_hash');
        });

        Schema::table('support_messages', function (Blueprint $table) {
            $table->string('external_id')->nullable()->unique()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn('external_id');
        });

        Schema::table('support_conversations', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_phone_hash']);
            $table->dropColumn('whatsapp_phone_hash');
        });
    }
};
