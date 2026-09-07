<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SourateSeeder::class,
            UtilisateurSeeder::class,
            DonneesDemoSeeder::class,
        ]);
    }
}
