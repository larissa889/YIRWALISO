@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Réserver un Rendez-vous</h1>
            <p class="text-gray-600">Choisissez votre service, votre coiffeur et l'heure qui vous convient</p>
        </div>

        <!-- Booking Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('bookings.store') }}" method="POST" id="bookingForm">
                @csrf

                <!-- Customer Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du Client</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nom complet *
                            </label>
                            <input type="text"
                                   id="customer_name"
                                   name="customer_name"
                                   value="{{ old('customer_name') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            @error('customer_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email *
                            </label>
                            <input type="email"
                                   id="customer_email"
                                   name="customer_email"
                                   value="{{ old('customer_email') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            @error('customer_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Téléphone (optionnel)
                            </label>
                            <input type="tel"
                                   id="customer_phone"
                                   name="customer_phone"
                                   value="{{ old('customer_phone') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('customer_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Service Selection -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Choisir un Service</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($services as $service)
                            <label class="relative">
                                <input type="radio"
                                       name="service_id"
                                       value="{{ $service->id }}"
                                       class="sr-only peer service-radio"
                                       {{ old('service_id') == $service->id ? 'checked' : '' }}
                                       required>
                                <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ $service->name }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">{{ $service->description }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-bold text-blue-600">{{ number_format($service->price, 0, ',', ' ') }} €</span>
                                            <p class="text-sm text-gray-500">{{ $service->duration }} min</p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('service_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Designer Selection -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Choisir un Coiffeur</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($designers as $designer)
                            <label class="relative">
                                <input type="radio"
                                       name="designer_id"
                                       value="{{ $designer->id }}"
                                       class="sr-only peer designer-radio"
                                       {{ old('designer_id') == $designer->id ? 'checked' : '' }}
                                       required>
                                <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                                    <div class="flex items-center space-x-3">
                                        @if($designer->user->avatar)
                                            <img src="{{ asset('storage/' . $designer->user->avatar) }}"
                                                 alt="{{ $designer->user->name }}"
                                                 class="w-12 h-12 rounded-full object-cover">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-gray-600 font-medium">
                                                    {{ substr($designer->user->name, 0, 1) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ $designer->user->name }}</h4>
                                            <p class="text-sm text-gray-600">{{ $designer->specialty }}</p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('designer_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date and Time Selection -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Choisir la Date et l'Heure</h3>

                    <!-- Date Selection -->
                    <div class="mb-4">
                        <label for="booking_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date *
                        </label>
                        <input type="date"
                               id="booking_date"
                               name="booking_date"
                               value="{{ old('booking_date') }}"
                               min="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                        @error('booking_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Available Time Slots -->
                    <div id="timeSlotsContainer" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Heure disponible *
                        </label>
                        <div id="timeSlots" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                            <!-- Les créneaux horaires seront générés dynamiquement -->
                        </div>
                        @error('start_time')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Manual Time Input (fallback) -->
                    <div id="manualTimeContainer">
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                            Heure (HH:MM) *
                        </label>
                        <input type="time"
                               id="start_time"
                               name="start_time"
                               value="{{ old('start_time') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                        @error('start_time')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes supplémentaires (optionnel)
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Précisez vos préférences ou demandes spéciales...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-md transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            id="submitBtn">
                        Réserver maintenant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gestion de la sélection du service et du coiffeur
document.querySelectorAll('.service-radio, .designer-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        loadAvailableTimeSlots();
    });
});

// Gestion du changement de date
document.getElementById('booking_date').addEventListener('change', function() {
    loadAvailableTimeSlots();
});

// Fonction pour charger les créneaux horaires disponibles
function loadAvailableTimeSlots() {
    const serviceId = document.querySelector('input[name="service_id"]:checked')?.value;
    const designerId = document.querySelector('input[name="designer_id"]:checked')?.value;
    const date = document.getElementById('booking_date').value;

    if (!serviceId || !designerId || !date) {
        return;
    }

    // Afficher le conteneur des créneaux horaires
    document.getElementById('timeSlotsContainer').classList.remove('hidden');

    // Désactiver le bouton de soumission
    document.getElementById('submitBtn').disabled = true;

    // Charger les créneaux disponibles via AJAX
    fetch(`{{ route('check.availability') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            service_id: serviceId,
            designer_id: designerId,
            booking_date: date
        })
    })
    .then(response => response.json())
    .then(data => {
        const timeSlotsContainer = document.getElementById('timeSlots');
        timeSlotsContainer.innerHTML = '';

        if (data.available_slots && data.available_slots.length > 0) {
            data.available_slots.forEach(slot => {
                const slotElement = document.createElement('label');
                slotElement.className = 'relative';
                slotElement.innerHTML = `
                    <input type="radio" name="start_time" value="${slot.time}" class="sr-only peer" required>
                    <div class="p-2 text-center border-2 border-gray-200 rounded cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                        ${slot.time}
                    </div>
                `;

                slotElement.querySelector('input').addEventListener('change', function() {
                    document.getElementById('submitBtn').disabled = false;
                });

                timeSlotsContainer.appendChild(slotElement);
            });
        } else {
            timeSlotsContainer.innerHTML = '<p class="col-span-full text-center text-gray-500 py-4">Aucun créneau disponible pour cette date.</p>';
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des créneaux:', error);
        document.getElementById('timeSlotsContainer').classList.add('hidden');
    });
}
</script>
@endsection
