<?php

namespace Database\Factories;

use App\Models\AccountActiveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountActiveRequest>
 */
class AccountActiveRequestFactory extends Factory
{
    protected $model = AccountActiveRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'created_at' => (string) time(),
            'updated_at' => (string) time(),
        ];
    }
}
