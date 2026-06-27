<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('therapy_sessions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });

        Schema::table('therapy_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->change();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('therapy_sessions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });

        Schema::table('therapy_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable(false)->change();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }
};
