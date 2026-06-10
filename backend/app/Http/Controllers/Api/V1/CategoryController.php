<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::with('parent');
        if ($request->has('search')) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }
        $categories = $query->orderBy('name')->get();
        return CategoryResource::collection($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $category = ProductCategory::create($request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]));
        return response()->json(['data' => new CategoryResource($category)], 201);
    }

    public function show(string|int $category): CategoryResource
    {
        $category = ProductCategory::with('parent', 'children')->findOrFail($category);
        return new CategoryResource($category);
    }

    public function update(Request $request, string|int $category): JsonResponse
    {
        $category = ProductCategory::findOrFail($category);
        $category->update($request->only(['name', 'code', 'description', 'is_active']));
        return response()->json(['data' => new CategoryResource($category->fresh())]);
    }

    public function destroy(string|int $category): JsonResponse
    {
        $category = ProductCategory::findOrFail($category);
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
