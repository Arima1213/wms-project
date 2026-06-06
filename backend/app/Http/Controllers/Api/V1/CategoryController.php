<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProductCategory::query();
        if ($request->has('search')) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }
        $categories = $query->orderBy('name')->get();
        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $category = ProductCategory::create($request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]));
        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, string|int $category): JsonResponse
    {
        $category = ProductCategory::findOrFail($category);
        $category->update($request->only(['name', 'code', 'description', 'is_active']));
        return response()->json(['data' => $category]);
    }

    public function destroy(string|int $category): JsonResponse
    {
        $category = ProductCategory::findOrFail($category);
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}