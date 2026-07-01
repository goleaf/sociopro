<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_user_ad_edit_returns_not_found(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->get(route('user.ad.edit', ['id' => 999999]))
            ->assertNotFound();
    }

    public function test_user_cannot_edit_another_users_ad(): void
    {
        $owner = $this->activeUser();
        $viewer = $this->activeUser();
        $ad = $this->sponsorFor($owner);

        $this->actingAs($viewer)
            ->get(route('user.ad.edit', ['id' => $ad->id]))
            ->assertForbidden();
    }

    public function test_missing_user_ad_update_returns_not_found(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('user.ad.update', ['id' => 999999]), $this->validAdPayload())
            ->assertNotFound();
    }

    public function test_user_cannot_update_another_users_ad(): void
    {
        $owner = $this->activeUser();
        $viewer = $this->activeUser();
        $ad = $this->sponsorFor($owner, ['name' => 'Original owner ad']);

        $this->actingAs($viewer)
            ->post(route('user.ad.update', ['id' => $ad->id]), $this->validAdPayload([
                'name' => 'Hijacked ad name',
            ]))
            ->assertForbidden();

        $this->assertSame('Original owner ad', $ad->refresh()->name);
    }

    public function test_missing_user_ad_delete_returns_not_found(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->get(route('user.ad.delete', ['id' => 999999]))
            ->assertNotFound();
    }

    public function test_user_cannot_delete_another_users_ad(): void
    {
        $owner = $this->activeUser();
        $viewer = $this->activeUser();
        $ad = $this->sponsorFor($owner);

        $this->actingAs($viewer)
            ->get(route('user.ad.delete', ['id' => $ad->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('sponsors', [
            'id' => $ad->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_missing_user_ad_payment_configuration_returns_not_found(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post(route('user.ad.payment_configuration', ['id' => 999999]), $this->validDatePayload())
            ->assertNotFound();
    }

    public function test_user_cannot_configure_payment_for_another_users_ad(): void
    {
        $owner = $this->activeUser();
        $viewer = $this->activeUser();
        $ad = $this->sponsorFor($owner);

        $this->actingAs($viewer)
            ->post(route('user.ad.payment_configuration', ['id' => $ad->id]), $this->validDatePayload())
            ->assertForbidden();

        $this->assertNull(session('payment_details'));
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
    private function sponsorFor(User $user, array $overrides = []): Sponsor
    {
        $sponsor = new Sponsor;
        $sponsor->forceFill(array_merge([
            'user_id' => $user->id,
            'name' => 'Scoped Sponsor',
            'description' => 'Owned ad',
            'ext_url' => 'https://example.test/ad',
            'image' => 'sponsor.jpg',
            'status' => 1,
            'start_date' => '2026-07-01 00:00:00',
            'end_date' => '2026-07-02 00:00:00',
        ], $overrides));
        $sponsor->save();

        return $sponsor;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validAdPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Updated ad',
            'description' => 'Updated description',
            'ext_url' => 'https://example.test/updated-ad',
        ], $overrides);
    }

    /**
     * @return array<string, string>
     */
    private function validDatePayload(): array
    {
        return [
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-02',
        ];
    }
}
