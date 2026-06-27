<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('document_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('category', 32);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->date('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'received_at']);
            $table->index('document_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_documents');
    }
};
