<?php

namespace App\Actions\Posts;

use App\Models\Posts;
use App\Models\User;
use App\Support\Files\FileUploader;
use Illuminate\Http\Request;

class UpdatePostAction
{
    public function __construct(private readonly StorePostMediaFilesAction $storePostMediaFiles) {}

    /**
     * @return array{status: int, message: string}
     */
    public function handle(Posts $post, User $user, Request $request): array
    {
        $postId = $post->post_id;
        $data = [
            'privacy' => $request->privacy,
            'mobile_app_image' => FileUploader::upload($request->mobile_app_image, 'public/storage/post/images/'),
            'updated_at' => time(),
        ];

        if (isset($request->tagged_users_id) && is_array($request->tagged_users_id)) {
            $data['tagged_user_ids'] = json_encode($request->tagged_users_id);
        }

        if (isset($request->feeling_and_activity_id) && ! empty($request->feeling_and_activity_id)) {
            $data['activity_id'] = $request->feeling_and_activity_id;
        }

        if (isset($request->description) && ! empty($request->description)) {
            $data['description'] = $request->description;
        }

        Posts::query()
            ->whereKey($postId)
            ->update($data);

        $this->storePostMediaFiles->handle($postId, $user, $request);

        return [
            'status' => 200,
            'message' => 'Your post successfully updated',
        ];
    }
}
