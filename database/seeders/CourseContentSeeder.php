<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseContentSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'teacher@example.com')->firstOrFail();

        $course = Course::factory()->of($teacher)->create([
            'title'        => 'Curso Demo: Introducción a Laravel',
            'description'  => 'Curso de ejemplo para probar el builder de contenido.',
            'is_published' => true,
        ]);

        $moduleIntro = Module::factory()->of($course)->create([
            'title' => 'Introducción',
            'order' => 1,
        ]);

        $moduleCore = Module::factory()->of($course)->create([
            'title' => 'Módulos y Lecciones',
            'order' => 2,
        ]);

        Lesson::factory()->inModule($moduleIntro)->freePreview()->create([
            'title' => 'Bienvenida',
            'order' => 1,
        ]);

        Lesson::factory()->inModule($moduleIntro)->create([
            'title' => 'Cómo funciona la plataforma',
            'order' => 2,
        ]);

        Lesson::factory()->inModule($moduleCore)->create([
            'title' => 'Creando tu primer curso',
            'order' => 1,
        ]);
    }
}
