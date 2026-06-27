<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoria append-only (LGPD / rastreabilidade).
     */
    public function up(): void
    {
        // Substitui schema legado (RBAC) pelo modelo LGPD append-only.
        Schema::dropIfExists('audit_logs');

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 64);
            $table->string('entity', 64);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
