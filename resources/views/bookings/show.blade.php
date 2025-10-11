<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Réservation #' . $booking->id) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Informations principales -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Détails de la réservation</h3>
                                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Service :</span>
                                        <span class="font-medium">{{ $booking->service->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Date :</span>
                                        <span class="font-medium">{{ $booking->formatted_date }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Heure :</span>
                                        <span class="font-medium">{{ $booking->formatted_time }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Coiffeur :</span>
                                        <span class="font-medium">{{ $booking->designer->user->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Prix :</span>
                                        <span class="font-medium text-indigo-600">{{ $booking->service->formatted_price }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Statut :</span>
                                        <span class="font-medium">{!! $booking->status_badge !!}</span>
                                    </div>
                                </div>
                            </div>

                            @if($booking->notes)
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Notes</h3>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-gray-700">{{ $booking->notes }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Informations du coiffeur -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Votre coiffeur</h3>
                                <div class="bg-indigo-50 rounded-lg p-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0 h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-indigo-600 font-bold text-lg">
                                                {{ substr($booking->designer->user->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-medium text-gray-900">{{ $booking->designer->user->name }}</h4>
                                            <p class="text-indigo-600">{{ $booking->designer->specialty }}</p>
                                            @if($booking->designer->bio)
                                                <p class="text-sm text-gray-600 mt-2">{{ Str::limit($booking->designer->bio, 100) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions disponibles -->
                            <div class="space-y-3">
                                @if(in_array($booking->status, ['pending', 'confirmed']))
                                    <form action="{{ route('bookings.cancel', $booking) }}" method="POST" class="inline-block w-full">
                                        @csrf
                                        @method('POST')
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')">
                                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Annuler la réservation
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('bookings.index') }}" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Retour à mes réservations
                                </a>

                                <a href="{{ route('home') }}" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Nouvelle réservation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
