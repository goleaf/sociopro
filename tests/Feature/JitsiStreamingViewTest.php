<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class JitsiStreamingViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_jitsi_streaming_view_tolerates_incomplete_jitsi_settings(): void
    {
        $user = User::factory()->create([
            'email' => 'host@example.test',
            'name' => 'Live Host',
        ]);

        Setting::query()
            ->where('type', 'zitsi_configuration')
            ->update([
                'description' => json_encode([
                    'account_email' => 'host@example.test',
                ]),
            ]);

        $this->actingAs($user);

        $html = view('frontend.main_content.jitsi_streaming', [
            'user' => $user,
            'join_pass' => 'room-pass',
            'room' => 'test-room',
        ])->render();

        $this->assertStringContainsString('id="jitsiMeet"', $html);
        $this->assertStringContainsString('roomName:', $html);
    }

    public function test_jitsi_streaming_host_view_uses_js_safe_literals_and_moderator_controls(): void
    {
        $host = User::factory()->create([
            'email' => 'host@example.test',
            'name' => 'Host " </script><script>alert("xss")</script>',
        ]);
        $joinPass = 'pass" </script><img src=x onerror=alert(1)>';
        $room = 'room" </script><script>alert("room")</script>';

        $this->storeJitsiConfiguration([
            'account_email' => 'host@example.test',
            'jitsi_app_id' => 'app" </script><script>alert("app")</script>',
            'jitsi_jwt' => 'jwt" </script><script>alert("jwt")</script>',
        ]);

        $this->actingAs($host);

        $html = $this->renderStreamingView($host, $joinPass, $room);

        $this->assertStringContainsString('id="jitsiMeet"', $html);
        $this->assertStringContainsString('moderator: true', $html);
        $this->assertStringContainsString('disableKick: false', $html);
        $this->assertStringContainsString("'mute-everyone'", $html);
        $this->assertStringContainsString('var name = '.$this->js($host->name).';', $html);
        $this->assertStringContainsString('var join_pass = '.$this->js($joinPass).';', $html);
        $this->assertStringContainsString('var room = '.$this->js($room).';', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
        $this->assertStringNotContainsString('<script>alert("room")</script>', $html);
        $this->assertStringNotContainsString('<script>alert("jwt")</script>', $html);
    }

    public function test_jitsi_streaming_audience_view_hides_moderator_controls_and_jwt(): void
    {
        $host = User::factory()->create([
            'email' => 'host@example.test',
            'name' => 'Live Host',
        ]);
        $viewer = User::factory()->create([
            'email' => 'viewer@example.test',
            'name' => 'Viewer User',
        ]);

        $this->storeJitsiConfiguration([
            'account_email' => 'host@example.test',
            'jitsi_app_id' => 'safe-app-id',
            'jitsi_jwt' => 'sample-private-jitsi-jwt',
        ]);

        $this->actingAs($viewer);

        $html = $this->renderStreamingView($host, 'room-pass', 'test-room');

        $this->assertStringContainsString('moderator: false', $html);
        $this->assertStringContainsString('disableKick: true', $html);
        $this->assertStringNotContainsString('moderator: true', $html);
        $this->assertStringNotContainsString('disableKick: false', $html);
        $this->assertStringNotContainsString("'mute-everyone'", $html);
        $this->assertStringNotContainsString('sample-private-jitsi-jwt', $html);
        $this->assertStringNotContainsString('jwt:', $html);
    }

    public function test_jitsi_settings_form_escapes_values_and_renders_form_errors(): void
    {
        $dangerousAppId = 'app-id" autofocus onfocus=alert(1) data-x="';
        $dangerousJwt = 'jwt-token"><script>alert("jwt")</script>';

        $this->storeJitsiConfiguration([
            'account_email' => 'admin@example.test',
            'jitsi_app_id' => $dangerousAppId,
            'jitsi_jwt' => $dangerousJwt,
        ]);
        $this->shareViewErrors([
            'jitsi_app_id' => ['The jitsi app id field is required.'],
        ]);

        $html = view('backend.admin.setting.zitsi_live_settings')->render();

        $this->assertStringContainsString('action="'.route('admin.zitsi.live.settings.update').'"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('The jitsi app id field is required.', $html);
        $this->assertStringContainsString(e($dangerousAppId), $html);
        $this->assertStringContainsString(e($dangerousJwt), $html);
        $this->assertStringNotContainsString('<script>alert("jwt")</script>', $html);
        $this->assertStringNotContainsString('value="app-id" autofocus', $html);
    }

    /**
     * @param  array{account_email?: string, jitsi_app_id?: string, jitsi_jwt?: string}  $configuration
     */
    private function storeJitsiConfiguration(array $configuration): void
    {
        Setting::query()
            ->where('type', 'zitsi_configuration')
            ->update([
                'description' => json_encode($configuration),
            ]);
    }

    private function renderStreamingView(User $host, string $joinPass, string $room): string
    {
        return view('frontend.main_content.jitsi_streaming', [
            'user' => $host,
            'join_pass' => $joinPass,
            'room' => $room,
        ])->render();
    }

    /**
     * @param  array<string, list<string>>  $messages
     */
    private function shareViewErrors(array $messages): void
    {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag($messages));

        view()->share('errors', $errors);
    }

    private function js(string $value): string
    {
        return (string) Js::from($value);
    }
}
