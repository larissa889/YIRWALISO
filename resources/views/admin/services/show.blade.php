@extends('admin.layouts.app')

@section('title', 'Détails du service')

@section('header', 'Détails du service')

@section('content')
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $service->name }}</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Informations détaillées du service</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.services.edit', $service) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Modifier
                </a>
                <a href="{{ route('admin.services.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Retour à la liste
                </a>
            </div>
        </div>
    </div>
    <div class="px-4 py-5 sm:px-6">
        <div class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
            <div>
                <h4 class="text-sm font-medium text-gray-500">Informations générales</h4>
                <div class="mt-1 text-sm text-gray-900">
                    <p class="font-medium text-lg">{{ $service->name }}</p>
                    <p class="text-gray-500">{{ $service->description }}</p>
                </div>

                <h4 class="mt-6 text-sm font-medium text-gray-500">Tarification</h4>
                <div class="mt-1 text-sm text-gray-900">
                    <p class="text-2xl font-bold text-indigo-600">{{ $service->formatted_price }}</p>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-medium text-gray-500">Durée et statistiques</h4>
                <div class="mt-1 text-sm text-gray-900">
                    <p><strong>Durée :</strong> {{ $service->duration }} minutes</p>
                    <p><strong>Réservations :</strong> {{ $service->bookings_count }}</p>
                    <p><strong>Créé le :</strong> {{ $service->created_at->format('d/m/Y à H:i') }}</p>
                    <p><strong>Dernière mise à jour :</strong> {{ $service->updated_at->format('d/m/Y à H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
