<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\Job;
use App\Models\Sponsor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DateTimeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_registration_rejects_invalid_timezone_and_defaults_missing_timezone(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), $this->registrationPayload([
                'email' => 'invalid-timezone@example.test',
                'timezone' => 'Not/AZone',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('timezone');

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'default-timezone@example.test',
        ]))
            ->assertRedirect(route('timeline'));

        $this->assertAuthenticated();
        $this->assertSame(config('app.timezone'), auth()->user()->timezone);
    }

    public function test_api_signup_rejects_invalid_timezone_with_legacy_error_shape(): void
    {
        $response = $this->postJson(route('api.auth.signup'), $this->registrationPayload([
            'email' => 'api-invalid-timezone@example.test',
            'timezone' => 'Not/AZone',
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'timezone',
                ],
            ]);
    }

    public function test_event_validation_requires_browser_date_and_time_formats(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('event.store'), [
                'eventname' => 'Invalid Date Event',
                'eventdate' => '07/01/2026',
                'eventtime' => '9pm',
                'eventlocation' => 'Vilnius',
                'privacy' => 'public',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'eventdate',
                    'eventtime',
                ],
            ]);

        $this->assertDatabaseMissing('events', [
            'title' => 'Invalid Date Event',
        ]);
    }

    public function test_event_validation_accepts_browser_date_and_midnight_time_boundaries(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('event.store'), [
                'eventname' => 'Valid Date Event',
                'eventdate' => '2026-07-01',
                'eventtime' => '00:00',
                'eventlocation' => 'Vilnius',
                'privacy' => 'public',
            ])
            ->assertOk()
            ->assertJson([
                'reload' => 1,
            ]);

        $this->assertDatabaseHas('events', [
            'title' => 'Valid Date Event',
            'event_date' => '2026-07-01',
            'event_time' => '00:00',
        ]);
    }

    public function test_admin_user_birth_date_rejects_ambiguous_old_and_future_values(): void
    {
        $admin = $this->adminUser();

        foreach (['12-31-1990', '1899-12-31', now()->addDay()->toDateString()] as $date) {
            $email = str_replace('-', '', $date).uniqid('@example.test');

            $this->actingAs($admin)
                ->from(route('admin.user.add'))
                ->post(route('admin.user.store'), $this->adminUserPayload([
                    'email' => $email,
                    'date_of_birth' => $date,
                ]))
                ->assertRedirect(route('admin.user.add'))
                ->assertSessionHasErrors('date_of_birth');

            $this->assertDatabaseMissing('users', [
                'email' => $email,
            ]);
        }
    }

    public function test_admin_user_birth_date_accepts_browser_date_and_stores_legacy_timestamp(): void
    {
        $admin = $this->adminUser();
        $email = 'valid-birth-date@example.test';

        $this->actingAs($admin)
            ->post(route('admin.user.store'), $this->adminUserPayload([
                'email' => $email,
                'date_of_birth' => '1990-12-31',
            ]))
            ->assertRedirect(route('admin.users'))
            ->assertSessionDoesntHaveErrors('date_of_birth');

        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertSame(
            CarbonImmutable::createFromFormat('!Y-m-d', '1990-12-31', config('app.timezone'))->timestamp,
            (int) $user->date_of_birth
        );
    }

    public function test_user_ad_date_range_is_inclusive_and_rejects_reversed_dates(): void
    {
        $user = $this->activeUser();
        $sponsor = $this->sponsorFor($user);

        $this->actingAs($user)
            ->get(route('user.ad.ad_charge_by_daterange', [
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-01',
            ]))
            ->assertOk()
            ->assertContent((string) get_settings('ad_charge_per_day'));

        $this->actingAs($user)
            ->from(route('user.ads'))
            ->post(route('user.ad.payment_configuration', ['id' => $sponsor->id]), [
                'start_date' => '2026-07-02',
                'end_date' => '2026-07-01',
            ])
            ->assertRedirect(route('user.ads'))
            ->assertSessionHasErrors('end_date');
    }

    public function test_marketplace_filter_dates_require_browser_date_format(): void
    {
        $this->authenticateApiUser();

        $response = $this->withToken('test-token')->getJson(route('api.marketplace.filter').'?'.http_build_query([
            'date_from' => '2026-07-01T10:00:00Z',
            'date_to' => '2026-07-02T10:00:00Z',
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'validationError' => [
                    'date_from',
                    'date_to',
                ],
            ]);
    }

    public function test_admin_profile_uses_browser_date_value_not_user_id(): void
    {
        $view = file_get_contents(resource_path('views/backend/admin/profile_view/profile.blade.php'));

        $this->assertStringNotContainsString('name="dateofbirth" value="{{ auth()->user()->id }}"', $view);
        $this->assertStringContainsString('name="dateofbirth"', $view);
        $this->assertStringContainsString("date('Y-m-d',", $view);
    }

    public function test_core_date_models_cast_database_date_attributes(): void
    {
        $user = User::factory()->create([
            'date_of_birth' => CarbonImmutable::createFromFormat('!Y-m-d', '1990-02-03', config('app.timezone'))->timestamp,
            'lastActive' => '2026-07-01 12:30:00',
        ]);
        $job = $this->jobWithDates();
        $sponsor = $this->sponsorFor($user);
        $badge = $this->badgeFor($user);

        $this->assertIsInt($user->refresh()->date_of_birth);
        $this->assertInstanceOf(CarbonInterface::class, $user->lastActive);
        $this->assertInstanceOf(CarbonInterface::class, $job->start_date);
        $this->assertInstanceOf(CarbonInterface::class, $job->end_date);
        $this->assertInstanceOf(CarbonInterface::class, $sponsor->refresh()->start_date);
        $this->assertInstanceOf(CarbonInterface::class, $sponsor->end_date);
        $this->assertInstanceOf(CarbonInterface::class, $badge->refresh()->start_date);
        $this->assertInstanceOf(CarbonInterface::class, $badge->end_date);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Date User',
            'email' => 'date-user@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function adminUserPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Managed User',
            'email' => 'managed-user@example.test',
            'password' => 'password',
            'gender' => 'male',
            'date_of_birth' => '1990-12-31',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function adminUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::Admin->value,
        ], $overrides));
    }

    private function sponsorFor(User $user): Sponsor
    {
        $sponsor = new Sponsor;
        $sponsor->forceFill([
            'user_id' => $user->id,
            'name' => 'Date Sponsor',
            'image' => 'sponsor.jpg',
            'status' => 1,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-02 00:00:00',
        ]);
        $sponsor->save();

        return $sponsor;
    }

    private function jobWithDates(): Job
    {
        $job = new Job;
        $job->forceFill([
            'title' => 'Date Job',
            'category_id' => 1,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-02 00:00:00',
        ]);

        return $job;
    }

    private function badgeFor(User $user): Badge
    {
        $badge = new Badge;
        $badge->forceFill([
            'user_id' => $user->id,
            'title' => 'Date Badge',
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-02 00:00:00',
        ]);
        $badge->save();

        return $badge;
    }

    private function authenticateApiUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }
}
