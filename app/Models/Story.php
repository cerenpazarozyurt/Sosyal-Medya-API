<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Story extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'media', 'type', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $appends = ['media_url', 'views_count', 'is_expired'];

    // Aktif hikayeler (24 saat dolmadı)
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    // Medya URL
    public function getMediaUrlAttribute(): string
    {
        return asset('storage/' . $this->media);
    }

    // Kaç kişi gördü
    public function getViewsCountAttribute(): int
    {
        return $this->viewers()->count();
    }

    // Süresi doldu mu?
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    // İlişkiler
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Kimler gördü
    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'story_views')
                    ->withTimestamps()
                    ->orderBy('story_views.viewed_at', 'desc');
    }
}