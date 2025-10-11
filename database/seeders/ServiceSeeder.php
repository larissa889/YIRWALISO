<?php
namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Coupe Homme',
                'description' => 'Coupe de cheveux classique pour hommes',
                'price' => 8000,
                'duration' => 30,
            ],
            [
                'name' => 'Coupe Femme',
                'description' => 'Coupe de cheveux personnalisée pour femmes',
                'price' => 12000,
                'duration' => 45,
            ],
            [
                'name' => 'Coupe Enfant',
                'description' => 'Coupe de cheveux pour enfants (moins de 12 ans)',
                'price' => 6000,
                'duration' => 25,
            ],
            [
                'name' => 'Coloration Complète',
                'description' => 'Coloration permanente de tous les cheveux',
                'price' => 25000,
                'duration' => 90,
            ],
            [
                'name' => 'Mèches',
                'description' => 'Coloration partielle avec mèches',
                'price' => 15000,
                'duration' => 60,
            ],
            [
                'name' => 'Brushing',
                'description' => 'Coiffage et brushing professionnel',
                'price' => 10000,
                'duration' => 40,
            ],
            [
                'name' => 'Permanente',
                'description' => 'Permanente pour cheveux bouclés',
                'price' => 20000,
                'duration' => 120,
            ],
            [
                'name' => 'Lissage Brésilien',
                'description' => 'Traitement lissant brésilien',
                'price' => 30000,
                'duration' => 180,
            ],
            [
                'name' => 'Soin Capillaire',
                'description' => 'Soin profond et masque hydratant',
                'price' => 8000,
                'duration' => 30,
            ],
            [
                'name' => 'Rasage Barbe',
                'description' => 'Rasage traditionnel avec serviette chaude',
                'price' => 5000,
                'duration' => 20,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
