<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'group_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'event_date' => now()->addWeek()->toDateString(),
            'event_time' => '10:00',
            'location' => $this->faker->city(),
            'going_users_id' => json_encode([]),
            'interested_users_id' => json_encode([]),
            'banner' => null,
            'privacy' => Visibility::Public->value,
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ];
    }

    public function forOwner(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
        ]);
    }
}
