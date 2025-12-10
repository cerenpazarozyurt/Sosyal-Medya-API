<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\Post;

class NewLikeNotification extends Notification
{
    use Queueable;

    public function __construct(public User $liker, public Post $post) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_like',
            'message' => $this->liker->name . ' gönderini beğendi',
            'liker_id' => $this->liker->id,
            'liker_name' => $this->liker->name,
            'liker_avatar' => $this->liker->avatar_url,
            'post_id' => $this->post->id,
        ];
    }
}