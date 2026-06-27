<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['document_request_id', 'created_at'], 'doc_req_access_logs_req_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_access_logs');
    }
};
