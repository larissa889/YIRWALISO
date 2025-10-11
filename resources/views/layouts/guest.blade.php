<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'YIRWALISO') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('styles')
</head>
<body class="h-full">
    <div class="min-h-screen flex">
        <!-- Côté gauche avec image -->
        <div class="hidden lg:block relative w-0 flex-1">
            <div class="absolute inset-0 bg-gradient-to-br from-primary-600 to-primary-800">
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1469&q=80')] bg-cover bg-center mix-blend-overlay"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
            </div>
            <div class="relative h-full flex flex-col justify-between p-12">
                <div>
                    <h2 class="text-3xl font-bold text-white">YIRWALISO</h2>
                    <p class="mt-2 text-lg text-gray-200">Votre partenaire de confiance pour des solutions innovantes</p>
                </div>
                <div class="mt-auto">
                    <blockquote class="text-white">
                        <p class="text-xl font-medium">
                            "La simplicité est la sophistication suprême."
                        </p>
                        <footer class="mt-2 text-gray-200">Léonard de Vinci</footer>
                    </blockquote>
                </div>
            </div>
        </div>

        <!-- Côté droit avec le formulaire -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-bold text-gray-900">
                        @if (request()->routeIs('login'))
                            Connexion
                        @elseif (request()->routeIs('register'))
                            Créer un compte
                        @elseif (request()->routeIs('password.request'))
                            Réinitialiser le mot de passe
                        @else
                            {{ config('app.name') }}
                        @endif
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        @if (request()->routeIs('login'))
                            Ou 
                            <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-500">
                                créez un compte
                            </a>
                        @elseif (request()->routeIs('register'))
                            Déjà inscrit ?
                            <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-500">
                                Connectez-vous
                            </a>
                        @endif
                    </p>
                </div>

                <div class="mt-8">
                    <div class="mt-6">
                        {{ $slot }}
                    </div>
                </div>

                <div class="mt-8 text-center text-sm text-gray-500">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
