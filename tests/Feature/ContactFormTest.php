<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Mail\ContactMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_validates_payload_before_sending_mail(): void
    {
        Mail::fake();
        $this->adminUser();

        $this
            ->from(route('contact.view'))
            ->post(route('contact.send'), [
                'name' => '',
                'email' => 'not-an-email',
                'subject' => '',
                'details' => '',
            ])
            ->assertRedirect(route('contact.view'))
            ->assertSessionHasErrors(['name', 'email', 'subject', 'details']);

        Mail::assertNothingOutgoing();
    }

    public function test_contact_form_requires_an_admin_recipient(): void
    {
        Mail::fake();

        $this
            ->from(route('contact.view'))
            ->post(route('contact.send'), $this->validPayload())
            ->assertRedirect(route('contact.view'))
            ->assertSessionHasErrors(['email']);

        Mail::assertNothingOutgoing();
    }

    public function test_contact_form_sends_mail_to_admin_with_safe_sender_headers_and_content(): void
    {
        Mail::fake();
        config()->set('mail.from.address', 'no-reply@sociopro.test');
        config()->set('mail.from.name', 'Sociopro');

        $admin = $this->adminUser([
            'email' => 'admin-contact@example.com',
            'password' => 'hashed-admin-password-secret',
            'remember_token' => 'remember-admin-secret',
            'payment_settings' => json_encode(['token' => 'payment-admin-secret']),
        ]);
        $payload = $this->validPayload([
            'details' => 'Please tell me more about deployment support. Reference: SOC-123.',
        ]);

        $this
            ->from(route('contact.view'))
            ->post(route('contact.send'), $payload)
            ->assertRedirect(route('contact.view'))
            ->assertSessionHasNoErrors();

        Mail::assertSent(ContactMail::class, function (ContactMail $mail) use ($admin, $payload): bool {
            $rendered = $this->renderedMailText($mail);

            return $mail->hasTo($admin->email)
                && ! $mail->hasTo($payload['email'])
                && $mail->name === $payload['name']
                && $mail->email === $payload['email']
                && $mail->subject === $payload['subject']
                && $mail->details === $payload['details']
                && $mail->from === [['name' => 'Sociopro', 'address' => 'no-reply@sociopro.test']]
                && $mail->replyTo === [['name' => $payload['name'], 'address' => $payload['email']]]
                && ! $mail instanceof ShouldQueue
                && str_contains($rendered, 'A Contact Request Sent From')
                && str_contains($rendered, $payload['name'])
                && str_contains($rendered, $payload['subject'])
                && str_contains($rendered, $payload['details'])
                && ! str_contains($rendered, 'hashed-admin-password-secret')
                && ! str_contains($rendered, 'remember-admin-secret')
                && ! str_contains($rendered, 'payment-admin-secret');
        });
        Mail::assertSentCount(1);
        Mail::assertNothingQueued();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function adminUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'user_role' => UserRole::Admin->value,
        ], $overrides));
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Contact Sender',
            'email' => 'sender@example.com',
            'subject' => 'Production readiness question',
            'details' => 'Please tell me more about deployment support.',
        ], $overrides);
    }

    private function renderedMailText(ContactMail $mail): string
    {
        return html_entity_decode(strip_tags($mail->render()));
    }
}
