<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Appel des seeders
        $this->call([
            AdminUserSeeder::class,
            // Ajoutez d'autres seeders ici si nécessaire
        ]);
    }
}
