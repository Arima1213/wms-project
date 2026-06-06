<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category');

        if ($request->has('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'ilike', "%{$request->search}%")
                ->orWhere('sku', 'ilike', "%{$request->search}%")
            );
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $products = $query->orderBy('name')->paginate($request->get('per_page', 25));
        return response()->json($products);
    }

    public function show(string|int $product): JsonResponse
    {
        $product = Product::with('category', 'barcodes', 'prices')->findOrFail($product);
        return response()->json(['data' => $product]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku',
            'name' => 'required|string|max:200',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:50',
            'weight_kg' => 'nullable|numeric',
            'dimensions' => 'nullable|array',
            'min_stock' => 'nullable|integer',
            'reorder_point' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $product = Product::create($validated);
        return response()->json(['data' => $product], 201);
    }

    public function update(Request $request, string|int $product): JsonResponse
    {
        $product = Product::findOrFail($product);
        $product->update($request->validate([
            'name' => 'sometimes|string|max:200',
            'category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:50',
            'weight_kg' => 'nullable|numeric',
            'dimensions' => 'nullable|array',
            'min_stock' => 'nullable|integer',
            'reorder_point' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]));
        return response()->json(['data' => $product]);
    }

    public function destroy(string|int $product): JsonResponse
    {
        $product = Product::findOrFail($product);
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);
        $products = Product::where(fn($q) => $q
            ->where('name', 'ilike', "%{$request->q}%")
            ->orWhere('sku', 'ilike', "%{$request->q}%")
            ->orWhere('barcode', 'ilike', "%{$request->q}%")
        )->limit(20)->get();
        return response()->json(['data' => $products]);
    }

    public function locations(string|int $product): JsonResponse
    {
        $product = Product::findOrFail($product);
        $locations = $product->inventoryLocations()->with('warehouse', 'zone', 'rack', 'slot')->get();
        return response()->json(['data' => $locations]);
    }
}