<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Media_files;
use App\Models\Message_thrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_web_chat_video_upload_uses_public_storage_disk(): void
    {
        Storage::fake('public');
        $this->disableS3Uploads();

        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $realFilePath = null;

        try {
            $this
                ->actingAs($sender)
                ->post(route('chat.save'), [
                    'reciver_id' => $receiver->id,
                    'messagecenter' => 'chat',
                    'message' => 'Video upload',
                    'thumbsup' => 0,
                    'multiple_files' => [
                        UploadedFile::fake()->create('clip.mp4', 128, 'video/mp4'),
                    ],
                ])
                ->assertOk();

            $media = $this->latestChatVideoFor($sender);
            $realFilePath = base_path('storage/chat/videos/'.$media->file_name.'.mp4');

            $this->assertMatchesRegularExpression('/\A[A-Za-z0-9]{40}\z/', $media->file_name);
            Storage::disk('public')->assertExists('chat/videos/'.$media->file_name.'.mp4');
        } finally {
            if ($realFilePath !== null) {
                File::delete($realFilePath);
            }
        }
    }

    public function test_existing_web_chat_video_upload_uses_public_storage_disk(): void
    {
        Storage::fake('public');
        $this->disableS3Uploads();

        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $this->createMessageThread($sender, $receiver);
        $realFilePath = null;

        try {
            $this
                ->actingAs($sender)
                ->post(route('chat.save'), [
                    'reciver_id' => $receiver->id,
                    'messagecenter' => 'chat',
                    'message' => 'Follow-up video upload',
                    'thumbsup' => 0,
                    'multiple_files' => [
                        UploadedFile::fake()->create('reply.mp4', 128, 'video/mp4'),
                    ],
                ])
                ->assertOk();

            $media = $this->latestChatVideoFor($sender);
            $realFilePath = base_path('storage/chat/videos/'.$media->file_name.'.mp4');

            $this->assertMatchesRegularExpression('/\A[A-Za-z0-9]{40}\z/', $media->file_name);
            Storage::disk('public')->assertExists('chat/videos/'.$media->file_name.'.mp4');
        } finally {
            if ($realFilePath !== null) {
                File::delete($realFilePath);
            }
        }
    }

    public function test_api_chat_video_upload_uses_public_storage_disk(): void
    {
        Storage::fake('public');
        $this->disableS3Uploads();

        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $response = $this
            ->withToken($sender->createToken('chat-upload-test')->plainTextToken)
            ->post(route('api.chat.messages.store'), [
                'reciver_id' => $receiver->id,
                'messagecenter' => 'chat',
                'message' => 'API video upload',
                'thumbsup' => 0,
                'multiple_files' => [
                    UploadedFile::fake()->create('api-clip.mp4', 128, 'video/mp4'),
                ],
            ])
            ->assertOk();

        $this->assertStringNotContainsString('validationError', $response->getContent(), $response->getContent());

        $media = $this->latestChatVideoFor($sender);

        $this->assertMatchesRegularExpression('/\A[A-Za-z0-9]{40}\.mp4\z/', $media->file_name);
        Storage::disk('public')->assertExists('chat/videos/'.$media->file_name);
    }

    private function disableS3Uploads(): void
    {
        DB::table('settings')->updateOrInsert(
            ['type' => 'amazon_s3'],
            ['description' => json_encode(['active' => '0'])]
        );
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'user_role' => UserRole::General->value,
        ]);
    }

    private function createMessageThread(User $sender, User $receiver): Message_thrade
    {
        return Message_thrade::factory()
            ->between($sender, $receiver)
            ->create();
    }

    private function latestChatVideoFor(User $user): Media_files
    {
        $media = Media_files::query()
            ->where('user_id', $user->id)
            ->where('file_type', 'video')
            ->latest('id')
            ->first();

        $this->assertInstanceOf(
            Media_files::class,
            $media,
            Media_files::query()->get(['id', 'user_id', 'chat_id', 'file_name', 'file_type'])->toJson()
        );

        return $media;
    }
}
