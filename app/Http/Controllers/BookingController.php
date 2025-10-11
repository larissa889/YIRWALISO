<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Designer;
use App\Notifications\BookingConfirmed;
use App\Notifications\NewBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Auth::user()->isAdmin()
            ? Booking::with(['user', 'designer.user', 'service'])->latest()->paginate(15)
            : Auth::user()->bookings()->with(['designer.user', 'service'])->latest()->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    public function create()
    {
        $services = Service::all();
        $designers = Designer::with('user')->get();

        return view('bookings.create', compact('services', 'designers'));
    }

    public function show(Booking $booking)
    {
        // Permettre l'accès public à la réservation via son ID
        return view('bookings.show', compact('booking'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'designer_id' => 'required|exists:designers,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
        ]);

        $service = Service::findOrFail($request->service_id);
        $designer = Designer::findOrFail($request->designer_id);
        $startTime = \Carbon\Carbon::parse($request->start_time);
        $endTime = (clone $startTime)->addMinutes($service->duration);

        // Vérifier la disponibilité
        if (!$designer->isAvailableOn($request->booking_date, $request->start_time, $service->duration)) {
            return back()->with('error', 'Ce créneau n\'est plus disponible. Veuillez en choisir un autre.');
        }

        // Créer une réservation pour invité (sans user_id)
        $booking = Booking::create([
            'user_id' => null, // Réservation d'invité
            'designer_id' => $designer->id,
            'service_id' => $service->id,
            'booking_date' => $request->booking_date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price' => $service->price,
            'status' => 'confirmed',
            'notes' => $request->notes,
            // Informations du client invité
            'guest_name' => $request->customer_name,
            'guest_email' => $request->customer_email,
            'guest_phone' => $request->customer_phone,
        ]);

        // Envoyer les notifications par email au client et au designer
        try {
            // Notification par email au client (si configuration email activée)
            Notification::route('mail', $request->customer_email)
                ->notify(new BookingConfirmed($booking));

            // Notification au designer
            $designer->user->notify(new NewBooking($booking));
        } catch (\Exception $e) {
            \Log::error('Erreur d\'envoi de notification : ' . $e->getMessage());
        }

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Votre réservation a été enregistrée avec succès ! Un email de confirmation vous a été envoyé.');
    }

    public function cancel(Booking $booking)
    {
        // Permettre l'annulation publique via l'email du client
        if (request('email') !== $booking->guest_email) {
            abort(403, 'Email de confirmation requis pour annuler cette réservation.');
        }

        $booking->update(['status' => 'cancelled']);

        return redirect()->back()
            ->with('success', 'La réservation a été annulée avec succès.');
    }
}
