<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;
use App\Models\Comment;

class NewCommentNotification extends Notification
{
    use Queueable;

    public function __construct(public User $commenter, public Comment $comment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_comment',
            'message' => $this->commenter->name . ' gönderine yorum yaptı: "' . Str::limit($this->comment->content, 50) . '"',
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name,
            'commenter_avatar' => $this->commenter->avatar_url,
            'post_id' => $this->comment->post->id,
            'comment_id' => $this->comment->id,
        ];
    }
}