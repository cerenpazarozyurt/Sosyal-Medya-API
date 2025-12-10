<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ActivityLogController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'Sosyal Medya API'
    ], 200);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Profile
    Route::put('/me', [UserController::class, 'updateProfile']);
    Route::post('/me/avatar', [UserController::class, 'uploadAvatar']);
    Route::delete('/me/avatar', [UserController::class, 'deleteAvatar']);

    // Users
    Route::get('/users', [UserController::class, 'search']);

    // Posts
    Route::apiResource('posts', PostController::class)->except(['create', 'edit', 'show']);
    Route::get('/my-posts', [PostController::class, 'myPosts']);
    Route::get('/feed', [PostController::class, 'feed']);
    Route::post('/posts/{post}/archive', [PostController::class, 'archive']);
    Route::post('/posts/{post}/unarchive', [PostController::class, 'unarchive']);

    // Likes
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle']);
    Route::get('/posts/{post}/likes', [LikeController::class, 'likers']);

    // Comments
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->where('comment', '[0-9]+');

    // Follow
    Route::post('/follow/{user}', [FollowController::class, 'toggle']);
    Route::get('/users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowController::class, 'following']);

    // Block
    Route::post('/block/{user}', [BlockController::class, 'toggle']);
    Route::get('/blocked-users', [BlockController::class, 'index']);

    // Bookmark
    Route::post('/posts/{post}/bookmark', [BookmarkController::class, 'toggle']);
    Route::get('/saved-posts', [BookmarkController::class, 'index']);

    // Stories
    Route::post('/stories', [StoryController::class, 'store']);
    Route::get('/stories/feed', [StoryController::class, 'feed']);
    Route::post('/stories/{story}/view', [StoryController::class, 'view']);
    Route::delete('/stories/{story}', [StoryController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Logs
    Route::get('/admin/logs', [ActivityLogController::class, 'index']);
});