<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSidebarMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_passes_sidebar_media_files_to_the_profile_sidebar(): void
    {
        $user = User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'friends' => json_encode([]),
            'about' => 'Profile intro',
            'profile_status' => 'unlock',
        ]);

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertOk();
        $this->assertTrue($response->viewData('media_files')->isEmpty());
    }
}
