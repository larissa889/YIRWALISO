<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Exécuter le seeder.
     */
    public function run(): void
    {
        // Vérifier si l'utilisateur admin existe déjà
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => 'Administrateur',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'), // À changer en production
                'is_admin' => true,
            ]);
            
            $this->command->info('Utilisateur administrateur créé avec succès !');
            $this->command->warn('Email: admin@example.com');
            $this->command->warn('Mot de passe: password');
            $this->command->warn('N\'oubliez pas de changer ces informations de connexion en production !');
        } else {
            $this->command->info('L\'utilisateur administrateur existe déjà.');
        }
    }
}
