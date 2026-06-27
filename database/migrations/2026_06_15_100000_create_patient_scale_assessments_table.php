<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('patient_therapeutic_goals');
        Schema::dropIfExists('patient_scale_assessments');

        Schema::create('patient_scale_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('scale_type', 32);
            $table->date('assessed_at');
            $table->unsignedSmallInteger('total_score');
            $table->string('severity', 32);
            $table->string('severity_label');
            $table->boolean('is_risk')->default(false);
            $table->text('responses');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'scale_type', 'assessed_at'], 'pt_scale_assess_patient_type_date_idx');
        });

        Schema::create('patient_therapeutic_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('in_progress');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->date('target_date')->nullable();
            $table->timestamp('achieved_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_therapeutic_goals');
        Schema::dropIfExists('patient_scale_assessments');
    }
};
