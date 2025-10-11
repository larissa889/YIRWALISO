<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Booking;
use App\Models\Accommodation;
use App\Models\Activity;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BookingController extends BaseController
{
    /**
     * Display a listing of the bookings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Si l'utilisateur n'est pas admin ou staff, il ne peut voir que ses propres réservations
        if (!$user->can('view_all_bookings')) {
            $query = Booking::where('user_id', $user->id);
        } else {
            $query = Booking::query();
        }

        $query->with(['user', 'accommodation', 'activity']);

        // Filtrage par type de réservation
        if ($request->has('type')) {
            if ($request->type === 'accommodation') {
                $query->whereNotNull('accommodation_id');
            } elseif ($request->type === 'activity') {
                $query->whereNotNull('activity_id');
            }
        }

        // Filtrage par statut
        if ($request->has('status')) {
            $query->where('booking_status', $request->status);
        }

        // Filtrage par statut de paiement
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filtrage par date
        if ($request->has('start_date')) {
            $query->where(function($q) use ($request) {
                $q->where('check_in_date', '>=', $request->start_date)
                  ->orWhere('activity_date', '>=', $request->start_date);
            });
        }

        if ($request->has('end_date')) {
            $query->where(function($q) use ($request) {
                $q->where('check_out_date', '<=', $request->end_date)
                  ->orWhere('activity_date', '<=', $request->end_date);
            });
        }

        // Recherche par référence, nom d'utilisateur ou email
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Tri
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        if (in_array($sortField, ['booking_number', 'total_amount', 'booking_status', 'payment_status', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $bookings = $query->paginate($perPage);

        return $this->sendPaginated($bookings, 'Bookings retrieved successfully');
    }

    /**
     * Store a newly created booking in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Validation des données de base
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:accommodation,activity',
            'accommodation_id' => [
                'required_if:type,accommodation',
                'exists:accommodations,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'accommodation') {
                        $accommodation = Accommodation::find($value);
                        if (!$accommodation || !$accommodation->is_available) {
                            $fail('The selected accommodation is not available.');
                        }
                    }
                },
            ],
            'activity_id' => [
                'required_if:type,activity',
                'exists:activities,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'activity') {
                        $activity = Activity::find($value);
                        if (!$activity || !$activity->is_active) {
                            $fail('The selected activity is not available.');
                        }
                    }
                },
            ],
            'check_in_date' => 'required_if:type,accommodation|date|after_or_equal:today',
            'check_out_date' => 'required_if:type,accommodation|date|after:check_in_date',
            'activity_date' => 'required_if:type,activity|date|after_or_equal:today',
            'number_of_guests' => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:1000',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:cash,credit_card,bank_transfer,mobile_money',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Vérification de la disponibilité
        if ($request->type === 'accommodation') {
            $accommodation = Accommodation::findOrFail($request->accommodation_id);
            
            // Vérifier si l'hébergement est disponible pour les dates sélectionnées
            if (!$accommodation->isAvailableForDates($request->check_in_date, $request->check_out_date)) {
                return $this->sendError('The selected accommodation is not available for the selected dates.', [], 422);
            }
            
            // Vérifier la capacité maximale
            if ($request->number_of_guests > $accommodation->max_occupancy) {
                return $this->sendError('The number of guests exceeds the maximum occupancy of this accommodation.', [], 422);
            }
            
            // Calculer le montant total
            $nights = Carbon::parse($request->check_in_date)->diffInDays(Carbon::parse($request->check_out_date));
            $totalAmount = $accommodation->price_per_night * $nights;
            
            $bookingData = [
                'accommodation_id' => $accommodation->id,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'total_amount' => $totalAmount,
            ];
        } else {
            $activity = Activity::findOrFail($request->activity_id);
            
            // Vérifier si l'activité est disponible pour la date sélectionnée
            if (!$activity->isAvailableForDate($request->activity_date)) {
                return $this->sendError('The selected activity is not available for the selected date.', [], 422);
            }
            
            // Vérifier le nombre de participants
            if ($request->number_of_guests > $activity->max_people) {
                return $this->sendError('The number of participants exceeds the maximum capacity for this activity.', [], 422);
            }
            
            // Calculer le montant total
            $totalAmount = $activity->price_per_person * $request->number_of_guests;
            
            $bookingData = [
                'activity_id' => $activity->id,
                'activity_date' => $request->activity_date,
                'total_amount' => $totalAmount,
            ];
        }

        // Générer un numéro de réservation unique
        $bookingNumber = 'BK-' . strtoupper(uniqid());
        
        // Créer la réservation
        $booking = new Booking([
            'booking_number' => $bookingNumber,
            'user_id' => $user ? $user->id : null,
            'number_of_guests' => $request->number_of_guests,
            'special_requests' => $request->special_requests,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'booking_status' => Booking::STATUS_PENDING,
            'payment_status' => 'pending',
            'paid_amount' => 0,
        ] + $bookingData);

        // Démarrer une transaction pour assurer l'intégrité des données
        DB::beginTransaction();
        
        try {
            $booking->save();
            
            // Si le paiement est effectué immédiatement (par exemple, en ligne)
            if ($request->has('payment_completed') && $request->payment_completed) {
                $payment = $booking->addPayment(
                    $totalAmount,
                    $request->payment_method,
                    $request->transaction_id ?? null,
                    'Paiement initial pour la réservation #' . $bookingNumber
                );
                
                $booking->update([
                    'booking_status' => Booking::STATUS_CONFIRMED,
                    'payment_status' => 'paid',
                    'paid_amount' => $totalAmount,
                ]);
                
                // Envoyer une notification de confirmation
                // $booking->user->notify(new BookingConfirmed($booking));
            }
            
            DB::commit();
            
            return $this->sendResponse($booking->load(['user', 'accommodation', 'activity']), 'Booking created successfully', 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('An error occurred while creating the booking. Please try again.');
        }
    }

    /**
     * Display the specified booking.
     *
     * @param  string  $bookingNumber
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($bookingNumber, Request $request)
    {
        $user = $request->user();
        
        $booking = Booking::with(['user', 'accommodation', 'activity', 'payments'])
            ->where('booking_number', $bookingNumber)
            ->first();

        if (is_null($booking)) {
            return $this->sendNotFound('Booking not found');
        }
        
        // Vérifier les autorisations
        if (!$user || ($user->id !== $booking->user_id && !$user->can('view_all_bookings'))) {
            return $this->sendForbidden('You do not have permission to view this booking');
        }

        return $this->sendResponse($booking, 'Booking retrieved successfully');
    }

    /**
     * Update the specified booking in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $bookingNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $bookingNumber)
    {
        $user = $request->user();
        $booking = Booking::where('booking_number', $bookingNumber)->first();

        if (is_null($booking)) {
            return $this->sendNotFound('Booking not found');
        }
        
        // Vérifier les autorisations
        if ($user->id !== $booking->user_id && !$user->can('edit_bookings')) {
            return $this->sendForbidden('You do not have permission to update this booking');
        }
        
        // Vérifier si la réservation peut être modifiée
        if ($booking->booking_status === Booking::STATUS_CANCELLED) {
            return $this->sendError('Cannot update a cancelled booking', [], 422);
        }
        
        if ($booking->isPast()) {
            return $this->sendError('Cannot update a past booking', [], 422);
        }

        $validator = Validator::make($request->all(), [
            'special_requests' => 'nullable|string|max:1000',
            'customer_name' => 'sometimes|required|string|max:255',
            'customer_email' => 'sometimes|required|email|max:255',
            'customer_phone' => 'sometimes|required|string|max:20',
            'number_of_guests' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($booking) {
                    if ($booking->accommodation_id && $value > $booking->accommodation->max_occupancy) {
                        $fail('The number of guests exceeds the maximum occupancy of this accommodation.');
                    }
                    if ($booking->activity_id && $value > $booking->activity->max_people) {
                        $fail('The number of participants exceeds the maximum capacity for this activity.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $booking->update($request->only([
            'special_requests', 
            'customer_name', 
            'customer_email', 
            'customer_phone',
            'number_of_guests',
        ]));

        return $this->sendResponse($booking->load(['user', 'accommodation', 'activity']), 'Booking updated successfully');
    }

    /**
     * Cancel the specified booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $bookingNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, $bookingNumber)
    {
        $user = $request->user();
        $booking = Booking::where('booking_number', $bookingNumber)->first();

        if (is_null($booking)) {
            return $this->sendNotFound('Booking not found');
        }
        
        // Vérifier les autorisations
        if ($user->id !== $booking->user_id && !$user->can('cancel_bookings')) {
            return $this->sendForbidden('You do not have permission to cancel this booking');
        }
        
        // Vérifier si la réservation peut être annulée
        if ($booking->booking_status === Booking::STATUS_CANCELLED) {
            return $this->sendError('Booking is already cancelled', [], 422);
        }
        
        if ($booking->isPast()) {
            return $this->sendError('Cannot cancel a past booking', [], 422);
        }
        
        // Calculer les frais d'annulation si nécessaire
        $cancellationFee = $this->calculateCancellationFee($booking);
        $refundAmount = $booking->paid_amount - $cancellationFee;
        
        // Démarrer une transaction
        DB::beginTransaction();
        
        try {
            // Annuler la réservation
            $booking->update([
                'booking_status' => Booking::STATUS_CANCELLED,
                'cancellation_reason' => $request->reason ?? 'Annulé par le client',
            ]);
            
            // Rembourser le client si nécessaire
            if ($refundAmount > 0) {
                // Créer un remboursement
                $refund = new Payment([
                    'booking_id' => $booking->id,
                    'amount' => -$refundAmount,
                    'payment_method' => $booking->payments()->latest()->first()->payment_method ?? 'refund',
                    'transaction_id' => 'RFND-' . strtoupper(uniqid()),
                    'status' => 'completed',
                    'notes' => 'Remboursement pour annulation de la réservation #' . $booking->booking_number,
                ]);
                
                $refund->save();
                
                // Mettre à jour le montant payé
                $booking->update([
                    'paid_amount' => $cancellationFee,
                ]);
                
                // Ici, vous pourriez appeler une passerelle de paiement pour effectuer le remboursement
                // Par exemple: $paymentGateway->refund($booking, $refundAmount);
            }
            
            DB::commit();
            
            // Envoyer une notification d'annulation
            // $booking->user->notify(new BookingCancelled($booking, $cancellationFee, $refundAmount));
            
            return $this->sendResponse([
                'booking' => $booking->fresh(['user', 'accommodation', 'activity']),
                'cancellation_fee' => $cancellationFee,
                'refund_amount' => $refundAmount > 0 ? $refundAmount : 0,
            ], 'Booking cancelled successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('An error occurred while cancelling the booking. Please try again.');
        }
    }

    /**
     * Calculate cancellation fee for a booking.
     *
     * @param  \App\Models\Booking  $booking
     * @return float
     */
    protected function calculateCancellationFee(Booking $booking)
    {
        // Politique d'annulation par défaut
        $now = now();
        $checkInDate = $booking->check_in_date ?? $booking->activity_date;
        $daysUntilCheckIn = $now->diffInDays($checkInDate, false);
        
        // Si la date de check-in est passée, pas de remboursement
        if ($daysUntilCheckIn < 0) {
            return $booking->total_amount;
        }
        
        // Remboursement à 100% si annulation plus de 7 jours avant
        if ($daysUntilCheckIn > 7) {
            return 0;
        }
        
        // Remboursement à 50% si annulation entre 2 et 7 jours avant
        if ($daysUntilCheckIn >= 2) {
            return $booking->total_amount * 0.5;
        }
        
        // Pas de remboursement si annulation moins de 48h avant
        return $booking->total_amount;
    }

    /**
     * Get the booking summary for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        
        $query = $user->bookings();
        
        $totalBookings = $query->count();
        $upcomingBookings = $query->upcoming()->count();
        $cancelledBookings = $query->where('booking_status', Booking::STATUS_CANCELLED)->count();
        $totalSpent = $query->sum('total_amount');
        
        return $this->sendResponse([
            'total_bookings' => $totalBookings,
            'upcoming_bookings' => $upcomingBookings,
            'cancelled_bookings' => $cancelledBookings,
            'total_spent' => $totalSpent,
            'favorite_accommodation' => $user->bookings()
                ->select('accommodation_id', DB::raw('count(*) as bookings_count'))
                ->whereNotNull('accommodation_id')
                ->groupBy('accommodation_id')
                ->orderBy('bookings_count', 'desc')
                ->with('accommodation')
                ->first(),
            'favorite_activity' => $user->bookings()
                ->select('activity_id', DB::raw('count(*) as bookings_count'))
                ->whereNotNull('activity_id')
                ->groupBy('activity_id')
                ->orderBy('bookings_count', 'desc')
                ->with('activity')
                ->first(),
        ], 'Booking summary retrieved successfully');
    }

    /**
     * Get the booking calendar for a specific date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calendar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'accommodation_id' => 'nullable|exists:accommodations,id',
            'activity_id' => 'nullable|exists:activities,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $query = Booking::query();
        
        // Filtrer par type de réservation
        if ($request->has('accommodation_id')) {
            $query->where('accommodation_id', $request->accommodation_id);
        } elseif ($request->has('activity_id')) {
            $query->where('activity_id', $request->activity_id);
        }
        
        // Filtrer par date
        $query->where(function($q) use ($request) {
            $q->whereBetween('check_in_date', [$request->start_date, $request->end_date])
              ->orWhereBetween('check_out_date', [$request->start_date, $request->end_date])
              ->orWhereBetween('activity_date', [$request->start_date, $request->end_date]);
        });
        
        // Exclure les réservations annulées
        $query->where('booking_status', '!=', Booking::STATUS_CANCELLED);
        
        // Récupérer les réservations
        $bookings = $query->with(['user', 'accommodation', 'activity'])->get();
        
        // Formater les données pour le calendrier
        $calendarData = $bookings->map(function($booking) {
            $isAccommodation = !is_null($booking->accommodation_id);
            
            return [
                'id' => $booking->id,
                'title' => $isAccommodation 
                    ? 'Réservation #' . $booking->booking_number . ' - ' . ($booking->accommodation->name ?? 'N/A')
                    : 'Activité #' . $booking->booking_number . ' - ' . ($booking->activity->name ?? 'N/A'),
                'start' => $isAccommodation ? $booking->check_in_date : $booking->activity_date,
                'end' => $isAccommodation 
                    ? Carbon::parse($booking->check_out_date)->addDay()->toDateString()
                    : Carbon::parse($booking->activity_date)->addHours(2)->toDateTimeString(),
                'allDay' => $isAccommodation,
                'color' => $isAccommodation ? '#3b82f6' : '#10b981',
                'extendedProps' => [
                    'type' => $isAccommodation ? 'accommodation' : 'activity',
                    'status' => $booking->booking_status,
                    'guests' => $booking->number_of_guests,
                    'amount' => $booking->total_amount,
                    'customer' => [
                        'name' => $booking->customer_name,
                        'email' => $booking->customer_email,
                        'phone' => $booking->customer_phone,
                    ],
                ],
            ];
        });
        
        return $this->sendResponse($calendarData, 'Calendar data retrieved successfully');
    }
}
