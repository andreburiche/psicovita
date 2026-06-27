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
            $table->string('email_hash', 64)->nullable()->after('email');
            $table->string('phone_hash', 64)->nullable()->after('phone');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE patients MODIFY email TEXT NULL');
            DB::statement('ALTER TABLE patients MODIFY phone TEXT NULL');
        } else {
            Schema::table('patients', function (Blueprint $table) {
                $table->text('email')->nullable()->change();
                $table->text('phone')->nullable()->change();
            });
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->index('email_hash');
            $table->index('phone_hash');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['email_hash']);
            $table->dropIndex(['phone_hash']);
            $table->dropColumn(['email_hash', 'phone_hash']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE patients MODIFY email VARCHAR(255) NULL');
            DB::statement('ALTER TABLE patients MODIFY phone VARCHAR(30) NULL');
        } else {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
                $table->string('phone', 30)->nullable()->change();
            });
        }
    }
};
