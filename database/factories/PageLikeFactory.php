<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Page;
use App\Models\PageLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageLike>
 */
class PageLikeFactory extends Factory
{
    protected $model = PageLike::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'page_id' => Page::factory(),
            'role' => MembershipRole::General->value,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }

    public function forPage(Page $page): static
    {
        return $this->state([
            'page_id' => $page->id,
        ]);
    }
}
