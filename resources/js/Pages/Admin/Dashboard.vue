<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Tableau de bord d'administration
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Bienvenue dans l'administration</h3>
            <p class="mt-2 text-sm text-gray-600">
              Vous êtes connecté en tant qu'administrateur.
            </p>
            
            <!-- Statistiques -->
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
              <!-- Carte Utilisateurs -->
              <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                      <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                      <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                          Utilisateurs
                        </dt>
                        <dd class="flex items-baseline">
                          <div class="text-2xl font-semibold text-gray-900">
                            {{ usersCount }}
                          </div>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
                <div class="bg-gray-50 px-4 py-4 sm:px-6">
                  <div class="text-sm">
                    <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                      Voir tous les utilisateurs
                    </a>
                  </div>
                </div>
              </div>

              <!-- Autres cartes de statistiques peuvent être ajoutées ici -->
            </div>

            <!-- Dernières activités -->
            <div class="mt-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Dernières activités</h3>
              <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200">
                  <li v-for="activity in activities" :key="activity.id">
                    <div class="px-4 py-4 sm:px-6">
                      <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-indigo-600 truncate">
                          {{ activity.description }}
                        </p>
                        <div class="ml-2 flex-shrink-0 flex">
                          <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ activity.time }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Données factices pour l'exemple
const usersCount = ref(0);
const activities = ref([
  { id: 1, description: 'Nouvel utilisateur enregistré', time: 'Il y a 2 minutes' },
  { id: 2, description: 'Mise à jour du profil utilisateur', time: 'Il y a 1 heure' },
  { id: 3, description: 'Connexion administrateur', time: 'Il y a 2 heures' },
]);

// Ici, vous pourriez ajouter des appels API pour récupérer les vraies données
// par exemple :
// axios.get('/api/admin/stats').then(response => {
//   usersCount.value = response.data.usersCount;
// });
</script>
