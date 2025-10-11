<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Review;
use App\Models\Accommodation;
use App\Models\Activity;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewController extends BaseController
{
    /**
     * Display a listing of the reviews.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'reviewable']);

        // Filtrage par type (hébergement ou activité)
        if ($request->has('type')) {
            $type = $request->type === 'accommodation' ? 'App\\Models\\Accommodation' : 'App\\Models\\Activity';
            $query->where('reviewable_type', $type);
        }

        // Filtrage par ID de l'hébergement ou de l'activité
        if ($request->has('reviewable_id')) {
            $query->where('reviewable_id', $request->reviewable_id);
        }

        // Filtrage par utilisateur
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtrage par note
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        } elseif ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Filtrage par statut d'approbation
        if ($request->has('is_approved')) {
            $query->where('is_approved', filter_var($request->is_approved, FILTER_VALIDATE_BOOLEAN));
        }

        // Tri
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        if (in_array($sortField, ['rating', 'created_at', 'helpful_count'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $reviews = $query->paginate($perPage);

        return $this->sendPaginated($reviews, 'Reviews retrieved successfully');
    }

    /**
     * Store a newly created review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'reviewable_type' => 'required|in:accommodation,activity',
            'reviewable_id' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $model = $request->reviewable_type === 'accommodation' 
                        ? 'App\\Models\\Accommodation' 
                        : 'App\\Models\\Activity';
                    
                    if (!class_exists($model) || !$model::find($value)) {
                        $fail('The selected ' . $request->reviewable_type . ' does not exist.');
                    }
                },
            ],
            'booking_id' => [
                'required',
                'exists:bookings,id',
                function ($attribute, $value, $fail) use ($user, $request) {
                    $booking = Booking::find($value);
                    
                    // Vérifier que la réservation appartient à l'utilisateur
                    if ($booking->user_id !== $user->id) {
                        $fail('You can only review your own bookings.');
                    }
                    
                    // Vérifier que la réservation correspond au type d'avis
                    $isAccommodationReview = $request->reviewable_type === 'accommodation';
                    $hasMismatch = ($isAccommodationReview && is_null($booking->accommodation_id)) || 
                                 (!$isAccommodationReview && is_null($booking->activity_id));
                    
                    if ($hasMismatch) {
                        $fail('The booking does not match the review type.');
                    }
                    
                    // Vérifier que la réservation est terminée
                    if ($isAccommodationReview && $booking->check_out_date > now()) {
                        $fail('You can only review after your stay has ended.');
                    }
                    
                    if (!$isAccommodationReview && $booking->activity_date > now()) {
                        $fail('You can only review after the activity has taken place.');
                    }
                    
                    // Vérifier qu'il n'y a pas déjà un avis pour cette réservation
                    $reviewableId = $isAccommodationReview 
                        ? $booking->accommodation_id 
                        : $booking->activity_id;
                    
                    $reviewExists = Review::where('user_id', $user->id)
                        ->where('reviewable_type', $isAccommodationReview ? 'App\\Models\\Accommodation' : 'App\\Models\\Activity')
                        ->where('reviewable_id', $reviewableId)
                        ->where('booking_id', $value)
                        ->exists();
                    
                    if ($reviewExists) {
                        $fail('You have already reviewed this ' . $request->reviewable_type . '.');
                    }
                },
            ],
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string|min:10|max:2000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'is_anonymous' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $booking = Booking::find($request->booking_id);
        $isAccommodationReview = $request->reviewable_type === 'accommodation';
        
        $reviewableId = $isAccommodationReview 
            ? $booking->accommodation_id 
            : $booking->activity_id;
        
        $reviewableType = $isAccommodationReview 
            ? 'App\\Models\\Accommodation' 
            : 'App\\Models\\Activity';
        
        // Créer l'avis
        $review = new Review([
            'user_id' => $user->id,
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $reviewableId,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_anonymous' => $request->is_anonymous ?? false,
            'is_approved' => false, // Les avis nécessitent une approbation manuelle
        ]);

        // Gestion des images
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews/images', 'public');
                $images[] = $path;
            }
            $review->images = $images;
        }

        $review->save();

        // Mettre à jour la note moyenne de l'hébergement ou de l'activité
        $this->updateReviewableRating($reviewableType, $reviewableId);

        return $this->sendResponse($review->load(['user', 'reviewable']), 'Review submitted successfully. It will be published after approval.', 201);
    }

    /**
     * Display the specified review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $review = Review::with(['user', 'reviewable', 'booking'])->find($id);

        if (is_null($review)) {
            return $this->sendNotFound('Review not found');
        }

        // Ne pas afficher les avis non approuvés sauf pour l'administrateur
        if (!$review->is_approved && !auth()->user()?->can('manage_reviews')) {
            return $this->sendForbidden('This review is pending approval');
        }

        return $this->sendResponse($review, 'Review retrieved successfully');
    }

    /**
     * Update the specified review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $review = Review::find($id);

        if (is_null($review)) {
            return $this->sendNotFound('Review not found');
        }

        // Vérifier que l'utilisateur est l'auteur de l'avis ou un administrateur
        if ($user->id !== $review->user_id && !$user->can('manage_reviews')) {
            return $this->sendForbidden('You do not have permission to update this review');
        }

        // Les administrateurs peuvent approuver/désapprouver les avis
        if ($user->can('manage_reviews') && $request->has('is_approved')) {
            $review->is_approved = $request->is_approved;
            $review->save();
            
            // Mettre à jour la note moyenne si l'état d'approbation a changé
            $this->updateReviewableRating($review->reviewable_type, $review->reviewable_id);
            
            return $this->sendResponse($review, 'Review updated successfully');
        }

        // Les utilisateurs normaux ne peuvent mettre à jour que leurs propres avis non approuvés
        if ($review->is_approved) {
            return $this->sendError('Approved reviews cannot be modified', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'title' => 'sometimes|required|string|max:255',
            'comment' => 'sometimes|required|string|min:10|max:2000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'is_anonymous' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Mettre à jour les champs modifiables
        $review->fill($request->only(['rating', 'title', 'comment', 'is_anonymous']));
        
        // Gestion des images
        if ($request->hasFile('images')) {
            // Supprimer les anciennes images
            if (!empty($review->images)) {
                foreach ($review->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews/images', 'public');
                $images[] = $path;
            }
            $review->images = $images;
        }

        $review->save();

        // Mettre à jour la note moyenne de l'hébergement ou de l'activité
        $this->updateReviewableRating($review->reviewable_type, $review->reviewable_id);

        return $this->sendResponse($review->load(['user', 'reviewable']), 'Review updated successfully');
    }

    /**
     * Remove the specified review from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $review = Review::find($id);

        if (is_null($review)) {
            return $this->sendNotFound('Review not found');
        }

        // Vérifier que l'utilisateur est l'auteur de l'avis ou un administrateur
        if ($user->id !== $review->user_id && !$user->can('manage_reviews')) {
            return $this->sendForbidden('You do not have permission to delete this review');
        }

        // Sauvegarder les informations pour la mise à jour de la note moyenne
        $reviewableType = $review->reviewable_type;
        $reviewableId = $review->reviewable_id;

        // Supprimer les images associées
        if (!empty($review->images)) {
            foreach ($review->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $review->delete();

        // Mettre à jour la note moyenne de l'hébergement ou de l'activité
        $this->updateReviewableRating($reviewableType, $reviewableId);

        return $this->sendResponse([], 'Review deleted successfully');
    }

    /**
     * Toggle the helpful status of a review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleHelpful(Request $request, $id)
    {
        $user = $request->user();
        $review = Review::find($id);

        if (is_null($review)) {
            return $this->sendNotFound('Review not found');
        }

        // Vérifier que l'utilisateur ne peut pas voter pour son propre avis
        if ($user->id === $review->user_id) {
            return $this->sendError('You cannot rate your own review', [], 403);
        }

        // Vérifier si l'utilisateur a déjà trouvé cet avis utile
        $hasVoted = $review->helpfulVotes()->where('user_id', $user->id)->exists();

        if ($hasVoted) {
            // Retirer le vote
            $review->helpfulVotes()->detach($user->id);
            $review->decrement('helpful_count');
            $message = 'Removed helpful vote';
        } else {
            // Ajouter le vote
            $review->helpfulVotes()->attach($user->id);
            $review->increment('helpful_count');
            $message = 'Marked as helpful';
        }

        return $this->sendResponse([
            'review_id' => $review->id,
            'helpful_count' => $review->helpful_count,
            'has_voted' => !$hasVoted,
        ], $message);
    }

    /**
     * Report a review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function report(Request $request, $id)
    {
        $user = $request->user();
        $review = Review::find($id);

        if (is_null($review)) {
            return $this->sendNotFound('Review not found');
        }

        // Vérifier que l'utilisateur ne peut pas signaler son propre avis
        if ($user->id === $review->user_id) {
            return $this->sendError('You cannot report your own review', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Vérifier si l'utilisateur a déjà signalé cet avis
        $alreadyReported = $review->reports()->where('user_id', $user->id)->exists();

        if ($alreadyReported) {
            return $this->sendError('You have already reported this review', [], 422);
        }

        // Enregistrer le signalement
        $review->reports()->create([
            'user_id' => $user->id,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        // Incrémenter le compteur de signalements
        $review->increment('report_count');

        // Si le nombre de signalements dépasse un certain seuil, désapprouver automatiquement l'avis
        if ($review->report_count >= config('reports.auto_disapprove_threshold', 5)) {
            $review->update([
                'is_approved' => false,
                'status' => 'pending_review',
            ]);
            
            // Mettre à jour la note moyenne de l'hébergement ou de l'activité
            $this->updateReviewableRating($review->reviewable_type, $review->reviewable_id);
            
            // Notifier l'administrateur
            // Notification::send(User::role('admin')->get(), new ReviewFlaggedForReview($review));
        }

        return $this->sendResponse([], 'Review reported successfully. Our team will review it shortly.');
    }

    /**
     * Update the rating of a reviewable item (accommodation or activity).
     *
     * @param  string  $reviewableType
     * @param  int  $reviewableId
     * @return void
     */
    protected function updateReviewableRating($reviewableType, $reviewableId)
    {
        $model = app($reviewableType);
        $reviewable = $model->find($reviewableId);
        
        if ($reviewable) {
            $approvedReviews = $reviewable->reviews()->where('is_approved', true);
            
            $reviewable->update([
                'average_rating' => $approvedReviews->avg('rating'),
                'review_count' => $approvedReviews->count(),
            ]);
        }
    }
}
