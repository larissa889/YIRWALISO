<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Designer;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiques générales
        $stats = [
            'total_bookings' => Booking::count(),
            'today_bookings' => Booking::whereDate('booking_date', today())->count(),
            'total_revenue' => Booking::whereIn('status', ['confirmed', 'completed'])->sum('price'),
            'total_customers' => User::where('role', 'client')->count(),
            'total_designers' => Designer::count(),
        ];

        // Dernières réservations
        $recentBookings = Booking::with(['user', 'designer.user', 'service'])
            ->latest()
            ->limit(10)
            ->get();

        // Données pour les graphiques
        $monthlyRevenue = Booking::select(
                DB::raw('DATE_FORMAT(booking_date, "%M %Y") as month'),
                DB::raw('SUM(price) as total')
            )
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('booking_date', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Répartition par statut
        $bookingsByStatus = Booking::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('admin.dashboard', compact(
            'stats',
            'recentBookings',
            'monthlyRevenue',
            'bookingsByStatus'
        ));
    }
}
