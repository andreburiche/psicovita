<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_request_id', 'category'], 'doc_req_files_req_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_files');
    }
};
