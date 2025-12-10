<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Bildirimlerim",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Bildirimler listesi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $request->user()->unreadNotifications->markAsRead();

        return response()->json($notifications);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     tags={"Notifications"},
     *     summary="Okunmamış bildirim sayısı",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Okunmamış sayısı",
     *         @OA\JsonContent(
     *             @OA\Property(property="unread_count", type="integer", example=5)
     *         )
     *     )
     * )
     */
    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications->count()
        ]);
    }
}