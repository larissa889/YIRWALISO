<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends BaseController
{
    /**
     * Display a listing of the users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('view_users')) {
            return $this->sendForbidden('You do not have permission to view users');
        }

        $query = User::with('role');

        // Filtrage par rôle
        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // Filtrage par statut
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Recherche par nom ou email
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        if (in_array($sortField, ['name', 'email', 'created_at', 'last_login_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return $this->sendPaginated($users, 'Users retrieved successfully');
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create_users')) {
            return $this->sendForbidden('You do not have permission to create users');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            ],
            'phone' => 'required|string|max:20|unique:users',
            'role_id' => [
                'required',
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($request) {
                    // Empêcher un utilisateur non admin de créer un admin
                    if ($value === 1 && !$request->user()->isAdmin()) {
                        $fail('You do not have permission to create admin users.');
                    }
                },
            ],
            'is_active' => 'boolean',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role_id' => $request->role_id,
            'is_active' => $request->is_active ?? true,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
        ]);

        return $this->sendResponse($user->load('role'), 'User created successfully', 201);
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Request $request)
    {
        $user = User::with('role')->find($id);

        if (is_null($user)) {
            return $this->sendNotFound('User not found');
        }

        // Vérifier les permissions
        $requestingUser = $request->user();
        if (!$requestingUser->can('view_users') && $requestingUser->id != $user->id) {
            return $this->sendForbidden('You do not have permission to view this user');
        }

        return $this->sendResponse($user, 'User retrieved successfully');
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (is_null($user)) {
            return $this->sendNotFound('User not found');
        }

        $requestingUser = $request->user();
        
        // Vérifier les permissions
        if (!$requestingUser->can('edit_users') && $requestingUser->id != $user->id) {
            return $this->sendForbidden('You do not have permission to update this user');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id),
            ],
            'role_id' => [
                'sometimes',
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($request, $user) {
                    // Empêcher la modification du rôle d'un admin par un non-admin
                    if ($user->isAdmin() && !$request->user()->isAdmin()) {
                        $fail('You do not have permission to change the role of an admin user.');
                    }
                    // Empêcher de créer un admin si on n'est pas admin
                    if ($value === 1 && !$request->user()->isAdmin()) {
                        $fail('You do not have permission to assign admin role.');
                    }
                },
            ],
            'is_active' => 'sometimes|boolean',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $user->update($request->all());

        return $this->sendResponse($user->load('role'), 'User updated successfully');
    }

    /**
     * Update the authenticated user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request, $id = null)
    {
        // Si aucun ID n'est fourni, utiliser l'utilisateur connecté
        $userId = $id ?? $request->user()->id;
        $user = User::find($userId);

        if (is_null($user)) {
            return $this->sendNotFound('User not found');
        }

        $requestingUser = $request->user();
        
        // Vérifier les permissions (soit l'utilisateur modifie son propre mot de passe, soit il a la permission)
        if (!$requestingUser->can('edit_users') && $requestingUser->id != $user->id) {
            return $this->sendForbidden('You do not have permission to update this user\'s password');
        }

        $validator = Validator::make($request->all(), [
            'current_password' => [
                'required_if:' . $requestingUser->id . ',=,' . $user->id,
                'current_password',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                'different:current_password',
            ],
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return $this->sendResponse([], 'Password updated successfully');
    }

    /**
     * Toggle the active status of the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id)
    {
        if (!$request->user()->can('edit_users')) {
            return $this->sendForbidden('You do not have permission to update user status');
        }

        $user = User::find($id);

        if (is_null($user)) {
            return $this->sendNotFound('User not found');
        }

        // Empêcher de désactiver son propre compte
        if ($user->id === $request->user()->id) {
            return $this->sendError('You cannot deactivate your own account', [], 403);
        }

        // Empêcher de désactiver un administrateur si on n'est pas admin
        if ($user->isAdmin() && !$request->user()->isAdmin()) {
            return $this->sendForbidden('You do not have permission to deactivate an admin user');
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return $this->sendResponse($user, "User {$status} successfully");
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete_users')) {
            return $this->sendForbidden('You do not have permission to delete users');
        }

        $user = User::find($id);

        if (is_null($user)) {
            return $this->sendNotFound('User not found');
        }

        // Empêcher de supprimer son propre compte
        if ($user->id === $request->user()->id) {
            return $this->sendError('You cannot delete your own account', [], 403);
        }

        // Empêcher de supprimer un administrateur si on n'est pas admin
        if ($user->isAdmin() && !$request->user()->isAdmin()) {
            return $this->sendForbidden('You do not have permission to delete an admin user');
        }

        $user->delete();

        return $this->sendResponse([], 'User deleted successfully');
    }

    /**
     * Get the list of available roles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoles()
    {
        $roles = Role::where('is_active', true)->get();
        return $this->sendResponse($roles, 'Roles retrieved successfully');
    }
}
