<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DevUserSeeder::class);
        $this->call(PsiConectaDemoSeeder::class);
        $this->call(ChatbotSeeder::class);
    }
}
