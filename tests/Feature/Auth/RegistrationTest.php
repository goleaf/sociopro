<?php

namespace Tests\Feature\Auth;

use App\Enums\UserAccountStatus;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (Setting::query()->where('type', 'public_signup')->exists()) {
            Setting::query()
                ->where('type', 'public_signup')
                ->update(['description' => '1']);

            return;
        }

        Setting::query()->insert([
            'type' => 'public_signup',
            'description' => '1',
        ]);
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_registration_password_fields_have_visibility_toggles(): void
    {
        $response = $this->get(route('register'));

        $response
            ->assertStatus(200)
            ->assertSee('id="register-password"', false)
            ->assertSee('id="register-password-confirmation"', false)
            ->assertSee('data-password-toggle-target="register-password"', false)
            ->assertSee('data-password-toggle-target="register-password-confirmation"', false)
            ->assertSee("passwordToggleInput.type = isHidden ? 'text' : 'password';", false);
    }

    public function test_registration_form_keeps_submit_available_without_javascript_and_requires_terms(): void
    {
        $response = $this->get(route('register'));

        $content = $response->getContent();

        $this->assertStringContainsString('name="check1"', $content);
        $this->assertMatchesRegularExpression('/<input\b[^>]*\bname="check1"[^>]*\brequired\b[^>]*>/i', $content);
        $this->assertMatchesRegularExpression('/<button\b[^>]*\bid="submit"[^>]*>/i', $content, 'Registration submit button should be rendered as a button.');

        preg_match('/<button\b[^>]*\bid="submit"[^>]*>/i', $content, $submitButton);

        $this->assertStringNotContainsString(' disabled', $submitButton[0]);
        $this->assertStringNotContainsString('aria-disabled="true"', $submitButton[0]);
    }

    public function test_registration_enhancement_script_does_not_require_jquery(): void
    {
        $response = $this->get(route('register'));

        $response
            ->assertDontSee('$( document ).ready', false)
            ->assertSee("document.addEventListener('DOMContentLoaded'", false)
            ->assertSee("termsCheckbox.addEventListener('change'", false);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'check1' => 'on',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('timeline'));
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Terms Bypass User',
            'email' => 'terms-bypass@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('check1');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'terms-bypass@example.com',
        ]);
    }

    public function test_registered_users_are_not_marked_as_deactivated_before_email_verification(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'check1' => 'on',
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(UserAccountStatus::Active->value, (int) $user->status);

        $this
            ->actingAs($user)
            ->get(route('timeline'))
            ->assertRedirect(route('verification.notice'));
    }
}
