<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_participants', function (Blueprint $table) {
            $table->unique(['therapy_session_id', 'patient_id'], 'session_participants_session_patient_unique');
        });
    }

    public function down(): void
    {
        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropUnique('session_participants_session_patient_unique');
        });
    }
};
