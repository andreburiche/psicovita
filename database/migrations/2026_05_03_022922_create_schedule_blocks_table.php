<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloqueios de agenda por profissional.
     */
    public function up(): void
    {
        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->date('block_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['professional_id', 'block_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_blocks');
    }
};
