<?php

namespace Tests\Feature\Auth;

use App\Enums\UserAccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('timeline'));
    }

    public function test_registered_users_are_not_marked_as_deactivated_before_email_verification(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(UserAccountStatus::Active->value, (int) $user->status);

        $this
            ->actingAs($user)
            ->get(route('timeline'))
            ->assertRedirect(route('verification.notice'));
    }
}
