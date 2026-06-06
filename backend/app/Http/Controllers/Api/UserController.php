<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');
        if ($request->has('search')) $query->where('name', 'ilike', '%' . $request->search . '%')->orWhere('email', 'ilike', '%' . $request->search . '%');
        if ($request->boolean('active_only')) $query->where('is_active', true);
        return response()->json($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:8', 'phone' => 'nullable|string|max:20', 'roles' => 'nullable|array', 'roles.*' => 'exists:roles,name',
        ]);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'phone' => $data['phone'] ?? null]);
        if (!empty($data['roles'])) $user->assignRole($data['roles']);
        return response()->json($user->load('roles'), 201);
    }

    public function show(string $id): JsonResponse { return response()->json(User::with('roles')->findOrFail($id)); }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->validate(['name' => 'nullable|string|max:255', 'email' => 'nullable|email|unique:users,email,' . $id, 'phone' => 'nullable|string|max:20', 'is_active' => 'nullable|boolean', 'roles' => 'nullable|array', 'roles.*' => 'exists:roles,name']);
        if (isset($data['password'])) $data['password'] = Hash::make($data['password']);
        $user->update(array_filter($data, fn($k) => $k !== 'roles', ARRAY_FILTER_USE_KEY));
        if (isset($data['roles'])) { $user->syncRoles($data['roles']); }
        return response()->json($user->load('roles'));
    }

    public function destroy(string $id): JsonResponse { User::findOrFail($id)->delete(); return response()->json(null, 204); }
}
