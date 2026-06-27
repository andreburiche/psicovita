<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapy_session_id')->nullable()->constrained('therapy_sessions')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 32)->default('pending')->index();
            $table->string('payment_method', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
