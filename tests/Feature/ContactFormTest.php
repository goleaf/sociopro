<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Mail\ContactMail;
use App\Models\User;
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

    public function test_contact_form_sends_mail_to_admin_with_safe_sender_headers(): void
    {
        Mail::fake();
        config()->set('mail.from.address', 'no-reply@sociopro.test');
        config()->set('mail.from.name', 'Sociopro');

        $admin = $this->adminUser(['email' => 'admin-contact@example.com']);

        $this
            ->from(route('contact.view'))
            ->post(route('contact.send'), $this->validPayload())
            ->assertRedirect(route('contact.view'))
            ->assertSessionHasNoErrors();

        Mail::assertSent(ContactMail::class, function (ContactMail $mail) use ($admin): bool {
            $mail->build();

            return $mail->hasTo($admin->email)
                && $mail->name === 'Contact Sender'
                && $mail->email === 'sender@example.com'
                && $mail->subject === 'Production readiness question'
                && $mail->details === 'Please tell me more about deployment support.'
                && $mail->from === [['name' => 'Sociopro', 'address' => 'no-reply@sociopro.test']]
                && $mail->replyTo === [['name' => 'Contact Sender', 'address' => 'sender@example.com']];
        });
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
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Contact Sender',
            'email' => 'sender@example.com',
            'subject' => 'Production readiness question',
            'details' => 'Please tell me more about deployment support.',
        ];
    }
}
