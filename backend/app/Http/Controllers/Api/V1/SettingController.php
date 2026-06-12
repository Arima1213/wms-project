<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Setting::class);

        $settings = Setting::all()->pluck('value', 'key');
        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('update', Setting::class);
        $data = $request->all();
        
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Settings updated']);
    }
}
