<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title'                 => $title,
            'slug'                  => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 9999),
            'description'           => fake()->paragraph(),
            'syllabus'              => fake()->paragraph(),
            'level'                 => CourseLevel::Beginner->value,
            'base_price'            => fake()->randomFloat(2, 1000, 50000),
            'offer_price'           => null,
            'duration_months'       => fake()->numberBetween(1, 12),
            'extension_price_month' => null,
            'is_published'          => false,
            'created_by'            => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }

    public function of(User $user): static
    {
        return $this->state(fn () => ['created_by' => $user->id]);
    }
}
