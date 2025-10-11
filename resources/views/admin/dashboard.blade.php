@extends('admin.layouts.app')

@section('title', 'Tableau de bord')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
<style>
    .stat-card {
        @apply bg-white overflow-hidden shadow rounded-lg;
    }
    .stat-card-header {
        @apply px-4 py-5 sm:px-6 border-b border-gray-200;
    }
    .stat-card-body {
        @apply px-4 py-5 sm:p-6;
    }
</style>
@endpush

@section('header', 'Tableau de bord')

@section('content')
<div class="pb-5 border-b border-gray-200">
    <h3 class="text-lg leading-6 font-medium text-gray-900">Aperçu des performances</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">Statistiques et indicateurs clés de votre salon de coiffure.</p>
</div>

<div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Total des réservations -->
    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Réservations totales</h3>
        </div>
        <div class="stat-card-body">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total des réservations</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">{{ number_format($stats['total_bookings'], 0, ',', ' ') }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Réservations aujourd'hui -->
    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Aujourd'hui</h3>
        </div>
        <div class="stat-card-body">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Réservations aujourd'hui</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">{{ $stats['today_bookings'] }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenu total -->
    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Revenu total</h3>
        </div>
        <div class="stat-card-body">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Chiffre d'affaires</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients -->
    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Clients & Coiffeurs</h3>
        </div>
        <div class="stat-card-body">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm font-medium text-gray-500">Clients</div>
                    <div class="text-lg font-medium text-gray-900">{{ $stats['total_customers'] }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Coiffeurs</div>
                    <div class="text-lg font-medium text-gray-900">{{ $stats['total_designers'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-5">
    <!-- Graphique des revenus -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Revenus des 6 derniers mois</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <canvas id="revenueChart" height="300"></canvas>
        </div>
    </div>

    <!-- Répartition des réservations -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Répartition des réservations</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <canvas id="bookingsChart" height="300"></canvas>
        </div>
    </div>
</div>

<!-- Dernières réservations -->
<div class="mt-5 bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Dernières réservations</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coiffeur</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Heure</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recentBookings as $booking)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($booking->user->name) }}&background=4F46E5&color=fff" alt="{{ $booking->user->name }}">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $booking->user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $booking->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $booking->service->name }}</div>
                            <div class="text-sm text-gray-500">{{ number_format($booking->price, 0, ',', ' ') }} FCFA</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $booking->designer->user->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $booking->formatted_date }}</div>
                            <div class="text-sm text-gray-500">{{ $booking->formatted_time }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {!! $booking->status_badge !!}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="text-indigo-600 hover:text-indigo-900">Voir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            Aucune réservation récente.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($recentBookings->isNotEmpty())
        <div class="px-6 py-3 border-t border-gray-200 text-right text-sm">
            <a href="{{ route('admin.bookings.index') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Voir toutes les réservations</a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des revenus
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = @json($monthlyRevenue);

    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueData.map(item => item.month),
            datasets: [{
                label: 'Revenus (FCFA)',
                data: revenueData.map(item => item.total),
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenu: ' + context.parsed.y.toLocaleString() + ' FCFA';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' FCFA';
                        }
                    }
                }
            }
        }
    });

    // Graphique des réservations par statut
    const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
    const bookingsData = @json($bookingsByStatus);
    const statusColors = {
        'pending': 'rgba(234, 179, 8, 0.8)',
        'confirmed': 'rgba(16, 185, 129, 0.8)',
        'completed': 'rgba(59, 130, 246, 0.8)',
        'cancelled': 'rgba(239, 68, 68, 0.8)'
    };
    const statusLabels = {
        'pending': 'En attente',
        'confirmed': 'Confirmée',
        'completed': 'Terminée',
        'cancelled': 'Annulée'
    };

    new Chart(bookingsCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(bookingsData).map(key => statusLabels[key] || key),
            datasets: [{
                data: Object.values(bookingsData),
                backgroundColor: Object.keys(bookingsData).map(key => statusColors[key] || '#9CA3AF'),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
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
