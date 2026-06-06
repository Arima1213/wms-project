<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function index(Request $request)
    {
        $products = $this->productService->list(
            $request->only(['search', 'category_id', 'is_active']),
            $request->integer('per_page', 25)
        );

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());
        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function show(string|int $product): ProductResource
    {
        return new ProductResource($this->productService->show((int) $product));
    }

    public function update(UpdateProductRequest $request, string|int $product): JsonResponse
    {
        $updated = $this->productService->update((int) $product, $request->validated());
        return response()->json(['data' => new ProductResource($updated)]);
    }

    public function destroy(string|int $product): JsonResponse
    {
        $this->authorize('product.delete');
        $this->productService->delete((int) $product);
        return response()->json(['message' => 'Product deleted']);
    }
}