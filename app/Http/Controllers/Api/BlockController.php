<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class BlockController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/block/{user}",
     *     tags={"Block"},
     *     summary="Kullanıcıyı engelle / engeli kaldır",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Kullanıcı engellendi"),
     *             @OA\Property(property="blocked", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Kendini engelleyemezsin")
     * )
     */
    public function toggle(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Kendini engelleyemezsin'], 400);
        }

        if (auth()->user()->isBlocking($user)) {
            auth()->user()->blockedUsers()->detach($user->id);
            return response()->json([
                'message' => 'Engel kaldırıldı',
                'blocked' => false
            ]);
        }

        auth()->user()->blockedUsers()->attach($user->id);

        return response()->json([
            'message' => 'Kullanıcı engellendi',
            'blocked' => true
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/blocked-users",
     *     tags={"Block"},
     *     summary="Engellediğim kullanıcılar",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Engellenen kullanıcılar listesi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="avatar", type="string", nullable=true)
     *             ))
     *         )
     *     )
     * )
     */
public function index()
{
    $blocked = auth()->user()->blockedUsers()
        ->select('users.name', 'users.username', 'users.avatar')
        ->paginate(20);

    $blocked->getCollection()->transform(function ($user) {
        return [
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=6366f1&color=fff&bold=true',
        ];
    });

    return response()->json($blocked);
}
}