<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Designer;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Création de l'administrateur
        $admin = User::create([
            'name' => 'Administrateur',
            'email' => 'admin@salon.com',
            'password' => Hash::make('password'),
            'phone' => '+2250102030405',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Création de quelques coiffeurs
        $designers = [
            [
                'name' => 'Marie Dubois',
                'email' => 'marie.dubois@salon.com',
                'specialty' => 'Coloriste experte',
                'bio' => 'Spécialiste en coloration et soins capillaires depuis 8 ans.',
            ],
            [
                'name' => 'Jean Martin',
                'email' => 'jean.martin@salon.com',
                'specialty' => 'Coiffeur homme',
                'bio' => 'Expert en coupes masculines et rasage traditionnel.',
            ],
            [
                'name' => 'Sophie Laurent',
                'email' => 'sophie.laurent@salon.com',
                'specialty' => 'Coiffeuse polyvalente',
                'bio' => 'Passionnée par les coiffures créatives et les tendances actuelles.',
            ],
        ];

        foreach ($designers as $designerData) {
            $designerUser = User::create([
                'name' => $designerData['name'],
                'email' => $designerData['email'],
                'password' => Hash::make('password'),
                'phone' => '+225010203040' . rand(1, 9),
                'role' => 'designer',
                'email_verified_at' => now(),
            ]);

            Designer::create([
                'user_id' => $designerUser->id,
                'specialty' => $designerData['specialty'],
                'bio' => $designerData['bio'],
                'working_days' => json_encode([1, 2, 3, 4, 5]), // Lundi à vendredi
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
            ]);
        }

        // Création de quelques clients
        $clients = [
            [
                'name' => 'Alice Dupont',
                'email' => 'alice.dupont@email.com',
            ],
            [
                'name' => 'Pierre Durand',
                'email' => 'pierre.durand@email.com',
            ],
            [
                'name' => 'Amélie Moreau',
                'email' => 'amelie.moreau@email.com',
            ],
        ];

        foreach ($clients as $clientData) {
            User::create([
                'name' => $clientData['name'],
                'email' => $clientData['email'],
                'password' => Hash::make('password'),
                'phone' => '+225010203040' . rand(1, 9),
                'role' => 'client',
                'email_verified_at' => now(),
            ]);
        }
    }
}
