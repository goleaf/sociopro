<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Chat;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaFile>
 */
class MediaFileFactory extends Factory
{
    protected $model = MediaFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'chat_id' => null,
            'file_name' => Str::uuid()->toString().'.jpg',
            'file_type' => 'image',
            'privacy' => 'public',
            'created_at' => time(),
            'updated_at' => time(),
        ];
    }

    public function forChat(Chat $chat): static
    {
        return $this->state(fn (): array => [
            'chat_id' => $chat->id,
            'user_id' => $chat->sender_id,
        ]);
    }

    public function image(): static
    {
        return $this->state(fn (): array => [
            'file_name' => Str::uuid()->toString().'.jpg',
            'file_type' => 'image',
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (): array => [
            'file_name' => Str::uuid()->toString().'.mp4',
            'file_type' => 'video',
        ]);
    }
}
