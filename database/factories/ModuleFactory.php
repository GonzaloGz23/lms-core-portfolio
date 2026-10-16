<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id'   => Course::factory(),
            'title'       => fake()->unique()->sentence(2),
            'description' => fake()->paragraph(),
            'order'       => 1,
        ];
    }

    public function of(Course $course): static
    {
        return $this->state(fn () => ['course_id' => $course->id]);
    }
}
