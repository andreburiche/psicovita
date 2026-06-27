<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anamnesis_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('anamnesis_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anamnesis_form_id')->constrained('anamnesis_forms')->cascadeOnDelete();
            $table->string('label');
            $table->string('field_key', 64);
            $table->string('field_type', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('required')->default(false);
            $table->string('mask')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['anamnesis_form_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anamnesis_questions');
        Schema::dropIfExists('anamnesis_forms');
    }
};
