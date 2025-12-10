<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Notifications\NewLikeNotification;

class LikeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/posts/{post}/like",
     *     tags={"Likes"},
     *     summary="Beğen / beğeniyi kaldır",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="liked", type="boolean"),
     *             @OA\Property(property="likes_count", type="integer")
     *         )
     *     )
     * )
     */
    public function toggle(Post $post)
    {
        $user = auth()->user();

        $like = $post->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            return response()->json([
                'message' => 'Beğeni kaldırıldı',
                'liked' => false,
                'likes_count' => $post->likes()->count()
            ]);
        }

        $post->likes()->create(['user_id' => $user->id]);

        if ($post->user_id !== $user->id) {
            $post->user->notify(new NewLikeNotification($user, $post));
        }

        return response()->json([
            'message' => 'Beğenildi',
            'liked' => true,
            'likes_count' => $post->likes()->count()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/posts/{post}/likes",
     *     tags={"Likes"},
     *     summary="Beğenenler listesi",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Beğenenler",
     *     )
     * )
     */
    public function likers(Post $post)
    {
        $likers = $post->likedBy()
                       ->select('users.name', 'users.username', 'users.avatar')
                       ->paginate(20);

        $likers->getCollection()->transform(function ($liker) {
            return [
                'name' => $liker->name,
                'username' => $liker->username,
                'avatar_url' => $liker->avatar ? asset('storage/' . $liker->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($liker->name) . '&background=6366f1&color=fff&bold=true',
            ];
        });

        return response()->json([
            'likes_count' => $post->likes()->count(),
            'likers' => $likers
        ]);
    }
}