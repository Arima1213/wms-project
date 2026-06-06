<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Uom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Uom::query();
        if ($request->boolean('active_only')) $query->where('is_active', true);
        return response()->json($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string|max:20|unique:uoms,code', 'name' => 'required|string|max:100', 'symbol' => 'required|string|max:10', 'type' => 'nullable|in:unit,weight,volume,length', 'conversion_factor' => 'nullable|numeric', 'base_uom_id' => 'nullable|exists:uoms,id']);
        return response()->json(Uom::create($data), 201);
    }

    public function show(string $id): JsonResponse { return response()->json(Uom::findOrFail($id)); }

    public function update(Request $request, string $id): JsonResponse
    {
        $uom = Uom::findOrFail($id);
        $uom->update($request->validated());
        return response()->json($uom);
    }

    public function destroy(string $id): JsonResponse
    {
        Uom::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
