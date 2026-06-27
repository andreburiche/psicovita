<?php

use App\Support\ContactHasher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_hash', 64)->nullable()->after('email');
        });

        DB::table('users')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                if ($row->email === null || $row->email === '') {
                    continue;
                }

                DB::table('users')->where('id', $row->id)->update([
                    'email_hash' => ContactHasher::emailHash(Str::lower(trim((string) $row->email))),
                ]);
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
            Schema::enableForeignKeyConstraints();
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY email TEXT NOT NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->text('email')->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email_hash']);
            $table->dropColumn('email_hash');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
