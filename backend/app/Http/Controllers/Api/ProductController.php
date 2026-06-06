<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'unit']);

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('sku', 'ilike', '%' . $request->search . '%')
                  ->orWhere('code', 'ilike', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $products = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:products,code',
            'sku' => 'required|string|max:30|unique:products,sku',
            'barcode' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
            'unit_id' => 'nullable|exists:uoms,id',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'min_stock' => 'nullable|numeric',
            'max_stock' => 'nullable|numeric',
            'reorder_point' => 'nullable|numeric',
            'safety_stock' => 'nullable|numeric',
            'product_type' => 'nullable|in:standard,oversized,hazmat,cold',
            'hs_code' => 'nullable|string|max:20',
        ]);

        $product = Product::create($data);
        $product->load(['category', 'unit']);

        return response()->json($product, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Product::with(['category', 'unit', 'batches'])->findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
            'unit_id' => 'nullable|exists:uoms,id',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'min_stock' => 'nullable|numeric',
            'max_stock' => 'nullable|numeric',
            'reorder_point' => 'nullable|numeric',
            'safety_stock' => 'nullable|numeric',
            'product_type' => 'nullable|in:standard,oversized,hazmat,cold',
        ]));
        $product->load(['category', 'unit']);
        return response()->json($product);
    }

    public function destroy(string $id): JsonResponse
    {
        Product::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function search(Request $request): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->q . '%')
                  ->orWhere('sku', 'ilike', '%' . $request->q . '%')
                  ->orWhere('barcode', $request->q);
            })
            ->limit(20)
            ->get(['id', 'code', 'sku', 'name', 'barcode']);

        return response()->json($products);
    }

    public function findBySku(string $sku): JsonResponse
    {
        $product = Product::where('sku', $sku)->orWhere('barcode', $sku)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $product->load(['category', 'unit', 'batches']);
        return response()->json($product);
    }
}
