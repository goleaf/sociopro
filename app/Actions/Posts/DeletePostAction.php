<?php

namespace App\Actions\Posts;

use App\Enums\UserRole;
use App\Models\Posts;
use App\Models\User;

class DeletePostAction
{
    /**
     * @return array{alertMessage: string, fadeOutElem: string}|array{}
     */
    public function handle(Posts $post, ?User $actor = null): array
    {
        $postId = $post->post_id;

        $query = Posts::query()->whereKey($postId);
        if ($actor instanceof User && $actor->user_role !== UserRole::Admin->value) {
            $query->where('user_id', $actor->id);
        }

        if ($query->delete() < 1) {
            return [];
        }

        return [
            'alertMessage' => get_phrase('Post Deleted Successfully'),
            'fadeOutElem' => '#postIdentification'.$postId,
        ];
    }
}
