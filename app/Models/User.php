<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'avatar',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['avatar_url', 'followers_count', 'following_count', 'posts_count'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'bio', 'avatar'])
            ->setDescriptionForEvent(fn(string $eventName) => "Profil {$eventName} güncellendi");
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6366f1&color=fff&bold=true';
    }

    public function getFollowersCountAttribute(): int
    {
        return $this->followers()->count();
    }

    public function getFollowingCountAttribute(): int
    {
        return $this->following()->count();
    }

    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }

    // İlişkiler
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function activeStories()
    {
        return $this->stories()->where('expires_at', '>', now())->orderBy('created_at', 'desc');
    }

    // Takip ilişkileri
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('users.id', $user->id)->exists();
    }

    // Engelleme
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocker_id', 'blocked_id')->withTimestamps();
    }

    public function isBlocking(User $user): bool
    {
        return $this->blockedUsers()->where('blocked_id', $user->id)->exists();
    }

    // Bookmark
    public function bookmarks()
    {
        return $this->belongsToMany(Post::class, 'bookmarks')->withTimestamps();
    }
}