<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Visibility;
use App\Http\Controllers\MainController;
use App\Models\Live_streamings;
use App\Models\Posts;
use App\Models\Setting;
use App\Models\User;
use App\Services\Zoom\ZoomMeetingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Tests\TestCase;

class ZoomMeetingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_streaming_creation_posts_zoom_meeting_and_stores_response_details(): void
    {
        $user = User::factory()->create(['name' => 'Live Host']);
        $post = $this->postFor($user, ['description' => 'A long live topic']);
        $this->configureZoom();

        Http::fake([
            'https://api.zoom.us/v2/users/me/meetings' => Http::response([
                'id' => 123456,
                'join_url' => 'https://zoom.test/join',
            ], 201),
        ]);

        $controller = app(MainController::class);
        $this->setControllerUser($controller, $user);

        $controller->create_live_streaming('post', $post->post_id);

        $stream = Live_streamings::query()
            ->where('publisher', 'post')
            ->where('publisher_id', $post->post_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame(123456, json_decode($stream->details, true)['id']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.zoom.us/v2/users/me/meetings'
            && $request->hasHeader('authorization')
            && $request['topic'] === 'A long live topic'
            && $request['type'] === MainController::MEETING_TYPE_SCHEDULE
            && $request['duration'] === 40);
    }

    public function test_live_streaming_update_patches_existing_zoom_meeting_details(): void
    {
        $user = User::factory()->create(['name' => 'Live Host']);
        $post = $this->postFor($user, ['description' => 'Updated live topic']);
        $stream = Live_streamings::create([
            'publisher' => 'post',
            'publisher_id' => $post->post_id,
            'user_id' => $user->id,
            'details' => json_encode([
                'id' => 456789,
                'topic' => 'Old topic',
                'join_url' => 'https://zoom.test/old',
            ]),
            'created_at' => time() - 60,
            'updated_at' => time() - 60,
        ]);
        $this->configureZoom();

        Http::fake([
            'https://api.zoom.us/v2/meetings/456789' => Http::response([], 204),
        ]);

        $controller = app(MainController::class);
        $this->setControllerUser($controller, $user);

        $controller->create_live_streaming('post', $post->post_id);

        $stream = Live_streamings::query()
            ->where('publisher', 'post')
            ->where('publisher_id', $post->post_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $details = json_decode($stream->details, true);

        $this->assertSame('Updated live topic', $details['topic']);
        $this->assertSame(456789, $details['id']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === 'https://api.zoom.us/v2/meetings/456789'
            && $request->hasHeader('authorization')
            && $request['topic'] === 'Updated live topic'
            && $request['type'] === MainController::MEETING_TYPE_SCHEDULE
            && $request['duration'] === 40);
    }

    public function test_zoom_time_conversion_logs_structured_context_without_raw_input(): void
    {
        $messages = [];
        Log::listen(function (MessageLogged $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame(
            '',
            app(ZoomMeetingClient::class)->toUnixTimeStamp('sensitive invalid datetime', 'Sensitive/Invalid')
        );

        $this->assertCount(1, $messages);
        $this->assertSame('warning', $messages[0]->level);
        $this->assertSame('zoom_time_conversion_failed', $messages[0]->message);
        $this->assertSame('to_unix_timestamp', $messages[0]->context['operation'] ?? null);
        $this->assertArrayHasKey('exception', $messages[0]->context);
        $this->assertArrayNotHasKey('message', $messages[0]->context);
        $this->assertArrayNotHasKey('date_time', $messages[0]->context);
        $this->assertArrayNotHasKey('timezone', $messages[0]->context);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function postFor(User $user, array $overrides = []): Posts
    {
        return Posts::create($overrides + [
            'user_id' => $user->id,
            'publisher' => 'post',
            'publisher_id' => $user->id,
            'post_type' => 'live_streaming',
            'privacy' => Visibility::Public->value,
            'description' => 'Live topic',
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
            'user_reacts' => json_encode([]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function configureZoom(): void
    {
        Setting::where('type', 'zoom_configuration')->update([
            'description' => json_encode([
                'api_key' => 'zoom-test-key',
                'api_secret' => 'zoom-test-secret-with-enough-length',
            ]),
        ]);
    }

    private function setControllerUser(MainController $controller, User $user): void
    {
        $reflection = new ReflectionClass($controller);
        $property = $reflection->getProperty('user');
        $property->setValue($controller, $user);
    }
}
