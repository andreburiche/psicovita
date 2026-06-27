<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('cpf', 11)->nullable()->after('birth_date');
            $table->string('address_postal_code', 8)->nullable()->after('cpf');
            $table->string('address_street', 255)->nullable()->after('address_postal_code');
            $table->string('address_number', 30)->nullable()->after('address_street');
            $table->string('address_complement', 120)->nullable()->after('address_number');
            $table->string('address_district', 120)->nullable()->after('address_complement');
            $table->string('address_city', 120)->nullable()->after('address_district');
            $table->string('address_state', 2)->nullable()->after('address_city');

            $table->unique(['professional_id', 'cpf']);
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['professional_id', 'cpf']);
            $table->dropColumn([
                'cpf',
                'address_postal_code',
                'address_street',
                'address_number',
                'address_complement',
                'address_district',
                'address_city',
                'address_state',
            ]);
        });
    }
};
