<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('clinic_owner_id')
                ->nullable()
                ->after('professional_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('clinic_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('email_hash', 64)->index();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_owner_id', 'email_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_invitations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clinic_owner_id');
        });
    }
};
