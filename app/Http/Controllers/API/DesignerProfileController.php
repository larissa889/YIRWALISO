<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DesignerProfileController extends BaseController
{
    /**
     * Display a listing of the designer profiles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = DesignerProfile::with(['user', 'portfolioItems'])
            ->where('status', 'approved')
            ->latest();

        // Filtrage par spécialité
        if ($request->has('specialty')) {
            $query->where('specialty', 'like', '%' . $request->specialty . '%');
        }

        // Filtrage par compétences
        if ($request->has('skills')) {
            $skills = explode(',', $request->skills);
            foreach ($skills as $skill) {
                $query->whereJsonContains('skills', $skill);
            }
        }

        // Filtrage par disponibilité
        if ($request->boolean('available')) {
            $query->where('is_available', true)
                ->where(function($q) {
                    $q->whereNull('next_available_date')
                      ->orWhere('next_available_date', '<=', now());
                });
        }

        // Filtrage par note minimale
        if ($request->has('min_rating')) {
            $query->where('average_rating', '>=', $request->min_rating);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $profiles = $query->paginate($perPage);

        return $this->sendPaginated($profiles, 'Designer profiles retrieved successfully');
    }

    /**
     * Store a newly created designer profile in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur a déjà un profil de designer
        if ($user->designerProfile) {
            return $this->sendError('User already has a designer profile', [], 400);
        }

        $validator = Validator::make($request->all(), [
            'specialty' => 'required|string|max:100',
            'bio' => 'required|string|min:100|max:2000',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'hourly_rate' => 'required|numeric|min:0|max:1000',
            'skills' => 'required|array|min:3|max:20',
            'skills.*' => 'string|max:50',
            'website' => 'nullable|url|max:255',
            'social_links' => 'sometimes|array',
            'social_links.*' => 'url|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        try {
            $data = $validator->validated();
            $data['user_id'] = $user->id;
            $data['slug'] = $this->generateUniqueSlug($data['specialty']);
            
            // Gérer le téléchargement de l'avatar
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('designers/avatars', 'public');
                $data['avatar'] = $path;
            }
            
            // Gérer le téléchargement de la photo de couverture
            if ($request->hasFile('cover_photo')) {
                $path = $request->file('cover_photo')->store('designers/covers', 'public');
                $data['cover_photo'] = $path;
            }
            
            $profile = DesignerProfile::create($data);
            
            // Mettre à jour le rôle de l'utilisateur en designer
            $user->assignRole('designer');
            
            return $this->sendResponse($profile, 'Designer profile created successfully', 201);
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to create designer profile', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified designer profile.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        $profile = DesignerProfile::with(['user', 'portfolioItems' => function($query) {
            $query->where('status', 'published')
                  ->orderBy('is_featured', 'desc')
                  ->orderBy('project_date', 'desc');
        }])
        ->where('slug', $slug)
        ->firstOrFail();

        // Incrémenter le compteur de vues
        $profile->increment('views_count');

        return $this->sendResponse($profile, 'Designer profile retrieved successfully');
    }

    /**
     * Update the specified designer profile in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $profile = DesignerProfile::findOrFail($id);

        // Vérifier que l'utilisateur est le propriétaire du profil ou un administrateur
        if ($user->id !== $profile->user_id && !$user->hasRole('admin')) {
            return $this->sendForbidden('You do not have permission to update this profile');
        }

        $validator = Validator::make($request->all(), [
            'specialty' => 'sometimes|required|string|max:100',
            'bio' => 'sometimes|required|string|min:100|max:2000',
            'years_of_experience' => 'sometimes|required|integer|min:0|max:50',
            'hourly_rate' => 'sometimes|required|numeric|min:0|max:1000',
            'is_available' => 'sometimes|boolean',
            'next_available_date' => 'nullable|date|after:now',
            'skills' => 'sometimes|array|min:3|max:20',
            'skills.*' => 'string|max:50',
            'website' => 'nullable|url|max:255',
            'social_links' => 'sometimes|array',
            'social_links.*' => 'url|max:255',
            'working_hours' => 'sometimes|array',
            'working_hours.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'working_hours.*.start' => 'required|date_format:H:i',
            'working_hours.*.end' => 'required|date_format:H:i|after:working_hours.*.start',
            'working_hours.*.is_available' => 'sometimes|boolean',
            'unavailable_dates' => 'sometimes|array',
            'unavailable_dates.*' => 'date|after:yesterday',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120',
            'status' => [Rule::in(['pending', 'approved', 'rejected'])],
            'rejection_reason' => 'required_if:status,rejected|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        try {
            $data = $validator->validated();
            
            // Mettre à jour le slug si la spécialité a changé
            if (isset($data['specialty']) && $data['specialty'] !== $profile->specialty) {
                $data['slug'] = $this->generateUniqueSlug($data['specialty']);
            }
            
            // Gérer le téléchargement de l'avatar
            if ($request->hasFile('avatar')) {
                // Supprimer l'ancien avatar s'il existe
                if ($profile->avatar) {
                    Storage::disk('public')->delete($profile->avatar);
                }
                $path = $request->file('avatar')->store('designers/avatars', 'public');
                $data['avatar'] = $path;
            }
            
            // Gérer le téléchargement de la photo de couverture
            if ($request->hasFile('cover_photo')) {
                // Supprimer l'ancienne photo de couverture si elle existe
                if ($profile->cover_photo) {
                    Storage::disk('public')->delete($profile->cover_photo);
                }
                $path = $request->file('cover_photo')->store('designers/covers', 'public');
                $data['cover_photo'] = $path;
            }
            
            // Seuls les administrateurs peuvent modifier le statut
            if (!$user->hasRole('admin')) {
                unset($data['status'], $data['rejection_reason']);
            } elseif (isset($data['status']) && $data['status'] === 'approved') {
                // Envoyer une notification à l'utilisateur lorsque son profil est approuvé
                $profile->user->notify(new ProfileApprovedNotification($profile));
            }
            
            $profile->update($data);
            
            return $this->sendResponse($profile, 'Designer profile updated successfully');
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to update designer profile', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified designer profile from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $profile = DesignerProfile::findOrFail($id);

        // Vérifier que l'utilisateur est le propriétaire du profil ou un administrateur
        if ($user->id !== $profile->user_id && !$user->hasRole('admin')) {
            return $this->sendForbidden('You do not have permission to delete this profile');
        }

        try {
            // Supprimer les fichiers associés
            if ($profile->avatar) {
                Storage::disk('public')->delete($profile->avatar);
            }
            if ($profile->cover_photo) {
                Storage::disk('public')->delete($profile->cover_photo);
            }
            
            // Supprimer le profil (soft delete)
            $profile->delete();
            
            // Si l'utilisateur n'est pas un administrateur, retirer le rôle de designer
            if (!$user->hasRole('admin')) {
                $user->removeRole('designer');
            }
            
            return $this->sendResponse(null, 'Designer profile deleted successfully', 204);
            
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete designer profile', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the authenticated user's designer profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function myProfile()
    {
        $user = Auth::user();
        
        if (!$user->designerProfile) {
            return $this->sendError('No designer profile found for this user', [], 404);
        }
        
        $profile = $user->designerProfile;
        $profile->load('portfolioItems');
        
        return $this->sendResponse($profile, 'Designer profile retrieved successfully');
    }

    /**
     * Update the authenticated user's designer profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMyProfile(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->designerProfile) {
            return $this->sendError('No designer profile found for this user', [], 404);
        }
        
        return $this->update($request, $user->designerProfile->id);
    }

    /**
     * Toggle the availability status of the authenticated designer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailability(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->designerProfile) {
            return $this->sendError('No designer profile found for this user', [], 404);
        }
        
        $profile = $user->designerProfile;
        $profile->is_available = !$profile->is_available;
        $profile->save();
        
        $status = $profile->is_available ? 'available' : 'unavailable';
        
        return $this->sendResponse(
            ['is_available' => $profile->is_available],
            "Your profile is now {$status}"
        );
    }

    /**
     * Generate a unique slug for the designer profile.
     *
     * @param  string  $title
     * @return string
     */
    private function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $count = DesignerProfile::where('slug', 'like', "{$slug}%")->count();
        
        return $count ? "{$slug}-{$count}" : $slug;
    }
}
