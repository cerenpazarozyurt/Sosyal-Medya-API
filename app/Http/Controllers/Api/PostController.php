<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    private function sanitizeUser($user)
    {
        if (!$user) {
            return null;
        }
        return [
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=6366f1&color=fff&bold=true',
        ];
    }
    /**
     * @OA\Get(
     *     path="/api/posts",
     *     tags={"Posts"},
     *     summary="Tüm postlar (kronolojik)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Post listesi",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function index()
    {
        $posts = Post::with(['user:id,name,username,avatar'])
                     ->latest()
                     ->paginate(15);

        $posts->getCollection()->transform(function ($post) {
            if ($post->user) {
                $post->user = $this->sanitizeUser($post->user);
            }
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * @OA\Post(
     *     path="/api/posts",
     *     tags={"Posts"},
     *     summary="Yeni gönderi oluştur (metin + birden fazla fotoğraf/video opsiyonel + konum)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"caption"},
     *                 @OA\Property(property="caption", type="string", example="Tatil fotoğrafları!"),
     *                 @OA\Property(
     *                     property="media[]",
     *                     description="Birden fazla fotoğraf veya video (max 10 tane)",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *                 @OA\Property(property="location", type="string", example="Antalya, Türkiye")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post oluşturuldu",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="caption", type="string"),
     *             @OA\Property(property="media", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="media_urls", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="user", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'caption'   => 'required|string|max:2200',
            'media.*'   => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:20480',
            'media'     => 'nullable|array|max:10',
            'location'  => 'nullable|string|max:100'
        ]);

        $mediaPaths = [];

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('posts', 'public');
                $mediaPaths[] = $path;
            }
        }

        $post = Post::create([
            'caption'   => $request->caption,
            'media'     => $mediaPaths,
            'location'  => $request->location,
            'user_id'   => auth()->id(),
        ]);

        $post->load('user:id,name,username,avatar');
        $post->user = $this->sanitizeUser($post->user);
        
        return response()->json($post, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/posts/{post}",
     *     tags={"Posts"},
     *     summary="Post düzenle (sadece sahibi)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="caption", type="string", example="Güncellenmiş başlık"),
     *             @OA\Property(property="location", type="string", example="İstanbul, Türkiye")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post güncellendi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Post başarıyla güncellendi"),
     *             @OA\Property(property="post", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Yetkisiz işlem"),
     *     @OA\Response(response=404, description="Post bulunamadı")
     * )
     */
    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $request->validate([
            'caption' => 'sometimes|required|string|max:2200',
            'location' => 'nullable|string|max:100'
        ]);

        $post->update($request->only('caption', 'location'));

        $post->load('user:id,name,username,avatar');
        $post->user = $this->sanitizeUser($post->user);

        return response()->json([
            'message' => 'Post başarıyla güncellendi',
            'post' => $post
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/posts/{post}",
     *     tags={"Posts"},
     *     summary="Post sil (sadece sahibi)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Post başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post başarıyla silindi")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Yetkisiz işlem"),
     *     @OA\Response(response=404, description="Post bulunamadı")
     * )
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        if ($post->media && is_array($post->media)) {
            foreach ($post->media as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $post->delete();

        return response()->json(['message' => 'Post başarıyla silindi'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/feed",
     *     tags={"Posts"},
     *     summary="Ana sayfa (takip edilenler + kendi postlar)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Feed",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function feed()
    {
        $followingIds = auth()->user()->following()->pluck('users.id');
        $blockedIds = auth()->user()->blockedUsers()->pluck('blocked_id');

        $posts = Post::active()
            ->where(function ($query) use ($followingIds) {
                $query->whereIn('user_id', $followingIds)
                      ->orWhere('user_id', auth()->id());
            })
            ->whereNotIn('user_id', $blockedIds)
            ->with(['user:id,name,username,avatar'])
            ->latest()
            ->paginate(15);

        $posts->getCollection()->transform(function ($post) {
            if ($post->user) {
                $post->user = $this->sanitizeUser($post->user);
            }
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * @OA\Get(
     *     path="/api/my-posts",
     *     tags={"Posts"},
     *     summary="Kendi postlarımı listele",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Kendi postlar",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function myPosts()
    {
        $posts = auth()->user()
            ->posts()
            ->active()
            ->with(['user:id,name,username,avatar'])
            ->latest()
            ->paginate(15);

        $posts->getCollection()->transform(function ($post) {
            if ($post->user) {
                $post->user = $this->sanitizeUser($post->user);
            }
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * @OA\Post(
     *     path="/api/posts/{post}/archive",
     *     tags={"Posts"},
     *     summary="Gönderiyi arşivle",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Gönderi başarıyla arşivlendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Gönderi başarıyla arşivlendi"),
     *             @OA\Property(property="post", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Yetkisiz işlem"),
     *     @OA\Response(response=404, description="Post bulunamadı")
     * )
     */
    public function archive(Post $post)
    {
        $this->authorize('update', $post);
        
        // Zaten arşivlenmiş mi kontrol et
        if ($post->archived_at) {
            $post->load('user:id,name,username,avatar');
            $post->user = $this->sanitizeUser($post->user);
            return response()->json([
                'message' => 'Gönderi zaten arşivlenmiş',
                'post' => $post
            ], 200);
        }
        
        $post->update(['archived_at' => now()]);
        
        $post->load('user:id,name,username,avatar');
        $post->user = $this->sanitizeUser($post->user);
        
        return response()->json([
            'message' => 'Gönderi başarıyla arşivlendi',
            'post' => $post
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/posts/{post}/unarchive",
     *     tags={"Posts"},
     *     summary="Gönderiyi arşivden çıkar",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Gönderi başarıyla arşivden çıkarıldı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Gönderi başarıyla arşivden çıkarıldı"),
     *             @OA\Property(property="post", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Yetkisiz işlem"),
     *     @OA\Response(response=404, description="Post bulunamadı")
     * )
     */
    public function unarchive(Post $post)
    {
        $this->authorize('update', $post);
        
        // Zaten arşivlenmemiş mi kontrol et
        if (!$post->archived_at) {
            $post->load('user:id,name,username,avatar');
            $post->user = $this->sanitizeUser($post->user);
            return response()->json([
                'message' => 'Gönderi zaten arşivlenmemiş',
                'post' => $post
            ], 200);
        }
        
        $post->update(['archived_at' => null]);
        
        $post->load('user:id,name,username,avatar');
        $post->user = $this->sanitizeUser($post->user);
        
        return response()->json([
            'message' => 'Gönderi başarıyla arşivden çıkarıldı',
            'post' => $post
        ], 200);
    }
}