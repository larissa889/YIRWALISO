@extends('layouts.admin')

@section('title', 'Tableau de bord - Administration')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Tableau de bord</h1>
        <p class="mt-2 text-gray-600">Gérez votre application et accédez aux différentes fonctionnalités</p>
    </div>

    <!-- Cartes API -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <!-- Carte API Utilisateurs -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-primary-100 text-primary-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">API Utilisateurs</h3>
                </div>
                <p class="text-gray-600 mb-4">Gérez les utilisateurs, les rôles et les permissions du système.</p>
                <div class="space-y-3">
                    <a href="{{ route('api.users.index') }}" class="block px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md text-sm font-medium text-gray-700">
                        GET /api/users
                    </a>
                    <a href="{{ route('api.users.create') }}" class="block px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md text-sm font-medium text-gray-700">
                        POST /api/users
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte API Designers -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">API Designers</h3>
                </div>
                <p class="text-gray-600 mb-4">Gérez les profils des designers et leurs portfolios.</p>
                <div class="space-y-3">
                    <a href="{{ route('api.designers.index') }}" class="block px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md text-sm font-medium text-gray-700">
                        GET /api/designers
                    </a>
                    <a href="{{ route('api.designers.portfolio.index') }}" class="block px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md text-sm font-medium text-gray-700">
                        GET /api/designers/portfolio
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte API Commandes -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-yellow-100 text-yellow-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">API Commandes</h3>
                </div>
                <p class="text-gray-600 mb-4">Gérez les commandes et les paiements des clients.</p>
                <div class="space-y-3">
                    <a href="{{ route('api.orders.index') }}" class="block px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md text-sm font-medium text-gray-700">
                        GET /api/orders
                    </a>
                    <a href="{{ route('api.orders.create') }}" class="block px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-md text-sm font-medium text-gray-700">
                        POST /api/orders
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <!-- Carte Utilisateurs -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Utilisateurs</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ App\Models\User::count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    Voir tous les utilisateurs
                </a>
            </div>
        </div>

        <!-- Carte Designers -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Designers</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ App\Models\User::role('designer')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <a href="{{ route('admin.designers.index') }}" class="text-sm font-medium text-green-600 hover:text-green-500">
                    Voir les designers
                </a>
            </div>
        </div>

        <!-- Carte Commandes -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-purple-100 text-purple-600 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Commandes</p>
                        <p class="text-2xl font-semibold text-gray-900">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-purple-600 hover:text-purple-500">
                    Voir les commandes
                </a>
            </div>
        </div>
    </div>

    <!-- Dernières activités -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Activités récentes</h3>
        </div>
        <div class="divide-y divide-gray-200">
            <!-- Exemple d'activité -->
            <div class="px-6 py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name=Admin&background=4F46E5&color=fff" alt="">
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Administrateur</p>
                        <p class="text-sm text-gray-500">a mis à jour les paramètres du site</p>
                        <p class="text-xs text-gray-400">Il y a 5 minutes</p>
                    </div>
                </div>
            </div>
            <!-- Plus d'activités ici -->
        </div>
        <div class="bg-gray-50 px-6 py-3 text-right">
            <a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                Voir toutes les activités
            </a>
        </div>
    </div>
</div>

<!-- Bouton d'administration flottant -->
<div class="fixed bottom-8 right-8">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-center w-14 h-14 rounded-full bg-primary-600 text-white shadow-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </a>
</div>
@endsection
                <h3 class="text-lg font-medium text-gray-900">Activités récentes</h3>
            </div>
            <div class="divide-y divide-gray-200">
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-primary-100 rounded-md p-2">
                            <svg class="h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Nouvel utilisateur enregistré</p>
                            <p class="text-sm text-gray-500">Il y a 2 minutes</p>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-2">
                            <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Nouvelle commande #1234</p>
                            <p class="text-sm text-gray-500">Il y a 15 minutes</p>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-md p-2">
                            <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Commande #1233 en attente</p>
                            <p class="text-sm text-gray-500">Il y a 2 heures</p>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-2">
                            <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Nouveau message de contact</p>
                            <p class="text-sm text-gray-500">Il y a 5 heures</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-4 py-4 sm:px-6 bg-gray-50 text-center">
                <a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                    Voir toutes les activités
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
