<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/logs",
     *     tags={"Admin"},
     *     summary="Sistem logları",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tüm aktiviteler",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="log_name", type="string", example="post"),
     *                 @OA\Property(property="description", type="string", example="Post created by user Ali"),
     *                 @OA\Property(property="causer", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Yetkisiz erişim")
     * )
     */
    public function index()
    {
        $logs = Activity::with('causer:id,name,username')
                        ->latest()
                        ->paginate(50);

        return response()->json($logs);
    }
}