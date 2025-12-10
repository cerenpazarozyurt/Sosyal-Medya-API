<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;

class BookmarkController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/posts/{post}/bookmark",
     *     tags={"Bookmark"},
     *     summary="Gönderiyi kaydet / kayıttan çıkar",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Gönderi kaydedildi"),
     *             @OA\Property(property="saved", type="boolean", example=true)
     *         )
     *     )
     * )
     */
    public function toggle(Post $post)
    {
        $user = auth()->user();
        $user->bookmarks()->toggle($post->id);
        $saved = $user->bookmarks()->where('post_id', $post->id)->exists();

        return response()->json([
            'message' => $saved ? 'Gönderi kaydedildi' : 'Gönderi kayıtlardan çıkarıldı',
            'saved' => $saved
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/saved-posts",
     *     tags={"Bookmark"},
     *     summary="Kaydedilen gönderiler",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Kaydedilen postlar",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $posts = auth()->user()->bookmarks()
            ->active()
            ->with(['user:id,name,username,avatar'])
            ->latest()
            ->paginate(15);

        return response()->json($posts);
    }
}