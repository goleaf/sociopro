<?php

namespace App\Actions\Posts;

use App\Models\Posts;

class DeletePostAction
{
    /**
     * @return array{alertMessage: string, fadeOutElem: string}|array{}
     */
    public function handle(Posts $post): array
    {
        $postId = $post->post_id;

        if (! $post->delete()) {
            return [];
        }

        return [
            'alertMessage' => get_phrase('Post Deleted Successfully'),
            'fadeOutElem' => '#postIdentification'.$postId,
        ];
    }
}
