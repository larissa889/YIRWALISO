<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends BaseController
{
    /**
     * Display a listing of the roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('view_roles')) {
            return $this->sendForbidden('You do not have permission to view roles');
        }

        $query = Role::with('permissions');

        // Filtrage par statut
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Recherche par nom
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Tri
        $sortField = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        
        if (in_array($sortField, ['name', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $roles = $query->paginate($perPage);

        return $this->sendPaginated($roles, 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create_roles')) {
            return $this->sendForbidden('You do not have permission to create roles');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:roles',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        // Attacher les permissions si fournies
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return $this->sendResponse($role->load('permissions'), 'Role created successfully', 201);
    }

    /**
     * Display the specified role.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Request $request)
    {
        if (!$request->user()->can('view_roles')) {
            return $this->sendForbidden('You do not have permission to view roles');
        }

        $role = Role::with('permissions')->find($id);

        if (is_null($role)) {
            return $this->sendNotFound('Role not found');
        }

        return $this->sendResponse($role, 'Role retrieved successfully');
    }

    /**
     * Update the specified role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!$request->user()->can('edit_roles')) {
            return $this->sendForbidden('You do not have permission to update roles');
        }

        $role = Role::find($id);

        if (is_null($role)) {
            return $this->sendNotFound('Role not found');
        }

        // Empêcher la modification des rôles système
        if ($role->is_system) {
            return $this->sendError('System roles cannot be modified', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('roles')->ignore($role->id),
            ],
            'description' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $role->update($request->only(['name', 'description', 'is_active']));

        // Mettre à jour les permissions si fournies
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return $this->sendResponse($role->load('permissions'), 'Role updated successfully');
    }

    /**
     * Toggle the active status of the specified role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id)
    {
        if (!$request->user()->can('edit_roles')) {
            return $this->sendForbidden('You do not have permission to update roles');
        }

        $role = Role::find($id);

        if (is_null($role)) {
            return $this->sendNotFound('Role not found');
        }

        // Empêcher la désactivation des rôles système
        if ($role->is_system) {
            return $this->sendError('System roles cannot be deactivated', [], 403);
        }

        // Vérifier si des utilisateurs ont ce rôle avant de le désactiver
        if ($role->is_active && $role->users()->exists()) {
            return $this->sendError('Cannot deactivate a role that is assigned to users', [], 422);
        }

        $role->update([
            'is_active' => !$role->is_active
        ]);

        $status = $role->is_active ? 'activated' : 'deactivated';
        return $this->sendResponse($role, "Role {$status} successfully");
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete_roles')) {
            return $this->sendForbidden('You do not have permission to delete roles');
        }

        $role = Role::find($id);

        if (is_null($role)) {
            return $this->sendNotFound('Role not found');
        }

        // Empêcher la suppression des rôles système
        if ($role->is_system) {
            return $this->sendError('System roles cannot be deleted', [], 403);
        }

        // Vérifier si des utilisateurs ont ce rôle avant de le supprimer
        if ($role->users()->exists()) {
            return $this->sendError('Cannot delete a role that is assigned to users', [], 422);
        }

        $role->delete();

        return $this->sendResponse([], 'Role deleted successfully');
    }

    /**
     * Get all permissions grouped by category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPermissions(Request $request)
    {
        if (!$request->user()->can('view_permissions')) {
            return $this->sendForbidden('You do not have permission to view permissions');
        }

        $permissions = Permission::all()
            ->groupBy('group')
            ->map(function ($group) {
                return $group->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'description' => $permission->description,
                        'is_system' => $permission->is_system,
                    ];
                });
            });

        return $this->sendResponse($permissions, 'Permissions retrieved successfully');
    }

    /**
     * Sync permissions for a specific role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncPermissions(Request $request, $id)
    {
        if (!$request->user()->can('edit_roles')) {
            return $this->sendForbidden('You do not have permission to update roles');
        }

        $role = Role::find($id);

        if (is_null($role)) {
            return $this->sendNotFound('Role not found');
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        // Ne pas autoriser la modification des permissions pour les rôles système
        if ($role->is_system && !$request->user()->isAdmin()) {
            return $this->sendForbidden('You do not have permission to update system role permissions');
        }

        $role->permissions()->sync($request->permissions);

        return $this->sendResponse($role->load('permissions'), 'Role permissions updated successfully');
    }
}
