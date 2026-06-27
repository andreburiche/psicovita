<?php

use App\Support\ContactHasher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_hash', 64)->nullable()->after('phone');
        });

        DB::table('users')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                if ($row->phone === null || $row->phone === '') {
                    continue;
                }

                $digits = only_digits((string) $row->phone);
                if (strlen($digits) < 10) {
                    continue;
                }

                DB::table('users')->where('id', $row->id)->update([
                    'phone_hash' => ContactHasher::phoneHash($digits),
                ]);
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY phone TEXT NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->text('phone')->nullable()->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index('phone_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone_hash']);
            $table->dropColumn('phone_hash');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY phone VARCHAR(32) NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 32)->nullable()->change();
            });
        }
    }
};
