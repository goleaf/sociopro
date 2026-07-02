<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Chat;
use App\Models\Marketplace;
use App\Models\MediaFile;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LegacyWebChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_chat_inbox_renders_existing_legacy_thread_and_previous_chat_list(): void
    {
        $sender = $this->activeUser(['name' => 'Legacy Sender']);
        $receiver = $this->activeUser(['name' => 'Legacy Receiver']);
        $previousContact = $this->activeUser(['name' => 'Previous Contact']);

        $thread = $this->createThread($sender, $receiver);
        $previousThread = $this->createThread($previousContact, $sender);

        $this->createChat($thread, $receiver, $sender, 'Existing legacy inbox message');
        $this->createChat($previousThread, $previousContact, $sender, 'Previous contact message');

        $response = $this->actingAs($sender)->get(route('chat', ['receiver' => $receiver->id]));

        $response
            ->assertOk()
            ->assertViewIs('frontend.chat.index')
            ->assertSee('Legacy Receiver')
            ->assertSee('Existing legacy inbox message');

        $response->assertViewHas('reciver_data', function (User $viewReceiver) use ($receiver): bool {
            return $viewReceiver->is($receiver);
        });

        $response->assertViewHas('previousChatList', function ($previousChatList) use ($thread, $previousThread): bool {
            return $previousChatList->pluck('id')->contains($thread->id)
                && $previousChatList->pluck('id')->contains($previousThread->id);
        });

        $this->assertDatabaseHas('message_thrades', [
            'id' => $thread->id,
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'chat',
        ]);
    }

    public function test_web_marketplace_product_chat_authorization_preserves_current_behavior(): void
    {
        $buyer = $this->activeUser();
        $seller = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = Marketplace::factory()->forOwner($seller)->create();

        $this->actingAs($buyer)
            ->get(route('chat', ['receiver' => $seller->id, 'product' => $product->id]))
            ->assertOk();

        $this->actingAs($buyer)
            ->get(route('chat', ['receiver' => $otherUser->id, 'product' => $product->id]))
            ->assertForbidden();

        $this->actingAs($seller)
            ->get(route('chat', ['receiver' => $seller->id, 'product' => $product->id]))
            ->assertForbidden();
    }

    public function test_web_first_message_creates_legacy_thread_and_chat_row_with_legacy_response_shape(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $response = $this->actingAs($sender)->post(route('chat.save'), [
            'reciver_id' => $receiver->id,
            'message' => 'First legacy message',
            'messagecenter' => 'chat',
            'thumbsup' => 1,
        ]);

        $payload = $this->decodeLegacyJsonResponse($response);

        $this->assertSame('#message_body', $payload['appendElement']);
        $this->assertSame('#messageResetBox', $payload['clickTo']);
        $this->assertArrayHasKey('content', $payload);
        $this->assertStringContainsString('single-item-countable', $payload['content']);
        $this->assertArrayNotHasKey('replaceUrl', $payload);

        $thread = MessageThread::query()->firstOrFail();
        $chat = Chat::query()->firstOrFail();

        $this->assertDatabaseHas('message_thrades', [
            'id' => $thread->id,
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'chatcenter' => 'chat',
        ]);

        $this->assertDatabaseHas('chats', [
            'id' => $chat->id,
            'sender_id' => $sender->id,
            'reciver_id' => $receiver->id,
            'message' => 'First legacy message',
            'message_thrade' => $thread->id,
            'chatcenter' => 'chat',
            'thumbsup' => 1,
            'file' => '1',
        ]);
    }

    public function test_web_product_message_response_includes_current_product_url_contract(): void
    {
        $buyer = $this->activeUser();
        $seller = $this->activeUser();
        $product = Marketplace::factory()->forOwner($seller)->create();

        $response = $this->actingAs($buyer)->post(route('chat.save'), [
            'reciver_id' => $seller->id,
            'product_id' => $product->id,
            'message' => 'Product chat message',
            'messagecenter' => 'chat',
            'thumbsup' => 0,
        ]);

        $payload = $this->decodeLegacyJsonResponse($response);

        $this->assertSame('#message_body', $payload['appendElement']);
        $this->assertSame('#message_body', $payload['replaceUrl']);
        $this->assertSame(route('chat', $seller->id), $payload['url']);
    }

    public function test_web_second_message_reuses_existing_thread_in_both_directions(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $this->actingAs($sender)->post(route('chat.save'), [
            'reciver_id' => $receiver->id,
            'message' => 'First direction',
            'messagecenter' => 'chat',
            'thumbsup' => 0,
        ])->assertOk();

        $thread = MessageThread::query()->firstOrFail();

        $this->actingAs($receiver)->post(route('chat.save'), [
            'reciver_id' => $sender->id,
            'message' => 'Second direction',
            'messagecenter' => 'chat',
            'thumbsup' => 0,
        ])->assertOk();

        $this->assertSame(1, MessageThread::query()->count());
        $this->assertSame(2, Chat::query()->count());

        $this->assertDatabaseHas('chats', [
            'sender_id' => $receiver->id,
            'reciver_id' => $sender->id,
            'message' => 'Second direction',
            'message_thrade' => $thread->id,
            'chatcenter' => 'chat',
        ]);
    }

    public function test_web_chat_upload_accepts_valid_image_and_video_and_records_media_files(): void
    {
        $this->disableS3Uploads();
        Storage::fake('public');

        $sender = $this->activeUser();
        $receiver = $this->activeUser();

        $this->actingAs($sender)->post(route('chat.save'), [
            'reciver_id' => $receiver->id,
            'message' => 'Image attachment',
            'messagecenter' => 'chat',
            'thumbsup' => 0,
            'multiple_files' => [
                UploadedFile::fake()->image('legacy-image.jpg', 20, 20),
            ],
        ])->assertOk();

        $this->actingAs($sender)->post(route('chat.save'), [
            'reciver_id' => $receiver->id,
            'message' => 'Video attachment',
            'messagecenter' => 'chat',
            'thumbsup' => 0,
            'multiple_files' => [
                UploadedFile::fake()->create('legacy-video.mp4', 64, 'video/mp4'),
            ],
        ])->assertOk();

        $imageMedia = MediaFile::query()->where('file_type', 'image')->firstOrFail();
        $videoMedia = MediaFile::query()->where('file_type', 'video')->firstOrFail();

        $this->assertSame($sender->id, $imageMedia->user_id);
        $this->assertNotNull($imageMedia->chat_id);
        $this->assertSame('public', $imageMedia->privacy);

        $this->assertSame($sender->id, $videoMedia->user_id);
        $this->assertNotNull($videoMedia->chat_id);
        $this->assertSame('public', $videoMedia->privacy);

        $this->assertNotEmpty(Storage::disk('public')->allFiles('chat/images'));
        Storage::disk('public')->assertExists('chat/videos/'.$videoMedia->file_name.'.mp4');
    }

    public function test_web_chat_read_route_marks_only_auth_users_unread_messages(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $otherUser = $this->activeUser();

        $thread = $this->createThread($sender, $receiver);
        $unrelatedThread = $this->createThread($receiver, $otherUser);

        $incoming = $this->createChat($thread, $receiver, $sender, 'Unread for sender');
        $outgoing = $this->createChat($thread, $sender, $receiver, 'Unread for receiver');
        $unrelated = $this->createChat($unrelatedThread, $receiver, $otherUser, 'Unrelated unread');

        $this->actingAs($sender)
            ->get(route('chat.read', ['id' => $receiver->id]))
            ->assertOk()
            ->assertContent('');

        $this->assertDatabaseHas('chats', ['id' => $incoming->id, 'read_status' => 1]);
        $this->assertDatabaseHas('chats', ['id' => $outgoing->id, 'read_status' => 0]);
        $this->assertDatabaseHas('chats', ['id' => $unrelated->id, 'read_status' => 0]);
    }

    public function test_web_chat_read_route_does_not_mark_messages_for_unrelated_user(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $unrelatedUser = $this->activeUser();

        $thread = $this->createThread($sender, $receiver);
        $incoming = $this->createChat($thread, $receiver, $sender, 'Unread for sender');

        $this->actingAs($unrelatedUser)
            ->get(route('chat.read', ['id' => $receiver->id]))
            ->assertOk()
            ->assertContent('');

        $this->assertDatabaseHas('chats', ['id' => $incoming->id, 'read_status' => 0]);
    }

    public function test_web_chat_load_returns_legacy_shape_and_marks_messages_read(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $wrongReceiver = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $incoming = $this->createChat($thread, $receiver, $sender, 'Load this unread message');

        $_GET['id'] = (string) $wrongReceiver->id;

        try {
            $response = $this->actingAs($sender)
                ->get(route('chat.load', ['id' => $receiver->id]));
        } finally {
            unset($_GET['id']);
        }

        $payload = $this->decodeLegacyJsonResponse($response);

        $this->assertSame('#message_body', $payload['appendElement']);
        $this->assertStringContainsString('Load this unread message', $payload['content']);
        $this->assertDatabaseHas('chats', ['id' => $incoming->id, 'read_status' => 1]);
    }

    public function test_web_remove_chat_deletes_message_and_redirects_for_participant(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $sender, $receiver, 'Message removed by participant');

        $this->actingAs($sender)
            ->from(route('chat', ['receiver' => $receiver->id]))
            ->get(route('remove.chat', $chat->id))
            ->assertRedirect(route('chat', ['receiver' => $receiver->id]));

        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
    }

    public function test_web_remove_chat_forbids_unrelated_authenticated_user(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $unrelatedUser = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $sender, $receiver, 'Message protected from unrelated user');

        $this->actingAs($unrelatedUser)
            ->from(route('chat', ['receiver' => $receiver->id]))
            ->get(route('remove.chat', $chat->id))
            ->assertForbidden();

        $this->assertDatabaseHas('chats', ['id' => $chat->id]);
    }

    public function test_web_react_chat_updates_reaction_and_returns_rendered_partial_contract(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $sender, $receiver, 'React to this message');

        $response = $this->actingAs($sender)->post(route('react.chat'), [
            'requestType' => 'update',
            'messageId' => $chat->id,
            'react' => 'love',
        ]);

        $payload = $this->decodeLegacyJsonResponse($response);

        $this->assertSame('#ShowReactId_'.$chat->id, $payload['elemSelector']);
        $this->assertArrayHasKey('content', $payload);
        $this->assertDatabaseHas('chats', ['id' => $chat->id, 'react' => 'love']);
    }

    public function test_web_react_chat_forbids_unrelated_authenticated_user(): void
    {
        $sender = $this->activeUser();
        $receiver = $this->activeUser();
        $unrelatedUser = $this->activeUser();
        $thread = $this->createThread($sender, $receiver);
        $chat = $this->createChat($thread, $sender, $receiver, 'Reaction protected from unrelated user');

        $this->actingAs($unrelatedUser)->post(route('react.chat'), [
            'requestType' => 'update',
            'messageId' => $chat->id,
            'react' => 'wow',
        ])->assertForbidden();

        $this->assertDatabaseHas('chats', ['id' => $chat->id, 'react' => null]);
    }

    public function test_web_chat_search_returns_html_for_existing_chat_contacts_only(): void
    {
        $sender = $this->activeUser(['name' => 'Search Sender']);
        $receiver = $this->activeUser(['name' => 'Legacy Search Contact']);
        $nonContact = $this->activeUser(['name' => 'Legacy Search Stranger']);
        $thread = $this->createThread($sender, $receiver);

        $this->createChat($thread, $receiver, $sender, 'Searchable latest message');

        $_GET['search'] = 'No Matches From Superglobal';

        try {
            $response = $this->actingAs($sender)
                ->get(route('search.chat', ['search' => 'Legacy Search']));
        } finally {
            unset($_GET['search']);
        }

        $response
            ->assertOk()
            ->assertSee('single-contact', false)
            ->assertSee('Legacy Search Contact', false)
            ->assertDontSee($nonContact->name, false);
    }

    public function test_web_chat_search_escapes_contact_name_and_last_message(): void
    {
        $sender = $this->activeUser(['name' => 'Search Sender']);
        $receiver = $this->activeUser(['name' => 'Unsafe Search <script>alert("name")</script>']);
        $thread = $this->createThread($sender, $receiver);

        $this->createChat($thread, $receiver, $sender, '<img src=x onerror=alert("message")>');

        $response = $this->actingAs($sender)
            ->get(route('search.chat', ['search' => 'Unsafe Search']));

        $response
            ->assertOk()
            ->assertSee(e($receiver->name), false)
            ->assertSee(e('<img src=x onerror=alert("message")>'), false)
            ->assertDontSee($receiver->name, false)
            ->assertDontSee('<img src=x onerror=alert("message")>', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
            'timezone' => 'UTC',
            'lastActive' => now()->subMinute(),
        ], $attributes));
    }

    private function createThread(User $sender, User $receiver, string $chatCenter = 'chat'): MessageThread
    {
        return MessageThread::factory()
            ->between($sender, $receiver)
            ->create(['chat_center' => $chatCenter]);
    }

    private function createChat(
        MessageThread $thread,
        User $sender,
        User $receiver,
        string $message,
        int $readStatus = 0
    ): Chat {
        return Chat::factory()
            ->forThread($thread)
            ->fromTo($sender, $receiver)
            ->create([
                'message' => $message,
                'read_status' => $readStatus,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeLegacyJsonResponse(TestResponse $response): array
    {
        $response->assertOk();

        $payload = json_decode($response->getContent(), true);

        $this->assertIsArray($payload);

        return $payload;
    }

    private function disableS3Uploads(): void
    {
        DB::table('settings')->where('type', 's3_file_system')->update(['description' => '0']);
    }
}
