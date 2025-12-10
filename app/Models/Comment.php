<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['content', 'user_id', 'post_id'];

    protected $with = ['user'];

    protected static $logAttributes = ['content'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'comment';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['content'])
            ->setDescriptionForEvent(fn(string $eventName) => "Yorum {$eventName} yapıldı");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}