<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewFollowerNotification;

class FollowController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/follow/{user}",
     *     tags={"Follow"},
     *     summary="Takip et / Takipten çık",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Başarılı")
     * )
     */
    public function toggle(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Kendini takip edemezsin'], 400);
        }

        $authUser = auth()->user();

        $isFollowing = $authUser->isFollowing($user);

        if ($isFollowing) {
            $authUser->following()->detach($user->id);
            
            return response()->json([
                'message' => 'Takipten çıkıldı',
                'following' => false
            ]);
        }

        $authUser->following()->attach($user->id);

        // Bildirim gönder
        try {
            $user->notify(new NewFollowerNotification($authUser));
        } catch (\Exception $e) {
            \Log::warning('Follow notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Takip edildi',
            'following' => true
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{user}/followers",
     *     tags={"Follow"},
     *     summary="Takipçiler",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Takipçi listesi")
     * )
     */
    public function followers(User $user)
    {
        $followers = $user->followers()
            ->select('users.name', 'users.username', 'users.avatar')
            ->paginate(20);

        // Avatar URL'ini ekle ve ID'yi kaldır
        $followers->getCollection()->transform(function ($follower) {
            return [
                'name' => $follower->name,
                'username' => $follower->username,
                'avatar_url' => $follower->avatar ? asset('storage/' . $follower->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($follower->name) . '&background=6366f1&color=fff&bold=true',
            ];
        });

        return response()->json($followers);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{user}/following",
     *     tags={"Follow"},
     *     summary="Takip edilenler",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Takip edilenler listesi")
     * )
     */
    public function following(User $user)
    {
        $following = $user->following()
            ->select('users.name', 'users.username', 'users.avatar')
            ->paginate(20);

        $following->getCollection()->transform(function ($follow) {
            return [
                'name' => $follow->name,
                'username' => $follow->username,
                'avatar_url' => $follow->avatar ? asset('storage/' . $follow->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($follow->name) . '&background=6366f1&color=fff&bold=true',
            ];
        });

        return response()->json($following);
    }
}