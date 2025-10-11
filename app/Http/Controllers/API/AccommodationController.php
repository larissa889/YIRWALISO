<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Accommodation;
use App\Models\AccommodationType;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccommodationController extends BaseController
{
    /**
     * Display a listing of the accommodations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Accommodation::with(['type', 'amenities']);

        // Filtrage par type d'hébergement
        if ($request->has('type_id')) {
            $query->where('accommodation_type_id', $request->type_id);
        }

        // Filtrage par disponibilité
        if ($request->has('is_available')) {
            $query->where('is_available', filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN));
        }

        // Filtrage par équipements
        if ($request->has('amenities')) {
            $amenities = is_array($request->amenities) ? $request->amenities : explode(',', $request->amenities);
            $query->whereHas('amenities', function($q) use ($amenities) {
                $q->whereIn('amenities.id', $amenities);
            }, '=', count($amenities));
        }

        // Filtrage par capacité
        if ($request->has('min_capacity')) {
            $query->where('max_occupancy', '>=', $request->min_capacity);
        }

        // Filtrage par prix
        if ($request->has('min_price')) {
            $query->where('price_per_night', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        // Recherche par nom ou description
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        if (in_array($sortField, ['name', 'price_per_night', 'max_occupancy', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->input('per_page', 12);
        $accommodations = $query->paginate($perPage);

        return $this->sendPaginated($accommodations, 'Accommodations retrieved successfully');
    }

    /**
     * Store a newly created accommodation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create_accommodations')) {
            return $this->sendForbidden('You do not have permission to create accommodations');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:accommodations',
            'description' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'max_occupancy' => 'required|integer|min:1',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|numeric|min:0',
            'has_kitchen' => 'boolean',
            'has_wifi' => 'boolean',
            'has_parking' => 'boolean',
            'is_available' => 'boolean',
            'accommodation_type_id' => 'required|exists:accommodation_types,id',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $data = $request->except(['main_image', 'gallery_images', 'amenities']);
        
        // Gestion de l'image principale
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('accommodations/images', 'public');
            $data['main_image'] = $path;
        }

        // Gestion des images de la galerie
        $galleryImages = [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $path = $image->store('accommodations/gallery', 'public');
                $galleryImages[] = $path;
            }
            $data['gallery_images'] = $galleryImages;
        }

        $accommodation = Accommodation::create($data);

        // Attacher les équipements
        if ($request->has('amenities')) {
            $accommodation->amenities()->sync($request->amenities);
        }

        return $this->sendResponse($accommodation->load(['type', 'amenities']), 'Accommodation created successfully', 201);
    }

    /**
     * Display the specified accommodation.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        $accommodation = Accommodation::with(['type', 'amenities'])
            ->where('slug', $slug)
            ->first();

        if (is_null($accommodation)) {
            return $this->sendNotFound('Accommodation not found');
        }

        return $this->sendResponse($accommodation, 'Accommodation retrieved successfully');
    }

    /**
     * Update the specified accommodation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!$request->user()->can('edit_accommodations')) {
            return $this->sendForbidden('You do not have permission to update accommodations');
        }

        $accommodation = Accommodation::find($id);

        if (is_null($accommodation)) {
            return $this->sendNotFound('Accommodation not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('accommodations')->ignore($accommodation->id),
            ],
            'description' => 'sometimes|required|string',
            'price_per_night' => 'sometimes|required|numeric|min:0',
            'max_occupancy' => 'sometimes|required|integer|min:1',
            'bedrooms' => 'sometimes|required|integer|min:0',
            'bathrooms' => 'sometimes|required|numeric|min:0',
            'has_kitchen' => 'sometimes|boolean',
            'has_wifi' => 'sometimes|boolean',
            'has_parking' => 'sometimes|boolean',
            'is_available' => 'sometimes|boolean',
            'accommodation_type_id' => 'sometimes|required|exists:accommodation_types,id',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $data = $request->except(['main_image', 'gallery_images', 'amenities']);
        
        // Gestion de la mise à jour de l'image principale
        if ($request->hasFile('main_image')) {
            // Supprimer l'ancienne image si elle existe
            if ($accommodation->main_image) {
                Storage::disk('public')->delete($accommodation->main_image);
            }
            
            $path = $request->file('main_image')->store('accommodations/images', 'public');
            $data['main_image'] = $path;
        }

        // Gestion de la mise à jour des images de la galerie
        if ($request->hasFile('gallery_images')) {
            // Supprimer les anciennes images de la galerie
            if (!empty($accommodation->gallery_images)) {
                foreach ($accommodation->gallery_images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            
            $galleryImages = [];
            foreach ($request->file('gallery_images') as $image) {
                $path = $image->store('accommodations/gallery', 'public');
                $galleryImages[] = $path;
            }
            $data['gallery_images'] = $galleryImages;
        }

        $accommodation->update($data);

        // Mettre à jour les équipements si fournis
        if ($request->has('amenities')) {
            $accommodation->amenities()->sync($request->amenities);
        }

        return $this->sendResponse($accommodation->load(['type', 'amenities']), 'Accommodation updated successfully');
    }

    /**
     * Toggle the availability status of the specified accommodation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailability(Request $request, $id)
    {
        if (!$request->user()->can('edit_accommodations')) {
            return $this->sendForbidden('You do not have permission to update accommodations');
        }

        $accommodation = Accommodation::find($id);

        if (is_null($accommodation)) {
            return $this->sendNotFound('Accommodation not found');
        }

        $accommodation->update([
            'is_available' => !$accommodation->is_available
        ]);

        $status = $accommodation->is_available ? 'available' : 'unavailable';
        return $this->sendResponse($accommodation, "Accommodation marked as {$status}");
    }

    /**
     * Remove the specified accommodation from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete_accommodations')) {
            return $this->sendForbidden('You do not have permission to delete accommodations');
        }

        $accommodation = Accommodation::find($id);

        if (is_null($accommodation)) {
            return $this->sendNotFound('Accommodation not found');
        }

        // Vérifier s'il y a des réservations à venir pour cet hébergement
        if ($accommodation->bookings()->where('check_out_date', '>=', now())->exists()) {
            return $this->sendError('Cannot delete accommodation with upcoming bookings', [], 422);
        }

        // Supprimer les images associées
        if ($accommodation->main_image) {
            Storage::disk('public')->delete($accommodation->main_image);
        }

        if (!empty($accommodation->gallery_images)) {
            foreach ($accommodation->gallery_images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        // Détacher les équipements
        $accommodation->amenities()->detach();

        $accommodation->delete();

        return $this->sendResponse([], 'Accommodation deleted successfully');
    }

    /**
     * Get all accommodation types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTypes()
    {
        $types = AccommodationType::where('is_active', true)->get();
        return $this->sendResponse($types, 'Accommodation types retrieved successfully');
    }

    /**
     * Get all amenities.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAmenities()
    {
        $amenities = Amenity::where('is_active', true)->get();
        return $this->sendResponse($amenities, 'Amenities retrieved successfully');
    }

    /**
     * Check accommodation availability for given dates.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $accommodation = Accommodation::find($id);

        if (is_null($accommodation)) {
            return $this->sendNotFound('Accommodation not found');
        }

        $isAvailable = $accommodation->isAvailableForDates(
            $request->check_in,
            $request->check_out,
            $request->except(['check_in', 'check_out', 'per_page'])
        );

        return $this->sendResponse([
            'is_available' => $isAvailable,
            'accommodation' => $accommodation->only(['id', 'name', 'price_per_night', 'max_occupancy']),
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'total_nights' => \Carbon\Carbon::parse($request->check_in)->diffInDays($request->check_out),
            'total_price' => $accommodation->price_per_night * \Carbon\Carbon::parse($request->check_in)->diffInDays($request->check_out),
        ], $isAvailable ? 'Accommodation is available for the selected dates' : 'Accommodation is not available for the selected dates');
    }
}
