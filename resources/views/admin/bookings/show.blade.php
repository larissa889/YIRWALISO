@extends('admin.layouts.app')

@section('title', 'Détails de la réservation #' . $booking->id)

@section('header', 'Détails de la réservation #' . $booking->id)

@section('content')
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Réservation #{{ $booking->id }}</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Détails complets de la réservation</p>
            </div>
            <div class="flex space-x-3">
                <form action="{{ route('admin.bookings.status.update', $booking) }}" method="POST" class="flex items-center">
                    @csrf
                    @method('PATCH')
                    <select name="status" onchange="this.form.submit()" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="pending" {{ $booking->status === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="confirmed" {{ $booking->status === 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                        <option value="completed" {{ $booking->status === 'completed' ? 'selected' : '' }}>Terminée</option>
                        <option value="cancelled" {{ $booking->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                    </select>
                </form>
            </div>
        </div>
    </div>
    <div class="px-4 py-5 sm:px-6">
        <div class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
            <div>
                <h4 class="text-sm font-medium text-gray-500">Informations client</h4>
                <div class="mt-1 text-sm text-gray-900">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($booking->user->name) }}&background=4F46E5&color=fff" alt="{{ $booking->user->name }}">
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">{{ $booking->user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $booking->user->email }}</div>
                            <div class="text-sm text-gray-500">{{ $booking->user->phone ?? 'Aucun téléphone' }}</div>
                        </div>
                    </div>
                </div>

                <h4 class="mt-6 text-sm font-medium text-gray-500">Détails du service</h4>
                <div class="mt-1 text-sm text-gray-900">
                    <p class="font-medium">{{ $booking->service->name }}</p>
                    <p class="text-gray-500">{{ $booking->service->description }}</p>
                    <p class="mt-2">{{ $booking->service->duration }} minutes • {{ number_format($booking->service->price, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-medium text-gray-500">Date et heure</h4>
                <div class="mt-1 text-sm text-gray-900">
                    <p>{{ $booking->booking_date->isoFormat('dddd D MMMM YYYY') }}</p>
                    <p class="text-gray-900 font-medium">{{ $booking->formatted_time }}</p>
                </div>

                <h4 class="mt-6 text-sm font-medium text-gray-500">Coiffeur assigné</h4>
                <div class="mt-1 flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-indigo-600 font-medium">
                            {{ substr($booking->designer->user->name, 0, 1) }}
                        </span>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ $booking->designer->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $booking->designer->specialty }}</div>
                    </div>
                </div>

                @if($booking->notes)
                    <h4 class="mt-6 text-sm font-medium text-gray-500">Notes</h4>
                    <div class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">
                        {{ $booking->notes }}
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500">Créé le {{ $booking->created_at->format('d/m/Y à H:i') }}</p>
                    <p class="text-sm text-gray-500">Dernière mise à jour le {{ $booking->updated_at->format('d/m/Y à H:i') }}</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
