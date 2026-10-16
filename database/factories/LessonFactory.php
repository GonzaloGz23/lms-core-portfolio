<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id'          => Module::factory(),
            'title'              => fake()->unique()->sentence(2),
            'content'            => fake()->paragraphs(2, true),
            'video_url'          => null,
            'video_duration_sec' => null,
            'external_video_id'  => null,
            'order'              => 1,
            'is_free_preview'    => false,
        ];
    }

    public function inModule(Module $module): static
    {
        return $this->state(fn () => ['module_id' => $module->id]);
    }

    public function freePreview(): static
    {
        return $this->state(fn () => ['is_free_preview' => true]);
    }
}
