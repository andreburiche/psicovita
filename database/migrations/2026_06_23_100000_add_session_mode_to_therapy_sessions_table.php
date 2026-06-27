<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('therapy_sessions', function (Blueprint $table) {
            $table->string('session_mode', 32)->default('individual')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('therapy_sessions', function (Blueprint $table) {
            $table->dropColumn('session_mode');
        });
    }
};
