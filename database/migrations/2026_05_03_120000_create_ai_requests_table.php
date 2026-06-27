<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->longText('input_text')->nullable();
            $table->longText('output_text')->nullable();
            $table->string('approach', 64)->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('tokens_used')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
