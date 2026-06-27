<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('institution_name');
            $table->string('institution_type', 32);
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->json('requested_documents');
            $table->text('request_reason')->nullable();
            $table->boolean('authorization_attached')->default(false);
            $table->date('request_date');
            $table->date('expected_return_date')->nullable();
            $table->string('status', 32)->default('pendente');
            $table->text('notes')->nullable();
            $table->timestamp('patient_consent_at')->nullable();
            $table->foreignId('patient_consent_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['professional_id', 'request_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
