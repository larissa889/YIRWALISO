<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class NotificationController extends BaseController
{
    /**
     * Get user notifications
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));
        
        return $this->sendPaginated($notifications, 'Notifications retrieved');
    }

    /**
     * Mark notifications as read
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request)
    {
        $user = $request->user();
        
        $updated = $user->unreadNotifications()->update(['read_at' => now()]);
        
        return $this->sendResponse(
            ['updated_count' => $updated], 
            'Notifications marked as read'
        );
    }

    /**
     * Mark a specific notification as read
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsReadSingle($id, Request $request)
    {
        $user = $request->user();
        
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return $this->sendResponse($notification, 'Notification marked as read');
    }

    /**
     * Get unread notifications count
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $count = $user->unreadNotifications()->count();
        
        return $this->sendResponse(
            ['unread_count' => $count],
            'Unread notifications count'
        );
    }

    /**
     * Clear all notifications
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearAll(Request $request)
    {
        $user = $request->user();
        $user->notifications()->delete();
        
        return $this->sendResponse([], 'All notifications cleared');
    }

    /**
     * Get notification preferences
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        
        $preferences = $user->notification_preferences ?? [
            'email' => true,
            'push' => true,
            'sms' => false,
        ];
        
        return $this->sendResponse(
            ['preferences' => $preferences],
            'Notification preferences retrieved'
        );
    }

    /**
     * Update notification preferences
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.email' => 'boolean',
            'preferences.push' => 'boolean',
            'preferences.sms' => 'boolean',
        ]);
        
        $user->notification_preferences = $validated['preferences'];
        $user->save();
        
        return $this->sendResponse(
            ['preferences' => $user->notification_preferences],
            'Notification preferences updated'
        );
    }
}
