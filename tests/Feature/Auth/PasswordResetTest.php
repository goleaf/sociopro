<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested_with_mail_channel_content_and_no_secret_leaks(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => 'hashed-reset-password-secret',
            'remember_token' => 'remember-reset-secret',
            'payment_settings' => json_encode(['token' => 'payment-reset-secret']),
        ]);
        $otherUser = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification, array $channels) use ($user): bool {
            $message = $notification->toMail($user);
            $mailText = $this->mailMessageText($message);

            return $channels === ['mail']
                && ! $notification instanceof ShouldQueue
                && $message->subject === 'Reset your password'
                && $message->actionText === 'Reset Password'
                && str_contains($message->actionUrl, $notification->token)
                && str_contains($message->actionUrl, rawurlencode($user->email))
                && str_contains($mailText, 'password reset request')
                && str_contains($mailText, 'If you did not request a password reset')
                && ! str_contains($mailText, 'hashed-reset-password-secret')
                && ! str_contains($mailText, 'remember-reset-secret')
                && ! str_contains($mailText, 'payment-reset-secret');
        });
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        Notification::assertNotSentTo($otherUser, ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_reset_password_link_is_not_sent_for_failed_or_unauthorized_requests(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this
            ->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'missing-reset@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors(['email']);

        Notification::assertNothingSent();

        $this
            ->actingAs($user)
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(RouteServiceProvider::HOME);

        Notification::assertNothingSentTo($user);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->get(route('password.reset', $token));

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
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
