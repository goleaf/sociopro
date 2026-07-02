<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
    }

    public function test_email_verification_notification_route_uses_invokable_controller(): void
    {
        $route = Route::getRoutes()->getByName('verification.send');

        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        $this->assertSame('email/verification-notification', $route->uri());
        $this->assertSame(EmailVerificationNotificationController::class, $route->getActionName());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('throttle:6,1', $route->gatherMiddleware());
    }

    public function test_email_verification_notification_can_be_requested_with_mail_channel_content_and_no_secret_leaks(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => 'hashed-verification-password-secret',
            'remember_token' => 'remember-verification-secret',
            'payment_settings' => json_encode(['token' => 'payment-verification-secret']),
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'));

        $response
            ->assertRedirect(route('verification.notice', [], false))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, array $channels) use ($user): bool {
            $message = $notification->toMail($user);
            $mailText = $this->mailMessageText($message);

            return $channels === ['mail']
                && ! $notification instanceof ShouldQueue
                && $message->subject === 'Verify your email address'
                && $message->actionText === 'Verify Email Address'
                && str_contains($message->actionUrl, '/verify-email/'.$user->getKey().'/'.sha1($user->email))
                && str_contains($mailText, 'verify your email address')
                && str_contains($mailText, 'If you did not create an account')
                && ! str_contains($mailText, 'hashed-verification-password-secret')
                && ! str_contains($mailText, 'remember-verification-secret')
                && ! str_contains($mailText, 'payment-verification-secret');
        });
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
        Notification::assertCount(1);
    }

    public function test_email_verification_notification_is_not_sent_for_guest_or_verified_users(): void
    {
        Notification::fake();

        $this
            ->post(route('verification.send'))
            ->assertRedirect(route('login'));

        Notification::assertNothingSent();

        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this
            ->actingAs($verifiedUser)
            ->post(route('verification.send'))
            ->assertRedirect(RouteServiceProvider::HOME);

        Notification::assertNothingSentTo($verifiedUser);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('timeline', [], false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    private function mailMessageText(MailMessage $message): string
    {
        return implode("\n", array_map(
            static fn (mixed $line): string => (string) $line,
            array_filter(array_merge(
                [$message->subject, $message->actionText, $message->actionUrl],
                $message->introLines,
                $message->outroLines,
            )),
        ));
    }
}
