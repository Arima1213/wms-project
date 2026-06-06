<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(Webhook::latest()->paginate($request->get('per_page', 20))); }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string', 'url' => 'required|url', 'events' => 'required|array', 'events.*' => 'string', 'headers' => 'nullable|array']);
        $data['secret'] = bin2hex(random_bytes(32));
        $data['created_by'] = $request->user()->id;
        return response()->json(Webhook::create($data), 201);
    }

    public function show(Webhook $webhook): JsonResponse { return response()->json($webhook->load('deliveries')); }

    public function destroy(Webhook $webhook): JsonResponse { $webhook->delete(); return response()->json(null, 204); }

    public function toggle(Request $request, Webhook $webhook): JsonResponse { $webhook->update(['is_active' => !$webhook->is_active]); return response()->json($webhook); }
}
