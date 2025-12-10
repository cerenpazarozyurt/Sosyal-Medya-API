<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Like extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['user_id', 'post_id'];

    protected static $logAttributes = ['user_id', 'post_id'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'like';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'post_id'])
            ->setDescriptionForEvent(fn(string $eventName) => "Post beğenisi {$eventName}");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}