<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);

        $notifications = Notification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', User::class)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', User::class)
            ->findOrFail($id);

        $notification->update(['read_at' => now()]);

        return response()->json([
            'data' => $notification,
            'message' => 'Notifikasi ditandai sudah dibaca',
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $updated = Notification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => [
                'updated' => $updated,
            ],
            'message' => 'Semua notifikasi ditandai sudah dibaca',
        ]);
    }
}
