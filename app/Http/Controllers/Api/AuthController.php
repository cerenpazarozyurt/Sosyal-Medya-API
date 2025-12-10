<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/**
 * @OA\Info(
 *     title="Sosyal Medya API",
 *     description="backend"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Kayıt Ol",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="Ali Veli"),
     *             @OA\Property(property="email", type="string", format="email", example="ali@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Kayıt başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Kayıt başarılı"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="avatar_url", type="string"),
     *                 @OA\Property(property="followers_count", type="integer"),
     *                 @OA\Property(property="following_count", type="integer"),
     *                 @OA\Property(property="posts_count", type="integer")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|laravel_sanctum_abc123...")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
            ->log('User registered');

        return response()->json([
            'message' => 'Kayıt başarılı',
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'followers_count' => $user->followers_count,
                'following_count' => $user->following_count,
                'posts_count' => $user->posts_count,
            ],
            'token' => $token
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Giriş Yap",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="ali@example.com"),
     *             @OA\Property(property="password", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Giriş başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Giriş başarılı"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string", example="1|laravel_sanctum_abc123...")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Bilgiler yanlış")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            activity()
                ->withProperties(['ip' => $request->ip(), 'email' => $request->email])
                ->log('Failed login attempt');

            throw ValidationException::withMessages(['email' => 'Bilgiler yanlış']);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('User logged in');

        return response()->json([
            'message' => 'Giriş başarılı',
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'followers_count' => $user->followers_count,
                'following_count' => $user->following_count,
                'posts_count' => $user->posts_count,
            ],
            'token' => $token
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Çıkış Yap",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Çıkış yapıldı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Çıkış yapıldı")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        activity()->causedBy($request->user())->log('User logged out');

        return response()->json(['message' => 'Çıkış yapıldı']);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Auth"},
     *     summary="Giriş yapan kullanıcı bilgileri",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="avatar_url", type="string"),
     *             @OA\Property(property="followers_count", type="integer"),
     *             @OA\Property(property="following_count", type="integer"),
     *             @OA\Property(property="posts_count", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Yetkisiz")
     * )
     */
    public function me(Request $request)
    {
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
}