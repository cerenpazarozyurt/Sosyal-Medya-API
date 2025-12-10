<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Post extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'caption',
        'media',     
        'location',
        'user_id',
        'archived_at', 
    ];

    protected $casts = [
        'media' => 'array',           
        'archived_at' => 'datetime', 
    ];

    protected $appends = [
        'media_urls',     
        'likes_count',
        'comments_count',
        'is_liked'
    ];

    // Log ayarları
    protected static $logAttributes = ['caption', 'location', 'media'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'post';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['caption', 'location', 'media'])
            ->setDescriptionForEvent(fn(string $eventName) => "Gönderi {$eventName} yapıldı");
    }

    public function getMediaUrlsAttribute(): Collection
    {
        if (empty($this->media)) {
            return collect();
        }

        return collect($this->media)->map(fn($path) => asset('storage/' . $path));
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    public function getIsLikedAttribute(): bool
    {
        if (!auth()->check()) return false;
        return $this->likes()->where('user_id', auth()->id())->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function likedBy(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'likes', 'post_id', 'user_id')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }
}