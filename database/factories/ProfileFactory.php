<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'first_name'      => fake()->firstName(),
            'last_name'       => fake()->lastName(),
            'document_number' => fake()->unique()->numerify('########'),
            'mobile_number'   => fake()->phoneNumber(),
            'address'         => fake()->address(),
            'country'         => fake()->country(),
            'city'            => fake()->city(),
        ];
    }
}
