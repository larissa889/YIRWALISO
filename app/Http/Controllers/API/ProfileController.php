<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends BaseController
{
    /**
     * Get the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();
        return $this->sendResponse(
            $user->load(['roles', 'permissions']), 
            'Profile retrieved successfully'
        );
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'preferences' => 'nullable|array',
            'notification_preferences' => 'nullable|array',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user->update($validated);

        return $this->sendResponse(
            $user->fresh(), 
            'Profile updated successfully'
        );
    }

    /**
     * Update the authenticated user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendError('Current password is incorrect', [], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->sendResponse([], 'Password updated successfully');
    }

    /**
     * Update the authenticated user's notification preferences.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNotificationPreferences(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
            'marketing_emails' => 'sometimes|boolean',
            'booking_updates' => 'sometimes|boolean',
            'special_offers' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        
        $user->notification_preferences = array_merge(
            (array) $user->notification_preferences,
            $validated
        );
        
        $user->save();

        return $this->sendResponse(
            ['notification_preferences' => $user->notification_preferences],
            'Notification preferences updated successfully'
        );
    }

    /**
     * Delete the authenticated user's account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return $this->sendError('Password is incorrect', [], 422);
        }

        // Soft delete the user
        $user->delete();

        // Revoke all tokens
        $user->tokens()->delete();

        return $this->sendResponse([], 'Your account has been deleted successfully');
    }

    /**
     * Upload a file to the user's storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|in:document,image,other',
        ]);

        $user = $request->user();
        $file = $request->file('file');
        
        // Generate a unique filename
        $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("users/{$user->id}/{$request->type}", $fileName, 'public');
        
        // Save file info to user's documents
        $user->documents()->create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'type' => $request->type,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
        
        return $this->sendResponse(
            ['path' => $path],
            'File uploaded successfully'
        );
    }

    /**
     * Get the authenticated user's documents.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDocuments(Request $request)
    {
        $user = $request->user();
        $documents = $user->documents()
            ->when($request->type, function($query, $type) {
                return $query->where('type', $type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
        
        return $this->sendPaginated($documents, 'Documents retrieved successfully');
    }

    /**
     * Delete a user document.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDocument($id, Request $request)
    {
        $user = $request->user();
        $document = $user->documents()->findOrFail($id);
        
        // Delete the file from storage
        Storage::disk('public')->delete($document->path);
        
        // Delete the document record
        $document->delete();
        
        return $this->sendResponse([], 'Document deleted successfully');
    }

    /**
     * Get the authenticated user's activity log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityLog(Request $request)
    {
        $user = $request->user();
        
        $activities = $user->activities()
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
        
        return $this->sendPaginated($activities, 'Activity log retrieved');
    }

    /**
     * Update the authenticated user's preferences.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'language' => 'sometimes|string|in:fr,en,es',
            'timezone' => 'sometimes|timezone',
            'currency' => 'sometimes|string|size:3',
            'theme' => 'sometimes|in:light,dark,system',
            'preferences' => 'sometimes|array',
        ]);

        $user = $request->user();
        $user->update($validated);

        return $this->sendResponse(
            $user->only(['language', 'timezone', 'currency', 'theme', 'preferences']),
            'Preferences updated successfully'
        );
    }

    /**
     * Get the authenticated user's statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_bookings' => $user->bookings()->count(),
            'upcoming_trips' => $user->bookings()
                ->where('check_in_date', '>=', now())
                ->where('booking_status', 'confirmed')
                ->count(),
            'past_trips' => $user->bookings()
                ->where('check_out_date', '<', now())
                ->whereIn('booking_status', ['completed', 'cancelled'])
                ->count(),
            'reviews' => $user->reviews()->count(),
            'loyalty_points' => $user->loyalty_points ?? 0,
            'member_since' => $user->created_at->format('F Y'),
        ];
        
        return $this->sendResponse($stats, 'User statistics retrieved');
    }
}
