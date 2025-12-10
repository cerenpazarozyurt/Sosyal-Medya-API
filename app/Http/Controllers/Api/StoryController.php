<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/stories",
     *     tags={"Stories"},
     *     summary="Yeni hikaye ekle",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"media"},
     *                 @OA\Property(property="media", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Hikaye eklendi",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'media' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:51200'
        ]);

        $path = $request->file('media')->store('stories', 'public');
        $type = str_contains($request->file('media')->getMimeType(), 'video') ? 'video' : 'image';

        $story = Story::create([
            'user_id' => auth()->id(),
            'media' => $path,
            'type' => $type,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json($story, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/stories/feed",
     *     tags={"Stories"},
     *     summary="Takip edilenlerin aktif hikayeleri",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Hikaye listesi",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     )
     * )
     */
    public function feed()
    {
        $followingIds = auth()->user()->following()->pluck('users.id');

        $usersWithStories = \App\Models\User::whereIn('id', $followingIds)
            ->orWhere('id', auth()->id())
            ->whereHas('activeStories')
            ->with(['activeStories' => fn($q) => $q->orderBy('created_at', 'desc')])
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar_url' => $user->avatar_url,
                    'stories' => $user->activeStories->map(fn($story) => [
                        'id' => $story->id,
                        'media_url' => $story->media_url,
                        'type' => $story->type,
                        'created_at' => $story->created_at,
                        'views_count' => $story->views_count,
                    ])
                ];
            });

        return response()->json($usersWithStories);
    }

    /**
     * @OA\Post(
     *     path="/api/stories/{story}/view",
     *     tags={"Stories"},
     *     summary="Hikayeyi görüntüle",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="story", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Hikaye başarıyla görüntülendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Hikaye başarıyla görüntülendi"),
     *             @OA\Property(property="story", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Hikaye süresi dolmuş"),
     *     @OA\Response(response=404, description="Hikaye bulunamadı")
     * )
     */
    public function view(Story $story)
    {
        // Hikaye süresi dolmuş mu kontrol et
        if ($story->expires_at && $story->expires_at->isPast()) {
            return response()->json([
                'message' => 'Bu hikaye süresi dolmuş'
            ], 400);
        }

        // Kendi hikayesi değilse ve daha önce görüntülenmemişse ekle
        if ($story->user_id !== auth()->id() && !$story->viewers()->where('user_id', auth()->id())->exists()) {
            $story->viewers()->attach(auth()->id());
        }

        // Story'yi user ilişkisi ile birlikte yükle
        $story->load('user:id,name,username,avatar');

        return response()->json([
            'message' => 'Hikaye başarıyla görüntülendi',
            'story' => $story
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/stories/{story}",
     *     tags={"Stories"},
     *     summary="Hikaye sil (sadece sahibi)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="story", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Hikaye başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Hikaye başarıyla silindi")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Yetkisiz işlem"),
     *     @OA\Response(response=404, description="Hikaye bulunamadı")
     * )
     */
    public function destroy(Story $story)
    {
        $this->authorize('delete', $story);

        if ($story->media) {
            Storage::disk('public')->delete($story->media);
        }

        $story->delete();

        return response()->json([
            'message' => 'Hikaye başarıyla silindi'
        ], 200);
    }
}