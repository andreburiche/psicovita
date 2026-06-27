<?php

use App\Enums\UserProfessionalFileCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('professional_bio')->nullable()->after('crp_number');
        });

        Schema::create('user_professional_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 32)->default(UserProfessionalFileCategory::Other->value);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_professional_files');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('professional_bio');
        });
    }
};
