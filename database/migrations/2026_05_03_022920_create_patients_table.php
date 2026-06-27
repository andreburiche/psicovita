<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pacientes isolados por profissional (tenant).
     * notes: criptografado no modelo (cast encrypted).
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 32)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['professional_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
