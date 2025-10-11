<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\AccommodationController;
use App\Http\Controllers\API\ActivityController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\DesignerProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Get public content
Route::get('/accommodations', [AccommodationController::class, 'index']);
Route::get('/accommodations/{slug}', [AccommodationController::class, 'show']);
Route::get('/accommodations/types', [AccommodationController::class, 'getTypes']);
Route::get('/accommodations/amenities', [AccommodationController::class, 'getAmenities']);
Route::get('/accommodations/{id}/availability', [AccommodationController::class, 'checkAvailability']);

Route::get('/activities', [ActivityController::class, 'index']);
Route::get('/activities/featured', [ActivityController::class, 'featured']);
Route::get('/activities/categories', [ActivityController::class, 'getCategories']);
Route::get('/activities/{slug}', [ActivityController::class, 'show']);
Route::get('/activities/{id}/similar', [ActivityController::class, 'similar']);

// Designer profiles
Route::get('/designers', [DesignerProfileController::class, 'index']);
Route::get('/designers/{slug}', [DesignerProfileController::class, 'show']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    
    // Accommodation routes
    Route::middleware('can:manage_accommodations')->group(function () {
        Route::post('/accommodations', [AccommodationController::class, 'store']);
        Route::put('/accommodations/{id}', [AccommodationController::class, 'update']);
        Route::delete('/accommodations/{id}', [AccommodationController::class, 'destroy']);
        Route::post('/accommodations/{id}/toggle-status', [AccommodationController::class, 'toggleStatus']);
    });
    
    // Activity routes
    Route::middleware('can:manage_activities')->group(function () {
        Route::post('/activities', [ActivityController::class, 'store']);
        Route::put('/activities/{id}', [ActivityController::class, 'update']);
        Route::delete('/activities/{id}', [ActivityController::class, 'destroy']);
        Route::post('/activities/{id}/toggle-status', [ActivityController::class, 'toggleStatus']);
    });
    
    // Booking routes
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/{bookingNumber}', [BookingController::class, 'show']);
        Route::put('/{bookingNumber}', [BookingController::class, 'update']);
        Route::post('/{bookingNumber}/cancel', [BookingController::class, 'cancel']);
        Route::get('/{bookingNumber}/invoice', [BookingController::class, 'getInvoice']);
        Route::get('/calendar/availability', [BookingController::class, 'calendar']);
    });
    
    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::get('/methods', [PaymentController::class, 'getPaymentMethods']);
        Route::post('/process', [PaymentController::class, 'processPayment']);
        Route::get('/history', [PaymentController::class, 'paymentHistory']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::post('/{id}/refund', [PaymentController::class, 'processRefundRequest']);
        Route::get('/{id}/verify', [PaymentController::class, 'verifyPayment']);
    });
    
    // Review routes
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::post('/', [ReviewController::class, 'store']);
        Route::get('/{id}', [ReviewController::class, 'show']);
        Route::put('/{id}', [ReviewController::class, 'update']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
        Route::post('/{id}/helpful', [ReviewController::class, 'toggleHelpful']);
        Route::post('/{id}/report', [ReviewController::class, 'report']);
    });
    
    // Message routes
    Route::prefix('messages')->group(function () {
        Route::get('/conversations', [MessageController::class, 'conversations']);
        Route::get('/conversations/recent', [MessageController::class, 'recentConversations']);
        Route::post('/conversations', [MessageController::class, 'startConversation']);
        Route::get('/conversations/{conversationId}', [MessageController::class, 'getConversation']);
        Route::post('/conversations/{conversationId}/messages', [MessageController::class, 'sendMessage']);
        Route::post('/conversations/{conversationId}/read', [MessageController::class, 'markAsRead']);
        Route::post('/upload-attachment', [MessageController::class, 'uploadAttachment']);
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);
        Route::get('/search-users', [MessageController::class, 'searchUsers']);
    });
    
    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsReadSingle']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::delete('/', [NotificationController::class, 'clearAll']);
        Route::get('/preferences', [NotificationController::class, 'getPreferences']);
        Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
    });
    
    // Designer profile routes
    Route::prefix('designer')->group(function () {
        Route::get('/profile', [DesignerProfileController::class, 'myProfile']);
        Route::post('/profile', [DesignerProfileController::class, 'store']);
        Route::put('/profile', [DesignerProfileController::class, 'updateMyProfile']);
        Route::post('/profile/toggle-availability', [DesignerProfileController::class, 'toggleAvailability']);
        
        // Admin only routes for managing designer profiles
        Route::middleware('role:admin')->group(function () {
            Route::put('/profiles/{id}', [DesignerProfileController::class, 'update']);
            Route::delete('/profiles/{id}', [DesignerProfileController::class, 'destroy']);
        });
    });
    
    // Admin routes
    Route::middleware('role:admin')->group(function () {
        // Role and permission management
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::get('/roles/{id}', [RoleController::class, 'show']);
        Route::put('/roles/{id}', [RoleController::class, 'update']);
        Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
        Route::post('/roles/{id}/toggle-status', [RoleController::class, 'toggleStatus']);
        Route::get('/permissions', [RoleController::class, 'getPermissions']);
        Route::post('/roles/{id}/sync-permissions', [RoleController::class, 'syncPermissions']);
        
        // Admin reports
        Route::get('/reports/bookings', [BookingController::class, 'bookingReport']);
        Route::get('/reports/revenue', [PaymentController::class, 'revenueReport']);
        Route::get('/reports/user-activity', [UserController::class, 'activityReport']);
    });
});

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
    ], 404);
});
