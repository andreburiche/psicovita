<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 32);
            $table->string('event_type', 64);
            $table->string('external_id')->nullable();
            $table->string('status', 32)->default('received');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'external_id']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
