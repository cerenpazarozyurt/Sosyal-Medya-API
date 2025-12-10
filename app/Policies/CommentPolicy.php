<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->user_id === $user->id) {
            return true;
        }


        if (!$comment->relationLoaded('post')) {
            $comment->load('post');
        }
        
        $post = $comment->post;
        
        if ($post && $post->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function restore(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }
}