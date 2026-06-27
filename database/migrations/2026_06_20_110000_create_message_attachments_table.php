<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->timestamps();

            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
