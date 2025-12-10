<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Notifications\NewCommentNotification;

class CommentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/posts/{post}/comments",
     *     tags={"Comments"},
     *     summary="Yorum yap",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Harika fotoğraf!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Yorum eklendi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request, Post $post)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        $comment = $post->comments()->create([
            'content' => $request->content,
            'user_id' => auth()->id(),
        ]);

        if ($post->user_id !== auth()->id()) {
            $post->user->notify(new NewCommentNotification(auth()->user(), $comment));
        }

        $comment->load('user:id,name,username,avatar');
        $comment->user = $comment->user ? [
            'name' => $comment->user->name,
            'username' => $comment->user->username,
            'avatar_url' => $comment->user->avatar ? asset('storage/' . $comment->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($comment->user->name) . '&background=6366f1&color=fff&bold=true',
        ] : null;

        return response()->json($comment, 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/comments/{comment}",
     *     tags={"Comments"},
     *     summary="Yorum sil (sadece sahibi veya post sahibi)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Yorum silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Yorum başarıyla silindi")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Yetkisiz işlem"),
     *     @OA\Response(response=404, description="Yorum bulunamadı")
     * )
     */
    public function destroy(Comment $comment)
    {
        if (!$comment->relationLoaded('post')) {
            $comment->load('post');
        }
        
        // Policy kontrolü
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Yorum başarıyla silindi'], 200);
    }
}