<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Herkes postları görebilir
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Herkes tek bir postu görebilir
     */
    public function view(User $user, Post $post): bool
    {
        return true;
    }

    /**
     * Giriş yapan herkes post oluşturabilir
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Sadece post sahibi düzenleyebilir
     */
    public function update(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    /**
     * Sadece post sahibi silebilir
     */
    public function delete(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    /**
     * Sadece post sahibi restore edebilir (soft delete kullanıyorsanız)
     */
    public function restore(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    /**
     * Sadece post sahibi kalıcı silebilir
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }
}
