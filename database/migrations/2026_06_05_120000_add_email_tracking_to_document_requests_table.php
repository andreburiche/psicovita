<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->timestamp('last_email_sent_at')->nullable()->after('notes');
            $table->string('last_email_sent_to')->nullable()->after('last_email_sent_at');
            $table->foreignId('last_email_sent_by')->nullable()->after('last_email_sent_to')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropForeign(['last_email_sent_by']);
            $table->dropColumn(['last_email_sent_at', 'last_email_sent_to', 'last_email_sent_by']);
        });
    }
};
