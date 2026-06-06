<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index(): JsonResponse { return response()->json(Role::with('permissions')->get()); }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|unique:roles,name', 'permissions' => 'nullable|array', 'permissions.*' => 'exists:permissions,name']);
        $role = Role::create(['name' => $data['name']]);
        if (!empty($data['permissions'])) $role->givePermissionTo($data['permissions']);
        return response()->json($role->load('permissions'), 201);
    }

    public function show(string $id): JsonResponse { return response()->json(Role::with('permissions')->findOrFail($id)); }

    public function update(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $data = $request->validate(['name' => 'nullable|string|unique:roles,name,' . $id, 'permissions' => 'nullable|array', 'permissions.*' => 'exists:permissions,name']);
        if (isset($data['name'])) $role->update(['name' => $data['name']]);
        if (isset($data['permissions'])) $role->syncPermissions($data['permissions']);
        return response()->json($role->load('permissions'));
    }

    public function destroy(string $id): JsonResponse { Role::findOrFail($id)->delete(); return response()->json(null, 204); }
}
