<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * @OA\Put(
     *     path="/api/me",
     *     tags={"Profile"},
     *     summary="Profil düzenleme",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Ahmet Yeni"),
     *             @OA\Property(property="username", type="string", example="ahmet123"),
     *             @OA\Property(property="bio", type="string", example="123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil güncellendi",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|unique:users,username,' . auth()->id(),
            'bio' => 'nullable|string|max:500'
        ]);

        $request->user()->update($request->only('name', 'username', 'bio'));

        $user = $request->user();
        return response()->json([
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
            'bio' => $user->bio,
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count,
            'posts_count' => $user->posts_count,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/me/avatar",
     *     tags={"Profile"},
     *     summary="Profil fotoğrafı yükle",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="avatar", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar yüklendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="avatar_url", type="string")
     *         )
     *     )
     * )
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048']);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Avatar yüklendi',
            'avatar_url' => asset('storage/' . $path)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/me/avatar",
     *     tags={"Profile"},
     *     summary="Profil fotoğrafını sil",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Avatar silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Avatar silindi")
     *         )
     *     )
     * )
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }
        return response()->json(['message' => 'Avatar silindi']);
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Kullanıcı ara",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", example="ahmet")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kullanıcı listesi",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');

        $blockedIds = auth()->user()->blockedUsers()->pluck('blocked_id');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->select('id', 'name', 'username', 'avatar', 'bio')
            ->where('id', '!=', auth()->id())
            ->whereNotIn('id', $blockedIds) 
            ->paginate(20);

        $users->getCollection()->transform(function ($user) {
            return [
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'followers_count' => $user->followers_count,
                'following_count' => $user->following_count,
                'posts_count' => $user->posts_count,
            ];
        });

        return response()->json($users);
    }

}