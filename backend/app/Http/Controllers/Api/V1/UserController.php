<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('roles:id,name');
        if ($request->has('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('email', 'ilike', '%' . $request->search . '%');
        }
        $data = $query->paginate($request->get('per_page', 15));
        return UserResource::collection($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
        ]);

        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return response()->json(['data' => new UserResource($user->load('roles'))], 201);
    }

    public function show(int $id): UserResource
    {
        $user = User::with('roles:id,name', 'permissions:id,name')->findOrFail($id);
        return new UserResource($user);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user->update($validated);

        if ($request->has('roles')) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json(['data' => new UserResource($user->load('roles'))]);
    }

    public function destroy(int $id): JsonResponse
    {
        User::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function roles(): JsonResponse
    {
        return response()->json(['data' => \Spatie\Permission\Models\Role::all()]);
    }

    public function permissions(): JsonResponse
    {
        return response()->json(['data' => \Spatie\Permission\Models\Permission::all()]);
    }
}
