<?php

namespace App\Actions\Posts;

use App\Enums\ContentStatus;
use App\Models\LiveStreaming;
use App\Models\Posts;
use App\Models\User;
use App\Support\Files\FileUploader;
use Illuminate\Http\Request;

class StorePostAction
{
    public function __construct(private readonly StorePostMediaFilesAction $storePostMediaFiles) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, Request $request): array
    {
        $data = $this->postData($user, $request);
        $postId = Posts::query()->insertGetId($data);

        $response = [];
        if ($postId >= 0) {
            $response = [
                'status' => 200,
                'message' => 'Your post successfully publidhed',
            ];
        }

        $this->storePostMediaFiles->handle($postId, $user, $request);

        if ($data['post_type'] === 'live_streaming') {
            $liveStreamingUrl = route('go.live', $postId);
            LiveStreaming::query()->insert([
                'publisher' => $data['publisher'],
                'publisher_id' => $postId,
                'user_id' => $user->id,
                'details' => json_encode(['link' => $liveStreamingUrl, 'status' => true]),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

            return [
                'open_new_tab' => $liveStreamingUrl,
                'reload' => 0,
                'status' => 1,
                'function' => 0,
                'messageShowOn' => '[name=about]',
                'message' => get_phrase('Post has been added to your timeline'),
            ];
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function postData(User $user, Request $request): array
    {
        $createdAt = now()->toDateTimeString();

        return [
            'user_id' => $user->id,
            'privacy' => $request->privacy,
            'publisher' => isset($request->publisher) && ! empty($request->publisher) ? $request->publisher : 'post',
            'publisher_id' => $this->publisherId($user, $request),
            'post_type' => isset($request->post_type) && ! empty($request->post_type) ? $request->post_type : 'general',
            'tagged_user_ids' => json_encode(is_array($request->tagged_users_id) ? $request->tagged_users_id : []),
            'activity_id' => isset($request->feeling_and_activity_id) && ! empty($request->feeling_and_activity_id)
                ? $request->feeling_and_activity_id
                : 0,
            'location' => isset($request->address) && ! empty($request->address) ? $request->address : '',
            'description' => isset($request->description) && ! empty($request->description) ? $request->description : '',
            'mobile_app_image' => FileUploader::upload($request->mobile_app_image, 'public/storage/post/images/'),
            'status' => ContentStatus::Active->value,
            'user_reacts' => json_encode([]),
            'shared_user' => json_encode([]),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    private function publisherId(User $user, Request $request): mixed
    {
        if (isset($request->event_id) && ! empty($request->event_id)) {
            return $request->event_id;
        }

        if (isset($request->page_id) && ! empty($request->page_id)) {
            return $request->page_id;
        }

        if (isset($request->group_id) && ! empty($request->group_id)) {
            return $request->group_id;
        }

        return $user->id;
    }
}
