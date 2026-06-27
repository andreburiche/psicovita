<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_anamneses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('anamnesis_form_id')->constrained('anamnesis_forms')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->longText('answers');
            $table->timestamps();

            $table->unique(['patient_id', 'anamnesis_form_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_anamneses');
    }
};
