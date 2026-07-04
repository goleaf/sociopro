<?php

namespace App\Actions\Comments;

use App\Enums\UserRole;
use App\Models\Comments;
use App\Models\User;

class DeleteCommentAction
{
    /**
     * @return array{alertMessage: string, fadeOutElem: string}|array{}
     */
    public function handle(Comments $comment, ?User $actor = null): array
    {
        $commentId = $comment->comment_id;

        $query = Comments::query()->whereKey($commentId);
        if ($actor instanceof User && $actor->user_role !== UserRole::Admin->value) {
            $query->where('user_id', $actor->id);
        }

        if ($query->delete() < 1) {
            return [];
        }

        return [
            'alertMessage' => get_phrase('Comment Deleted Successfully'),
            'fadeOutElem' => '#comment_'.$commentId,
        ];
    }
}
