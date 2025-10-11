<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Activity;
use App\Models\ActivityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ActivityController extends BaseController
{
    /**
     * Display a listing of the activities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Activity::with(['categories']);

        // Filtrage par catégorie
        if ($request->has('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('activity_categories.id', $request->category_id);
            });
        }

        // Filtrage par statut
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Filtrage par difficulté
        if ($request->has('difficulty_level')) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        // Filtrage par prix
        if ($request->has('min_price')) {
            $query->where('price_per_person', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price_per_person', '<=', $request->max_price);
        }

        // Filtrage par date
        if ($request->has('date')) {
            $query->whereDate('start_time', '<=', $request->date)
                  ->whereDate('end_time', '>=', $request->date);
        }

        // Recherche par nom ou description
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortField = $request->input('sort_field', 'start_time');
        $sortDirection = $request->input('sort_direction', 'asc');
        
        if (in_array($sortField, ['name', 'price_per_person', 'start_time', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->input('per_page', 12);
        $activities = $query->paginate($perPage);

        return $this->sendPaginated($activities, 'Activities retrieved successfully');
    }

    /**
     * Store a newly created activity in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create_activities')) {
            return $this->sendForbidden('You do not have permission to create activities');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:activities',
            'description' => 'required|string',
            'highlights' => 'nullable|string',
            'location' => 'required|string|max:255',
            'price_per_person' => 'required|numeric|min:0',
            'duration_hours' => 'required|numeric|min:0.5',
            'min_people' => 'required|integer|min:1',
            'max_people' => 'required|integer|min:1|gt:min_people',
            'is_active' => 'boolean',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'included' => 'nullable|array',
            'included.*' => 'string|max:255',
            'not_included' => 'nullable|array',
            'not_included.*' => 'string|max:255',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string|max:255',
            'difficulty_level' => 'required|in:easy,moderate,challenging,difficult',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:activity_categories,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $data = $request->except(['main_image', 'gallery_images', 'categories', 'included', 'not_included', 'requirements']);
        
        // Gestion de l'image principale
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('activities/images', 'public');
            $data['main_image'] = $path;
        }

        // Gestion des images de la galerie
        $galleryImages = [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $path = $image->store('activities/gallery', 'public');
                $galleryImages[] = $path;
            }
            $data['gallery_images'] = $galleryImages;
        }

        // Convertir les tableaux en JSON
        if ($request->has('included')) {
            $data['included'] = json_encode($request->included);
        }
        
        if ($request->has('not_included')) {
            $data['not_included'] = json_encode($request->not_included);
        }
        
        if ($request->has('requirements')) {
            $data['requirements'] = json_encode($request->requirements);
        }

        // Créer l'activité
        $activity = Activity::create($data);

        // Attacher les catégories
        $activity->categories()->sync($request->categories);

        return $this->sendResponse($activity->load('categories'), 'Activity created successfully', 201);
    }

    /**
     * Display the specified activity.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        $activity = Activity::with(['categories'])
            ->where('slug', $slug)
            ->first();

        if (is_null($activity)) {
            return $this->sendNotFound('Activity not found');
        }

        // Décoder les champs JSON
        $activity->included = json_decode($activity->included, true) ?? [];
        $activity->not_included = json_decode($activity->not_included, true) ?? [];
        $activity->requirements = json_decode($activity->requirements, true) ?? [];

        return $this->sendResponse($activity, 'Activity retrieved successfully');
    }

    /**
     * Update the specified activity in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!$request->user()->can('edit_activities')) {
            return $this->sendForbidden('You do not have permission to update activities');
        }

        $activity = Activity::find($id);

        if (is_null($activity)) {
            return $this->sendNotFound('Activity not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('activities')->ignore($activity->id),
            ],
            'description' => 'sometimes|required|string',
            'highlights' => 'nullable|string',
            'location' => 'sometimes|required|string|max:255',
            'price_per_person' => 'sometimes|required|numeric|min:0',
            'duration_hours' => 'sometimes|required|numeric|min:0.5',
            'min_people' => 'sometimes|required|integer|min:1',
            'max_people' => 'sometimes|required|integer|min:1|gt:min_people',
            'is_active' => 'sometimes|boolean',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'included' => 'nullable|array',
            'included.*' => 'string|max:255',
            'not_included' => 'nullable|array',
            'not_included.*' => 'string|max:255',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string|max:255',
            'difficulty_level' => 'sometimes|required|in:easy,moderate,challenging,difficult',
            'categories' => 'sometimes|required|array|min:1',
            'categories.*' => 'exists:activity_categories,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $data = $request->except(['main_image', 'gallery_images', 'categories', 'included', 'not_included', 'requirements']);
        
        // Gestion de la mise à jour de l'image principale
        if ($request->hasFile('main_image')) {
            // Supprimer l'ancienne image si elle existe
            if ($activity->main_image) {
                Storage::disk('public')->delete($activity->main_image);
            }
            
            $path = $request->file('main_image')->store('activities/images', 'public');
            $data['main_image'] = $path;
        }

        // Gestion de la mise à jour des images de la galerie
        if ($request->hasFile('gallery_images')) {
            // Supprimer les anciennes images de la galerie
            if (!empty($activity->gallery_images)) {
                foreach ($activity->gallery_images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            
            $galleryImages = [];
            foreach ($request->file('gallery_images') as $image) {
                $path = $image->store('activities/gallery', 'public');
                $galleryImages[] = $path;
            }
            $data['gallery_images'] = $galleryImages;
        }

        // Convertir les tableaux en JSON
        if ($request->has('included')) {
            $data['included'] = json_encode($request->included);
        }
        
        if ($request->has('not_included')) {
            $data['not_included'] = json_encode($request->not_included);
        }
        
        if ($request->has('requirements')) {
            $data['requirements'] = json_encode($request->requirements);
        }

        $activity->update($data);

        // Mettre à jour les catégories si fournies
        if ($request->has('categories')) {
            $activity->categories()->sync($request->categories);
        }

        return $this->sendResponse($activity->load('categories'), 'Activity updated successfully');
    }

    /**
     * Toggle the active status of the specified activity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id)
    {
        if (!$request->user()->can('edit_activities')) {
            return $this->sendForbidden('You do not have permission to update activities');
        }

        $activity = Activity::find($id);

        if (is_null($activity)) {
            return $this->sendNotFound('Activity not found');
        }

        $activity->update([
            'is_active' => !$activity->is_active
        ]);

        $status = $activity->is_active ? 'activated' : 'deactivated';
        return $this->sendResponse($activity, "Activity {$status} successfully");
    }

    /**
     * Remove the specified activity from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete_activities')) {
            return $this->sendForbidden('You do not have permission to delete activities');
        }

        $activity = Activity::find($id);

        if (is_null($activity)) {
            return $this->sendNotFound('Activity not found');
        }

        // Vérifier s'il y a des réservations à venir pour cette activité
        if ($activity->bookings()->where('activity_date', '>=', now())->exists()) {
            return $this->sendError('Cannot delete activity with upcoming bookings', [], 422);
        }

        // Supprimer les images associées
        if ($activity->main_image) {
            Storage::disk('public')->delete($activity->main_image);
        }

        if (!empty($activity->gallery_images)) {
            foreach ($activity->gallery_images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        // Détacher les catégories
        $activity->categories()->detach();

        $activity->delete();

        return $this->sendResponse([], 'Activity deleted successfully');
    }

    /**
     * Get all activity categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = ActivityCategory::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return $this->sendResponse($categories, 'Activity categories retrieved successfully');
    }

    /**
     * Get featured activities.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured()
    {
        $activities = Activity::where('is_active', true)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->take(6)
            ->get();
            
        return $this->sendResponse($activities, 'Featured activities retrieved successfully');
    }

    /**
     * Get similar activities.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function similar($id)
    {
        $activity = Activity::find($id);

        if (is_null($activity)) {
            return $this->sendNotFound('Activity not found');
        }

        // Récupérer les activités similaires (même catégorie, même niveau de difficulté)
        $similarActivities = Activity::where('id', '!=', $activity->id)
            ->where('is_active', true)
            ->where('start_time', '>=', now())
            ->where('difficulty_level', $activity->difficulty_level)
            ->whereHas('categories', function($q) use ($activity) {
                $q->whereIn('activity_categories.id', $activity->categories->pluck('id'));
            })
            ->with('categories')
            ->inRandomOrder()
            ->take(4)
            ->get();

        // Si pas assez de résultats, compléter avec d'autres activités
        if ($similarActivities->count() < 4) {
            $additionalActivities = Activity::where('id', '!=', $activity->id)
                ->where('is_active', true)
                ->where('start_time', '>=', now())
                ->whereNotIn('id', $similarActivities->pluck('id'))
                ->with('categories')
                ->inRandomOrder()
                ->take(4 - $similarActivities->count())
                ->get();
                
            $similarActivities = $similarActivities->merge($additionalActivities);
        }

        return $this->sendResponse($similarActivities, 'Similar activities retrieved successfully');
    }
}
