<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('cpf_hash', 64)->nullable()->after('cpf');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['professional_id', 'cpf']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE patients MODIFY cpf TEXT NULL');
        } else {
            Schema::table('patients', function (Blueprint $table) {
                $table->text('cpf')->nullable()->change();
            });
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->unique(['professional_id', 'cpf_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['professional_id', 'cpf_hash']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE patients MODIFY cpf VARCHAR(11) NULL');
        } else {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('cpf', 11)->nullable()->change();
            });
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('cpf_hash');
            $table->unique(['professional_id', 'cpf']);
        });
    }
};
