<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use App\Models\DesignerProfile;
use App\Models\PortfolioItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DesignerController extends BaseController
{
    /**
     * Get or create designer profile for the authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrCreateProfile(Request $request)
    {
        $user = $request->user();
        
        // Vérifier si l'utilisateur a déjà un profil designer
        if (!$user->designerProfile) {
            $user->designerProfile()->create([
                'specialty' => 'Designer',
                'bio' => 'Je suis un designer passionné',
                'is_available' => true,
                'hourly_rate' => 0,
            ]);
            $user->load('designerProfile');
        }

        return $this->sendResponse(
            $user->designerProfile,
            'Profil designer récupéré avec succès'
        );
    }

    /**
     * Update designer profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $profile = $user->designerProfile;

        if (!$profile) {
            return $this->sendError('Profil designer non trouvé', [], 404);
        }

        $validated = $request->validate([
            'specialty' => 'sometimes|string|max:100',
            'bio' => 'sometimes|string|max:1000',
            'years_of_experience' => 'sometimes|integer|min:0',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'is_available' => 'sometimes|boolean',
            'skills' => 'sometimes|array',
            'skills.*' => 'string|max:50',
            'website' => 'nullable|url|max:255',
            'social_links' => 'sometimes|array',
            'social_links.*' => 'url|max:255',
        ]);

        // Gérer l'upload de l'avatar si fourni
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('designers/avatars', 'public');
            $validated['avatar'] = $path;
        }

        $profile->update($validated);

        return $this->sendResponse(
            $profile->fresh(),
            'Profil mis à jour avec succès'
        );
    }

    /**
     * Get all portfolio items for the authenticated designer
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPortfolioItems(Request $request)
    {
        $user = $request->user();
        $items = $user->designerProfile->portfolioItems()
            ->latest()
            ->paginate(10);

        return $this->sendPaginated(
            $items,
            'Portfolio récupéré avec succès'
        );
    }

    /**
     * Add a new portfolio item
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPortfolioItem(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'project_date' => 'required|date',
            'client_name' => 'nullable|string|max:255',
            'project_url' => 'nullable|url|max:255',
            'technologies' => 'sometimes|array',
            'technologies.*' => 'string|max:100',
            'images' => 'sometimes|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max per image
        ]);

        $user = $request->user();
        $portfolioItem = $user->designerProfile->portfolioItems()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'project_date' => $validated['project_date'],
            'client_name' => $validated['client_name'] ?? null,
            'project_url' => $validated['project_url'] ?? null,
            'technologies' => $validated['technologies'] ?? [],
        ]);

        // Gérer le téléchargement des images
        if ($request->hasFile('images')) {
            $this->uploadPortfolioImages($portfolioItem, $request->file('images'));
        }

        return $this->sendResponse(
            $portfolioItem->load('media'),
            'Projet ajouté au portfolio avec succès',
            201
        );
    }

    /**
     * Update a portfolio item
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePortfolioItem(Request $request, $id)
    {
        $portfolioItem = PortfolioItem::where('designer_profile_id', $request->user()->designerProfile->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'project_date' => 'sometimes|date',
            'client_name' => 'nullable|string|max:255',
            'project_url' => 'nullable|url|max:255',
            'technologies' => 'sometimes|array',
            'technologies.*' => 'string|max:100',
            'images_to_remove' => 'sometimes|array',
            'images_to_remove.*' => 'integer|exists:media,id',
            'new_images' => 'sometimes|array|max:10',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Supprimer les images sélectionnées
        if (isset($validated['images_to_remove'])) {
            $portfolioItem->media()->whereIn('id', $validated['images_to_remove'])->delete();
        }

        // Mettre à jour les autres champs
        $updateData = array_diff_key($validated, [
            'images_to_remove' => '',
            'new_images' => ''
        ]);

        $portfolioItem->update($updateData);

        // Ajouter de nouvelles images
        if ($request->hasFile('new_images')) {
            $this->uploadPortfolioImages($portfolioItem, $request->file('new_images'));
        }

        return $this->sendResponse(
            $portfolioItem->fresh()->load('media'),
            'Projet mis à jour avec succès'
        );
    }

    /**
     * Delete a portfolio item
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePortfolioItem(Request $request, $id)
    {
        $portfolioItem = PortfolioItem::where('designer_profile_id', $request->user()->designerProfile->id)
            ->findOrFail($id);

        // Supprimer les médias associés
        $portfolioItem->media()->delete();
        $portfolioItem->delete();

        return $this->sendResponse(
            null,
            'Projet supprimé avec succès',
            204
        );
    }

    /**
     * Get designer's availability
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailability(Request $request)
    {
        $user = $request->user();
        $profile = $user->designerProfile;

        return $this->sendResponse([
            'is_available' => $profile->is_available,
            'next_available_date' => $profile->next_available_date,
            'working_hours' => $profile->working_hours,
            'booked_slots' => $profile->bookedSlots()
                ->where('end_time', '>', now())
                ->get()
                ->map(function ($slot) {
                    return [
                        'start' => $slot->start_time,
                        'end' => $slot->end_time,
                        'title' => 'Indisponible'
                    ];
                })
        ], 'Disponibilité récupérée avec succès');
    }

    /**
     * Update designer's availability
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAvailability(Request $request)
    {
        $validated = $request->validate([
            'is_available' => 'sometimes|boolean',
            'next_available_date' => 'nullable|date|after:now',
            'working_hours' => 'sometimes|array',
            'working_hours.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'working_hours.*.start' => 'required|date_format:H:i',
            'working_hours.*.end' => 'required|date_format:H:i|after:working_hours.*.start',
            'working_hours.*.is_available' => 'sometimes|boolean',
            'unavailable_dates' => 'sometimes|array',
            'unavailable_dates.*' => 'date|after:yesterday',
        ]);

        $profile = $request->user()->designerProfile;
        $profile->update($validated);

        return $this->sendResponse(
            $profile->fresh(),
            'Disponibilité mise à jour avec succès'
        );
    }

    /**
     * Get designer's reviews and ratings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReviews(Request $request)
    {
        $reviews = $request->user()->designerProfile->reviews()
            ->with('user:id,name,profile_photo_path')
            ->latest()
            ->paginate(10);

        $averageRating = $request->user()->designerProfile->reviews()->avg('rating');
        $totalReviews = $request->user()->designerProfile->reviews()->count();

        return $this->sendResponse([
            'average_rating' => (float) number_format($averageRating, 1),
            'total_reviews' => $totalReviews,
            'reviews' => $reviews
        ], 'Avis récupérés avec succès');
    }

    /**
     * Upload portfolio images
     *
     * @param  \App\Models\PortfolioItem  $portfolioItem
     * @param  array  $images
     * @return void
     */
    private function uploadPortfolioImages($portfolioItem, $images)
    {
        foreach ($images as $image) {
            $path = $image->store('portfolio/' . $portfolioItem->id, 'public');
            
            $portfolioItem->addMedia(storage_path('app/public/' . $path))
                ->toMediaCollection('portfolio_images');
        }
    }

    /**
     * Get designer statistics
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        $profile = $request->user()->designerProfile;
        
        $stats = [
            'total_projects' => $profile->portfolioItems()->count(),
            'total_clients' => $profile->portfolioItems()->distinct('client_name')->count('client_name'),
            'average_rating' => (float) number_format($profile->reviews()->avg('rating') ?? 0, 1),
            'total_reviews' => $profile->reviews()->count(),
            'completion_rate' => $profile->projects()->count() > 0 
                ? round(($profile->projects()->where('status', 'completed')->count() / $profile->projects()->count()) * 100)
                : 0,
            'skills_distribution' => $this->getSkillsDistribution($profile),
        ];

        return $this->sendResponse($stats, 'Statistiques récupérées avec succès');
    }

    /**
     * Get skills distribution for a designer
     *
     * @param  \App\Models\DesignerProfile  $profile
     * @return array
     */
    private function getSkillsDistribution($profile)
    {
        // Récupérer tous les projets et compétences associées
        $projects = $profile->portfolioItems()
            ->whereNotNull('technologies')
            ->pluck('technologies')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(5);

        $total = $projects->sum();
        
        return $projects->mapWithKeys(function ($count, $skill) use ($total) {
            return [$skill => round(($count / $total) * 100)];
        })->toArray();
    }
}
