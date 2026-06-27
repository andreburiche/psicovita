<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('landing_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('site_settings')->insert([
            [
                'key' => 'social_links',
                'value' => json_encode([
                    'instagram' => '',
                    'linkedin' => '',
                    'facebook' => '',
                    'youtube' => '',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'whatsapp',
                'value' => json_encode([
                    'phone' => '',
                    'message' => 'Olá! Gostaria de saber mais sobre a plataforma.',
                    'enabled' => true,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $partners = [
            'REDE NORTE',
            'CLÍNICA HORIZONTE',
            'MindWell',
            'pulsecare',
            'UNISAÚDE',
        ];

        foreach ($partners as $i => $name) {
            DB::table('landing_partners')->insert([
                'name' => $name,
                'url' => null,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_partners');
        Schema::dropIfExists('site_settings');
    }
};
