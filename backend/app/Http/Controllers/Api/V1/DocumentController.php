<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('user:id,name')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->orderByDesc('created_at');

        $data = $query->paginate($request->get('per_page', 20));
        return DocumentResource::collection($data);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'minio');

        $doc = Document::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'original_name' => $file->getClientOriginalName(),
            'type' => $request->type,
            'size' => $file->getSize(),
            'path' => $path,
            'disk' => 'minio',
        ]);

        return response()->json(['data' => new DocumentResource($doc)], 201);
    }

    public function show(int $id): JsonResponse
    {
        $doc = Document::with('user')->findOrFail($id);
        $url = Storage::disk('minio')->url($doc->path);
        return response()->json(['data' => new DocumentResource($doc), 'url' => $url]);
    }

    public function destroy(int $id): JsonResponse
    {
        $doc = Document::findOrFail($id);
        Storage::disk('minio')->delete($doc->path);
        $doc->delete();
        return response()->json(null, 204);
    }
}
