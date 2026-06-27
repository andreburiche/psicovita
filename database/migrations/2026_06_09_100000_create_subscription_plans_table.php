<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('name');
            $table->unsignedInteger('price_cents')->default(0);
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->unsignedInteger('max_patients')->nullable();
            $table->json('features');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
