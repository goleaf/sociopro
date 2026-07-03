<?php

namespace Database\Factories;

use App\Models\AlbumImage;
use App\Models\Albums;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlbumImage>
 */
class AlbumImageFactory extends Factory
{
    protected $model = AlbumImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'album_id' => Albums::factory(),
            'user_id' => User::factory(),
            'page_id' => null,
            'group_id' => null,
            'image' => 'album-image.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
