<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

class AuthController extends BaseController
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'accept_terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'email_verification_token' => Str::random(60),
        ]);

        // Assign default role (you can customize this based on your requirements)
        $user->assignRole('user');

        // Send email verification notification
        // $user->sendEmailVerificationNotification();

        // Create token for immediate login
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->sendResponse(
            [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'User registered successfully. Please check your email to verify your account.',
            201
        );
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember_me', false);

        if (!Auth::attempt($credentials, $remember)) {
            return $this->sendError('Invalid login credentials', [], 401);
        }

        $user = $request->user();

        // Check if email is verified (uncomment if you implement email verification)
        // if (!$user->hasVerifiedEmail()) {
        //     return $this->sendError('Please verify your email address before logging in.', [], 403);
        // }

        // Check if user is active
        if (isset($user->is_active) && !$user->is_active) {
            return $this->sendError('Your account has been deactivated. Please contact support.', [], 403);
        }

        // Revoke all tokens (optional, for security)
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->sendResponse(
            [
                'user' => $user->load('roles', 'permissions'),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'Login successful'
        );
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return $this->sendResponse([], 'Successfully logged out');
    }

    /**
     * Get the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return $this->sendResponse(
            $request->user()->load('roles', 'permissions'),
            'User retrieved successfully'
        );
    }

    /**
     * Update the user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $user->update($validator->validated());

        return $this->sendResponse(
            $user->fresh(),
            'Profile updated successfully'
        );
    }

    /**
     * Update the user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'current_password.current_password' => 'The current password is incorrect.'
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->sendResponse([], 'Password updated successfully');
    }

    /**
     * Send a password reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->sendResponse(
                [],
                'Password reset link sent to your email'
            );
        }

        return $this->sendError(
            'Unable to send password reset link',
            ['email' => __($status)],
            422
        );
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResponse(
                [],
                'Password has been reset successfully'
            );
        }

        return $this->sendError(
            'Unable to reset password',
            ['email' => __($status)],
            422
        );
    }

    /**
     * Verify the user's email address.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return $this->sendError('Invalid verification link', [], 400);
        }

        $user = User::findOrFail($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return $this->sendError('Email already verified', [], 400);
        }

        $user->markEmailAsVerified();

        return $this->sendResponse(
            [],
            'Email verified successfully. You can now login.'
        );
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->sendError('Email already verified', [], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->sendResponse(
            [],
            'Verification link sent to your email address'
        );
    }
}
