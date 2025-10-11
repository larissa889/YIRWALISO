<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund as StripeRefund;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Carbon\Carbon;

class PaymentController extends BaseController
{
    /**
     * Initialize Stripe with the API key.
     */
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    /**
     * Get payment methods.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethods(Request $request)
    {
        $methods = PaymentMethod::where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(function($method) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'code' => $method->code,
                    'description' => $method->description,
                    'icon' => $method->icon,
                    'is_online' => $method->is_online,
                    'requires_online_processing' => $method->requires_online_processing,
                ];
            });

        return $this->sendResponse($methods, 'Payment methods retrieved successfully');
    }

    /**
     * Process a payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_token' => 'required_if:payment_method_id,1', // Requis pour les cartes de crédit
            'save_payment_method' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $user = $request->user();
        $booking = Booking::findOrFail($request->booking_id);
        $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);

        // Vérifier que l'utilisateur a le droit d'effectuer un paiement pour cette réservation
        if ($user->id !== $booking->user_id && !$user->can('manage_bookings')) {
            return $this->sendForbidden('You do not have permission to make payments for this booking');
        }

        // Vérifier que le montant est valide
        $remainingAmount = $booking->total_amount - $booking->paid_amount;
        if ($request->amount > $remainingAmount) {
            return $this->sendError('The payment amount exceeds the remaining balance', [], 422);
        }

        // Traitement du paiement en fonction de la méthode
        try {
            $paymentData = [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $request->amount,
                'currency' => 'XOF',
                'status' => 'pending',
                'transaction_id' => null,
                'payment_details' => [],
                'notes' => $request->notes,
            ];

            // Créer un enregistrement de paiement
            $payment = new Payment($paymentData);
            
            // Traiter le paiement en fonction de la méthode
            switch ($paymentMethod->code) {
                case 'credit_card':
                    $result = $this->processCreditCardPayment($request, $payment);
                    break;
                case 'mobile_money':
                    $result = $this->processMobileMoneyPayment($request, $payment);
                    break;
                case 'bank_transfer':
                    $result = $this->processBankTransfer($request, $payment);
                    break;
                case 'cash':
                    $result = $this->processCashPayment($request, $payment);
                    break;
                default:
                    return $this->sendError('Unsupported payment method', [], 400);
            }

            if (!$result['success']) {
                return $this->sendError($result['message'], $result['data'] ?? [], 400);
            }

            // Mettre à jour le paiement avec les détails de la transaction
            $payment->fill($result['data']);
            $payment->save();

            // Mettre à jour le montant payé de la réservation
            $booking->paid_amount += $payment->amount;
            
            // Si le montant payé est supérieur ou égal au montant total, marquer comme payé
            if ($booking->paid_amount >= $booking->total_amount) {
                $booking->payment_status = 'paid';
                $booking->payment_completed_at = now();
                
                // Si c'est un hébergement, vérifier la disponibilité avant de confirmer
                if ($booking->accommodation_id) {
                    if (!$booking->accommodation->isAvailableForDates($booking->check_in_date, $booking->check_out_date, $booking->id)) {
                        // Rembourser le paiement si l'hébergement n'est plus disponible
                        $this->processRefund($payment, 'Hébergement non disponible pour les dates sélectionnées');
                        return $this->sendError('The accommodation is no longer available for the selected dates', [], 422);
                    }
                }
                
                $booking->booking_status = 'confirmed';
                
                // Envoyer une notification de confirmation
                // $booking->user->notify(new BookingConfirmed($booking));
            }
            
            $booking->save();

            // Si l'utilisateur souhaite enregistrer sa carte pour des paiements futurs
            if ($request->save_payment_method && $paymentMethod->code === 'credit_card' && $user->stripe_id) {
                $this->savePaymentMethod($user, $request->payment_token);
            }

            return $this->sendResponse(
                [
                    'payment' => $payment->load('paymentMethod'),
                    'booking' => $booking,
                ], 
                'Payment processed successfully'
            );

        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'booking_id' => $booking->id,
            ]);

            return $this->sendServerError('An error occurred while processing your payment. Please try again.');
        }
    }

    /**
     * Process a credit card payment using Stripe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return array
     */
    protected function processCreditCardPayment(Request $request, Payment $payment)
    {
        try {
            // Créer un paiement Stripe
            $intent = PaymentIntent::create([
                'amount' => $payment->amount * 100, // Convertir en centimes
                'currency' => strtolower($payment->currency),
                'payment_method' => $request->payment_token,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'description' => 'Paiement pour la réservation #' . $payment->booking->booking_number,
                'metadata' => [
                    'booking_id' => $payment->booking_id,
                    'user_id' => $payment->user_id,
                ],
                'return_url' => config('app.frontend_url') . '/payment/confirm',
            ]);

            // Vérifier si le paiement nécessite une authentification supplémentaire
            if ($intent->status === 'requires_action' && $intent->next_action->type === 'use_stripe_sdk') {
                return [
                    'success' => true,
                    'requires_action' => true,
                    'payment_intent_client_secret' => $intent->client_secret,
                    'data' => [
                        'status' => 'requires_action',
                        'transaction_id' => $intent->id,
                        'payment_details' => [
                            'payment_intent_id' => $intent->id,
                            'client_secret' => $intent->client_secret,
                            'requires_action' => true,
                        ],
                    ],
                ];
            }

            // Si le paiement est réussi
            if ($intent->status === 'succeeded') {
                return [
                    'success' => true,
                    'data' => [
                        'status' => 'completed',
                        'transaction_id' => $intent->id,
                        'payment_details' => [
                            'payment_intent_id' => $intent->id,
                            'amount_received' => $intent->amount_received / 100,
                            'currency' => strtoupper($intent->currency),
                            'payment_method' => $intent->payment_method,
                        ],
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment could not be processed',
                'data' => [
                    'status' => 'failed',
                    'payment_details' => [
                        'error' => 'Payment was not successful',
                    ],
                ],
            ];

        } catch (CardException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'status' => 'failed',
                    'payment_details' => [
                        'error' => $e->getError()->message,
                        'code' => $e->getError()->code,
                    ],
                ],
            ];
        } catch (InvalidRequestException $e) {
            return [
                'success' => false,
                'message' => 'Invalid payment details',
                'data' => [
                    'status' => 'failed',
                    'payment_details' => [
                        'error' => $e->getMessage(),
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred while processing your payment',
                'data' => [
                    'status' => 'failed',
                    'payment_details' => [
                        'error' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }

    /**
     * Process a mobile money payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return array
     */
    protected function processMobileMoneyPayment(Request $request, Payment $payment)
    {
        // Intégration avec une API de paiement mobile money (ex: Flutterwave, Paystack, etc.)
        // Ceci est un exemple simplifié
        
        try {
            // Générer une référence de transaction unique
            $transactionId = 'MM-' . strtoupper(uniqid());
            
            // Ici, vous appelleriez l'API de paiement mobile money
            // $response = $mobileMoneyService->initiatePayment([
            //     'amount' => $payment->amount,
            //     'currency' => $payment->currency,
            //     'phone' => $request->phone_number,
            //     'email' => $payment->booking->customer_email,
            //     'reference' => $transactionId,
            //     'description' => 'Paiement pour la réservation #' . $payment->booking->booking_number,
            // ]);
            
            // Simuler une réponse réussie pour l'exemple
            $response = [
                'status' => 'pending',
                'transaction_id' => $transactionId,
                'payment_url' => null, // URL pour compléter le paiement si nécessaire
                'message' => 'Veuillez confirmer le paiement sur votre téléphone',
            ];
            
            return [
                'success' => true,
                'data' => [
                    'status' => 'pending',
                    'transaction_id' => $transactionId,
                    'payment_details' => $response,
                ],
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement mobile money',
                'data' => [
                    'status' => 'failed',
                    'payment_details' => [
                        'error' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }

    /**
     * Process a bank transfer payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return array
     */
    protected function processBankTransfer(Request $request, Payment $payment)
    {
        // Générer une référence de virement unique
        $reference = 'VIR-' . strtoupper(uniqid());
        
        // Envoyer les instructions de virement par email
        // $payment->booking->user->notify(new BankTransferInstructions($payment, $reference));
        
        return [
            'success' => true,
            'data' => [
                'status' => 'pending',
                'transaction_id' => $reference,
                'payment_details' => [
                    'reference' => $reference,
                    'bank_name' => 'Banque XYZ',
                    'account_name' => 'YIRWALISO',
                    'account_number' => 'CI059 01010 12345678901 01',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'instructions' => 'Veuillez effectuer un virement avec la référence fournie.',
                ],
            ],
        ];
    }

    /**
     * Process a cash payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return array
     */
    protected function processCashPayment(Request $request, Payment $payment)
    {
        // Pour les paiements en espèces, marquer comme en attente de confirmation
        return [
            'success' => true,
            'data' => [
                'status' => 'pending_verification',
                'transaction_id' => 'CASH-' . strtoupper(uniqid()),
                'payment_details' => [
                    'method' => 'cash',
                    'instructions' => 'Veuillez vous présenter à notre bureau pour effectuer le paiement en espèces.',
                ],
            ],
        ];
    }

    /**
     * Save a payment method for future use.
     *
     * @param  \App\Models\User  $user
     * @param  string  $paymentMethodId
     * @return void
     */
    protected function savePaymentMethod($user, $paymentMethodId)
    {
        try {
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }
            
            // Ajouter la méthode de paiement au client Stripe
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $user->stripe_id]);
            
            // Mettre à jour la méthode de paiement par défaut si c'est la première
            if (!$user->hasPaymentMethod()) {
                $user->updateDefaultPaymentMethod($paymentMethodId);
                $user->updateDefaultPaymentMethodFromStripe();
            }
            
            // Ajouter la méthode de paiement à la base de données
            $user->addPaymentMethod($paymentMethod);
            
        } catch (\Exception $e) {
            Log::error('Failed to save payment method: ' . $e->getMessage());
            // Ne pas échouer le paiement si l'enregistrement de la carte échoue
        }
    }

    /**
     * Process a refund for a payment.
     *
     * @param  \App\Models\Payment  $payment
     * @param  string  $reason
     * @return bool
     */
    protected function processRefund(Payment $payment, $reason = '')
    {
        try {
            // Vérifier si le paiement peut être remboursé
            if ($payment->status !== 'completed' || $payment->amount <= 0) {
                Log::warning('Cannot refund payment ' . $payment->id . ': invalid status or amount');
                return false;
            }
            
            // Si c'est un paiement Stripe, traiter le remboursement via Stripe
            if ($payment->paymentMethod->code === 'credit_card' && $payment->transaction_id) {
                $refund = StripeRefund::create([
                    'payment_intent' => $payment->transaction_id,
                    'amount' => $payment->amount * 100, // Convertir en centimes
                    'reason' => 'requested_by_customer',
                    'metadata' => [
                        'booking_id' => $payment->booking_id,
                        'reason' => $reason,
                    ],
                ]);
                
                $refundStatus = $refund->status;
                $transactionId = $refund->id;
            } else {
                // Pour les autres méthodes de paiement, marquer comme remboursé manuellement
                $refundStatus = 'succeeded';
                $transactionId = 'MANUAL-' . strtoupper(uniqid());
            }
            
            // Créer un enregistrement de remboursement
            $refund = new Refund([
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $refundStatus,
                'transaction_id' => $transactionId,
                'reason' => $reason,
                'processed_by' => auth()->id(),
                'refunded_at' => now(),
            ]);
            
            $refund->save();
            
            // Mettre à jour le statut du paiement
            $payment->status = 'refunded';
            $payment->save();
            
            // Mettre à jour la réservation
            $booking = $payment->booking;
            $booking->paid_amount -= $payment->amount;
            
            // Si le montant payé est inférieur au montant total, mettre à jour le statut de paiement
            if ($booking->paid_amount < $booking->total_amount) {
                $booking->payment_status = 'partially_refunded';
            } else {
                $booking->payment_status = 'refunded';
            }
            
            $booking->save();
            
            // Envoyer une notification de remboursement
            // $booking->user->notify(new PaymentRefunded($payment, $refund));
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Refund failed: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'error' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * Get payment history for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentHistory(Request $request)
    {
        $user = $request->user();
        
        $query = Payment::with(['booking', 'paymentMethod'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');
        
        // Filtrage par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filtrage par date
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }
        
        $payments = $query->paginate($request->input('per_page', 10));
        
        return $this->sendPaginated($payments, 'Payment history retrieved successfully');
    }

    /**
     * Get payment details.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Request $request)
    {
        $payment = Payment::with(['booking', 'paymentMethod', 'refunds'])
            ->findOrFail($id);
        
        // Vérifier que l'utilisateur a le droit de voir ce paiement
        if ($payment->user_id !== $request->user()->id && !$request->user()->can('manage_payments')) {
            return $this->sendForbidden('You do not have permission to view this payment');
        }
        
        return $this->sendResponse($payment, 'Payment details retrieved successfully');
    }

    /**
     * Process a refund for a payment.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processRefundRequest($id, Request $request)
    {
        $payment = Payment::findOrFail($id);
        $user = $request->user();
        
        // Vérifier les autorisations
        if ($payment->user_id !== $user->id && !$user->can('manage_payments')) {
            return $this->sendForbidden('You do not have permission to request a refund for this payment');
        }
        
        // Vérifier que le paiement peut être remboursé
        if ($payment->status !== 'completed' || $payment->amount <= 0) {
            return $this->sendError('This payment cannot be refunded', [], 422);
        }
        
        // Vérifier s'il existe déjà une demande de remboursement en attente
        $existingRefund = $payment->refunds()
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
            
        if ($existingRefund) {
            return $this->sendError('A refund request is already in progress for this payment', [], 422);
        }
        
        // Créer une demande de remboursement
        $refund = new Refund([
            'payment_id' => $payment->id,
            'amount' => $request->input('amount', $payment->amount),
            'currency' => $payment->currency,
            'status' => 'pending',
            'reason' => $request->input('reason', 'Demande de remboursement'),
            'requested_by' => $user->id,
            'requested_at' => now(),
        ]);
        
        $refund->save();
        
        // Si l'utilisateur est un administrateur, traiter le remboursement immédiatement
        if ($user->can('manage_payments')) {
            $success = $this->processRefund($payment, $refund->reason);
            
            if ($success) {
                return $this->sendResponse($refund->fresh(), 'Refund processed successfully');
            } else {
                return $this->sendError('Failed to process refund', [], 500);
            }
        }
        
        // Sinon, envoyer une notification aux administrateurs pour approbation
        // Notification::send(User::role('admin')->get(), new RefundRequested($refund));
        
        return $this->sendResponse($refund, 'Refund request submitted successfully. Our team will review it shortly.');
    }

    /**
     * Verify a payment status.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment($id, Request $request)
    {
        $payment = Payment::findOrFail($id);
        
        // Vérifier que l'utilisateur a le droit de vérifier ce paiement
        if ($payment->user_id !== $request->user()->id && !$request->user()->can('manage_payments')) {
            return $this->sendForbidden('You do not have permission to verify this payment');
        }
        
        // Vérifier le statut du paiement avec le processeur de paiement
        try {
            if ($payment->paymentMethod->code === 'credit_card' && $payment->transaction_id) {
                // Vérifier avec Stripe
                $intent = PaymentIntent::retrieve($payment->transaction_id);
                
                if ($intent->status === 'succeeded' && $payment->status !== 'completed') {
                    // Mettre à jour le statut du paiement
                    $payment->status = 'completed';
                    $payment->paid_at = now();
                    $payment->save();
                    
                    // Mettre à jour la réservation
                    $booking = $payment->booking;
                    $booking->paid_amount += $payment->amount;
                    
                    if ($booking->paid_amount >= $booking->total_amount) {
                        $booking->payment_status = 'paid';
                        $booking->payment_completed_at = now();
                        $booking->booking_status = 'confirmed';
                        
                        // Envoyer une notification de confirmation
                        // $booking->user->notify(new BookingConfirmed($booking));
                    }
                    
                    $booking->save();
                }
                
                $status = $intent->status;
                $paymentDetails = $intent->toArray();
                
            } else {
                // Pour les autres méthodes de paiement, renvoyer le statut actuel
                $status = $payment->status;
                $paymentDetails = $payment->payment_details;
            }
            
            return $this->sendResponse([
                'payment_id' => $payment->id,
                'status' => $status,
                'payment_details' => $paymentDetails,
                'updated' => $payment->wasChanged('status'),
            ], 'Payment status verified');
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to verify payment status: ' . $e->getMessage(), [], 500);
        }
    }
}
