<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Prenez rendez-vous en ligne') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Hero Section -->
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Bienvenue chez {{ config('app.name') }}</h1>
                <p class="text-xl text-gray-600 mb-8">Réservez vos soins capillaires en ligne</p>
                <a href="{{ route('bookings.create') }}"
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition duration-200">
                    Réserver maintenant
                    <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="md:flex">
                        <!-- Sélection du service -->
                        <div class="md:w-1/3 p-6 border-r border-gray-200">
                            <h2 class="text-2xl font-bold mb-6">Sélectionnez un service</h2>
                            <div class="space-y-4" id="services-list">
                                @foreach($services as $service)
                                <div class="p-4 border rounded-lg cursor-pointer hover:bg-gray-50 service-item"
                                     data-id="{{ $service->id }}"
                                     data-duration="{{ $service->duration }}">
                                    <h3 class="font-medium">{{ $service->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $service->description }}</p>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-indigo-600 font-medium">{{ $service->formatted_price }}</span>
                                        <span class="text-sm text-gray-500">{{ $service->duration }} min</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Sélection de la date et de l'heure -->
                        <div class="md:w-1/3 p-6 border-r border-gray-200">
                            <h2 class="text-2xl font-bold mb-6">Choisissez une date et une heure</h2>
                            <div id="datepicker" class="mb-6"></div>
                            <div id="time-slots" class="space-y-2">
                                <p class="text-gray-500">Sélectionnez d'abord un service</p>
                            </div>
                        </div>

                        <!-- Résumé de la réservation -->
                        <div class="md:w-1/3 p-6">
                            <h2 class="text-2xl font-bold mb-6">Résumé de la réservation</h2>
                            <div id="booking-summary" class="space-y-4">
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <p class="text-gray-500">Sélectionnez un service, une date et une heure pour voir les détails de votre réservation.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .service-item {
            transition: all 0.2s ease;
        }
        .service-item.selected {
            @apply border-indigo-500 bg-indigo-50;
        }
        .time-slot {
            @apply px-4 py-2 border rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500;
        }
        .time-slot.selected {
            @apply bg-indigo-100 border-indigo-500 text-indigo-700;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation du datepicker
        const datepicker = flatpickr("#datepicker", {
            minDate: "today",
            maxDate: new Date().fp_incr(30), // 30 jours à partir d'aujourd'hui
            locale: "fr",
            dateFormat: "d/m/Y",
            onChange: function(selectedDates, dateStr, instance) {
                checkAvailability();
            }
        });

        // Gestion de la sélection de service
        document.querySelectorAll('.service-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.service-item').forEach(i => {
                    i.classList.remove('selected');
                });
                this.classList.add('selected');
                updateBookingSummary();
                checkAvailability();
            });
        });

        // Fonction pour vérifier les disponibilités
        async function checkAvailability() {
            const selectedService = document.querySelector('.service-item.selected');
            const selectedDate = document.querySelector('#datepicker').value;

            if (!selectedService || !selectedDate) return;

            const timeSlotsContainer = document.getElementById('time-slots');
            timeSlotsContainer.innerHTML = '<p class="text-gray-500">Chargement des créneaux disponibles...</p>';

            try {
                const response = await fetch('{{ route("check.availability") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        date: selectedDate,
                        service_id: selectedService.dataset.id
                    })
                });

                const data = await response.json();
                updateTimeSlots(data.slots || []);
            } catch (error) {
                console.error('Erreur:', error);
                timeSlotsContainer.innerHTML = '<p class="text-red-500">Erreur lors du chargement des créneaux</p>';
            }
        }

        // Mise à jour de l'affichage des créneaux horaires
        function updateTimeSlots(slots) {
            const container = document.getElementById('time-slots');

            if (slots.length === 0) {
                container.innerHTML = '<p class="text-gray-500">Aucun créneau disponible pour cette date.</p>';
                return;
            }

            container.innerHTML = '';
            slots.forEach(slot => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left px-4 py-2 border rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 time-slot';
                button.textContent = slot.time + ' - ' + slot.designer_name;
                button.dataset.time = slot.time;
                button.dataset.designerId = slot.designer_id;
                button.dataset.designerName = slot.designer_name;
                button.addEventListener('click', selectTimeSlot);
                container.appendChild(button);
            });
        }

        // Sélection d'un créneau horaire
        function selectTimeSlot(event) {
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            event.target.classList.add('selected');
            updateBookingSummary();
        }

        // Mise à jour du résumé de la réservation
        function updateBookingSummary() {
            const selectedService = document.querySelector('.service-item.selected');
            const selectedDate = document.querySelector('#datepicker').value;
            const selectedTimeSlot = document.querySelector('.time-slot.selected');

            if (!selectedService) return;

            const summary = document.getElementById('booking-summary');
            let summaryHTML = `
                <div class="p-4 bg-indigo-50 rounded-lg">
                    <h3 class="font-medium text-lg mb-2">${selectedService.querySelector('h3').textContent}</h3>
                    <p class="text-gray-600 mb-2">${selectedService.querySelector('p').textContent}</p>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-700">Durée :</span>
                        <span>${selectedService.dataset.duration} minutes</span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-700">Prix :</span>
                        <span class="text-indigo-600 font-medium">${selectedService.querySelector('span:first-child').textContent}</span>
                    </div>`;

            if (selectedDate && selectedTimeSlot) {
                const [day, month, year] = selectedDate.split('/');
                const date = new Date(year, month - 1, day);
                const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                const formattedDate = date.toLocaleDateString('fr-FR', options);

                summaryHTML += `
                    <div class="border-t border-gray-200 pt-3 mt-3">
                        <h4 class="font-medium mb-2">Détails du rendez-vous</h4>
                        <p class="text-sm text-gray-600">${formattedDate} à ${selectedTimeSlot.dataset.time}</p>
                        <p class="text-sm text-gray-600">Coiffeur: ${selectedTimeSlot.dataset.designerName}</p>
                    </div>`;
            }

            if ({{ Auth::check() ? 'true' : 'false' }}) {
                summaryHTML += `
                    <form action="{{ route('bookings.store') }}" method="POST" class="mt-4">
                        @csrf
                        <input type="hidden" name="service_id" value="${selectedService.dataset.id}">
                        <input type="hidden" name="designer_id" value="${selectedTimeSlot ? selectedTimeSlot.dataset.designerId : ''}">
                        <input type="hidden" name="booking_date" value="${selectedDate ? selectedDate.split('/').reverse().join('-') : ''}">
                        <input type="hidden" name="start_time" value="${selectedTimeSlot ? selectedTimeSlot.dataset.time : ''}">

                        <div class="mt-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Notes (optionnel)</label>
                            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <button type="submit" class="w-full mt-4 bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" ${!selectedTimeSlot ? 'disabled' : ''}>
                            Confirmer la réservation
                        </button>
                    </form>`;
            } else {
                summaryHTML += `
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 mb-2">Pour réserver, veuillez vous connecter ou créer un compte.</p>
                        <div class="space-y-2">
                            <a href="{{ route('login') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Connexion
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Créer un compte
                                </a>
                            @endif
                        </div>
                    </div>`;
            }

            summaryHTML += `</div>`;
            summary.innerHTML = summaryHTML;
        }
    });
    </script>
    @endpush
</x-app-layout>
