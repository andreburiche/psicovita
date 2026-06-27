<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_clinical_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32);
            $table->date('issued_at');
            $table->json('payload');
            $table->timestamps();

            $table->index(['patient_id', 'type']);
            $table->index(['professional_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_clinical_documents');
    }
};
