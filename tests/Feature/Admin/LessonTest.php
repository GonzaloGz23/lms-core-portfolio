<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------------ */
    /* Acceso y middleware */
    /* ------------------------------------------------------------------ */

    public function test_guests_are_redirected_to_login_when_storing_a_lesson()
    {
        $course = Course::factory()->create();
        $module = Module::factory()->of($course)->create();

        $this->post(route('admin.modules.lessons.store', $module), $this->validLessonData())
            ->assertRedirect(route('login'));
    }

    public function test_students_are_redirected_to_dashboard_when_storing_a_lesson()
    {
        $student = User::factory()->create();
        $course  = Course::factory()->create();
        $module  = Module::factory()->of($course)->create();

        $this->actingAs($student)
            ->post(route('admin.modules.lessons.store', $module), $this->validLessonData())
            ->assertRedirect(route('dashboard'));
    }

    /* ------------------------------------------------------------------ */
    /* Store */
    /* ------------------------------------------------------------------ */

    public function test_admin_can_create_a_lesson_in_any_module()
    {
        $admin  = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->of($course)->create();

        $this->actingAs($admin)
            ->post(route('admin.modules.lessons.store', $module), $this->validLessonData(['title' => 'Lección 1']))
            ->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'module_id' => $module->id,
            'title'     => 'Lección 1',
        ]);
    }

    public function test_teacher_owner_can_create_a_lesson()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();

        $this->actingAs($teacher)
            ->post(route('admin.modules.lessons.store', $module), $this->validLessonData(['title' => 'Bienvenida']))
            ->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'module_id' => $module->id,
            'title'     => 'Bienvenida',
        ]);
    }

    public function test_teacher_cannot_create_lesson_in_another_teachers_module()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();
        $module = Module::factory()->of($course)->create();

        $this->actingAs($other)
            ->post(route('admin.modules.lessons.store', $module), $this->validLessonData())
            ->assertForbidden();
    }

    public function test_lesson_requires_a_title()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();

        $this->actingAs($teacher)
            ->post(route('admin.modules.lessons.store', $module), $this->validLessonData(['title' => '']))
            ->assertSessionHasErrors('title');
    }

    public function test_store_assigns_order_automatically_within_module()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();

        Lesson::factory()->inModule($module)->create(['order' => 1]);
        Lesson::factory()->inModule($module)->create(['order' => 2]);

        $this->actingAs($teacher)
            ->post(route('admin.modules.lessons.store', $module), $this->validLessonData(['title' => 'Nueva']))
            ->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'module_id' => $module->id,
            'title'     => 'Nueva',
            'order'     => 3,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Update */
    /* ------------------------------------------------------------------ */

    public function test_owner_teacher_can_update_their_lesson()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $lesson  = Lesson::factory()->inModule($module)->create(['title' => 'Vieja', 'order' => 1]);

        $this->actingAs($teacher)
            ->put(route('admin.lessons.update', $lesson), [
                'title'           => 'Nueva',
                'content'         => 'Contenido',
                'is_free_preview' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'id'              => $lesson->id,
            'title'           => 'Nueva',
            'order'           => 1,
            'is_free_preview' => true,
        ]);
    }

    public function test_teacher_cannot_update_another_teachers_lesson()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();
        $module = Module::factory()->of($course)->create();
        $lesson = Lesson::factory()->inModule($module)->create();

        $this->actingAs($other)
            ->put(route('admin.lessons.update', $lesson), ['title' => 'Hack'])
            ->assertForbidden();
    }

    public function test_update_requires_title()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $lesson  = Lesson::factory()->inModule($module)->create();

        $this->actingAs($teacher)
            ->put(route('admin.lessons.update', $lesson), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    /* ------------------------------------------------------------------ */
    /* Destroy */
    /* ------------------------------------------------------------------ */

    public function test_owner_can_delete_lesson()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $lesson  = Lesson::factory()->inModule($module)->create();

        $this->actingAs($teacher)
            ->delete(route('admin.lessons.destroy', $lesson))
            ->assertRedirect();

        $this->assertSoftDeleted($lesson);
    }

    public function test_teacher_cannot_delete_another_teachers_lesson()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();
        $module = Module::factory()->of($course)->create();
        $lesson = Lesson::factory()->inModule($module)->create();

        $this->actingAs($other)
            ->delete(route('admin.lessons.destroy', $lesson))
            ->assertForbidden();

        $this->assertNotSoftDeleted($lesson);
    }

    /* ------------------------------------------------------------------ */
    /* Move */
    /* ------------------------------------------------------------------ */

    public function test_move_down_swaps_with_next_within_same_module()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $l1      = Lesson::factory()->inModule($module)->create(['order' => 1]);
        $l2      = Lesson::factory()->inModule($module)->create(['order' => 2]);

        $this->actingAs($teacher)
            ->post(route('admin.lessons.move', $l1), ['direction' => 'down'])
            ->assertRedirect();

        $this->assertEquals(2, $l1->fresh()->order);
        $this->assertEquals(1, $l2->fresh()->order);
    }

    public function test_move_up_swaps_with_previous_within_same_module()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $l1      = Lesson::factory()->inModule($module)->create(['order' => 1]);
        $l2      = Lesson::factory()->inModule($module)->create(['order' => 2]);

        $this->actingAs($teacher)
            ->post(route('admin.lessons.move', $l2), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertEquals(2, $l1->fresh()->order);
        $this->assertEquals(1, $l2->fresh()->order);
    }

    public function test_move_at_extreme_does_not_change_orders()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $l1      = Lesson::factory()->inModule($module)->create(['order' => 1]);
        $l2      = Lesson::factory()->inModule($module)->create(['order' => 2]);

        $this->actingAs($teacher)
            ->post(route('admin.lessons.move', $l1), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertEquals(1, $l1->fresh()->order);
        $this->assertEquals(2, $l2->fresh()->order);
    }

    public function test_move_does_not_affect_lessons_from_other_module()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $m1      = Module::factory()->of($course)->create();
        $m2      = Module::factory()->of($course)->create();
        $l1m1    = Lesson::factory()->inModule($m1)->create(['order' => 1]);
        $l2m1    = Lesson::factory()->inModule($m1)->create(['order' => 2]);
        $l1m2    = Lesson::factory()->inModule($m2)->create(['order' => 1]);

        $this->actingAs($teacher)
            ->post(route('admin.lessons.move', $l1m1), ['direction' => 'down'])
            ->assertRedirect();

        $this->assertEquals(1, $l1m2->fresh()->order);
    }

    /* ------------------------------------------------------------------ */
    /* Helper */
    /* ------------------------------------------------------------------ */

    private function validLessonData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Lección de prueba',
        ], $overrides);
    }
}
