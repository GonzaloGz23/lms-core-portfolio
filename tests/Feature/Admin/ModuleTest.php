<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase; // borra y recrea la BD antes de cada prueba.
use Tests\TestCase;

class ModuleTest extends TestCase
{
    use RefreshDatabase;

    /* Acceso y middleware */
    // los invitados son redirigidos al login cuando intentan  guardar un módulo.
    public function test_guests_are_redirectioned_to_login_when_storing_a_module()
    {
        // crea un registro real de un Curso en la BD su respectivo Factory.
        $course = Course::factory()->create();
        // simula en envío de un formulario mediante una petición HTTP de tipo POST hacia el servidor.
        // se genera la url dinámica de la ruta encargada de guardar el módulo, pasándole el $course que creamos arriba como parámetro de la URL.
        // llama a la función validModuleData(). Envía los daros simulados del módulo.
        $this->post(route('admin.modules.store', $course), $this->validModuleData())
            ->assertRedirect(route('login'));
    }

    // los estudiantes son redirigidos al panel de control al guardar un módulo
    public function test_students_are_redirectioned_to_dashboard_when_storing_a_module()
    {
        $student = User::factory()->create();
        $course  = Course::factory()->create();
        $this->actingAs($student) // simula que el usuario inició sesión como estudiante
            ->post(route('admin.modules.store', $course), $this->validModuleData())
            ->assertRedirect(route('dashboard'));
    }

    /* store */
    // el admin puede crear un módulo en cualquier curso.
    public function test_admin_can_create_module_in_any_course()
    {
        $admin  = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $this->actingAs($admin)
            ->post(route('admin.modules.store', $course), $this->validModuleData(['title' => 'Módulo 1']))
            ->assertRedirect();
        // va directamente a la tabla modules en la base de datos de pruebas y confirma que exista una fila que cumpla ambas condiciones:
        // que pertenezca al id del curso creado ($course->id)
        // y que tenga el título exacto que sobreescribiste (Módulo 1)
        $this->assertDatabaseHas('modules', ['course_id' => $course->id, 'title' => 'Módulo 1']);
    }

    // el profesor propietario puede crear un módulo
    public function test_teacher_owner_can_create_a_module()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $this->actingAs($teacher)
            ->post(route('admin.modules.store', $course), $this->validModuleData(['title' => 'Intro']))
            ->assertRedirect();
    }

    // el profesor no puede crear un módulo en el curso de otro profesor
    public function test_teacher_cannot_create_module_in_another_teachers_course()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();
        $this->actingAs($other)
            ->post(route('admin.modules.store', $course), $this->validModuleData())
            ->assertForbidden(); // verifica que la petición haya sido denegada
    }

    // el módulo requiere un título
    public function test_module_requires_a_title()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $this->actingAs($teacher)
            ->post(route('admin.modules.store', $course), $this->validModuleData(['title' => '']))
            ->assertSessionHasErrors('title'); // verifica que si haya el error de no tener título
    }

    // asignar automaticamente el número de orden correcto
    public function test_store_assigns_order_automatically()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        Module::factory()->of($course)->create(['order' => 1]);
        Module::factory()->of($course)->create(['order' => 2]);
        $this->actingAs($teacher)
            ->post(route('admin.modules.store', $course), $this->validModuleData(['title' => 'Nuevo']))
            ->assertRedirect();
        $this->assertDatabaseHas('modules', ['course_id' => $course->id, 'title' => 'Nuevo', 'order' => 3]);
    }

    /* update */
    // el profesor propietario puede actualizar sus módulos
    public function test_owner_teacher_can_update_their_module()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create(['title' => 'Viejo', 'order' => 1]);
        $this->actingAs($teacher)
            ->put(route('admin.modules.update', $module), ['title' => 'Nuevo', 'description' => 'Desc'])
            ->assertRedirect();
        $this->assertDatabaseHas('modules', ['id' => $module->id, 'title' => 'Nuevo', 'order' => 1]);
    }

    // el profesor no puede actualizar el módulo de otro curso que no es suyo
    public function test_teacher_cannot_update_another_teachers_module()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();
        $module = Module::factory()->of($course)->create();
        $this->actingAs($other)
            ->put(route('admin.modules.update', $module), ['title' => 'Hack'])
            ->assertForbidden();
    }

    // la actualización requiere un título
    public function test_update_requires_title()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $this->actingAs($teacher)
            ->put(route('admin.modules.update', $module), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    /* Destroy */
    // el propietario puede eliminar módulo
    public function test_owner_can_delete_module()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $this->actingAs($teacher)
            ->delete(route('admin.modules.destroy', $module))
            ->assertRedirect();
        $this->assertSoftDeleted($module); // verifica que no haya sido borrado de BD (si softdelete)
    }

    // borrar un módulo también marca como borradas sus lecciones (cascada)
    public function test_owner_can_delete_module_also_soft_deletes_its_lessons()
    {
        // armamos el árbol: teacher crea el curso, el curso tiene un módulo y el módulo una lección
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $lesson  = Lesson::factory()->inModule($module)->create();

        // el teacher se "loguea" y manda un DELETE a la ruta de borrar el módulo
        $this->actingAs($teacher)
            ->delete(route('admin.modules.destroy', $module))
            ->assertRedirect();

        // la lupa: ¿quedó marcado el módulo?... y ¿quedó marcada su lección?
        $this->assertSoftDeleted($module);
        $this->assertSoftDeleted($lesson);
    }

    // el profesor no puede borrar el módulo de otro
    public function test_teacher_cannot_delete_another_teachers_module()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();
        $module = Module::factory()->of($course)->create();
        $this->actingAs($other)
            ->delete(route('admin.modules.destroy', $module))
            ->assertForbidden();
        $this->assertNotSoftDeleted($module);
    }

    /* Move */
    // Verifica que mover un módulo hacia abajo intercambie su posición con el siguiente módulo.
    public function test_move_down_swaps_with_next()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $m1      = Module::factory()->of($course)->create(['order' => 1]);
        $m2      = Module::factory()->of($course)->create(['order' => 2]);
        $this->actingAs($teacher)
            ->post(route('admin.modules.move', $m1), ['direction' => 'down'])
            ->assertRedirect();
        $this->assertEquals(2, $m1->fresh()->order);
        $this->assertEquals(1, $m2->fresh()->order);
    }

    // Verifica que mover un módulo hacia arriba intercambie su posición con el módulo anterior.
    public function test_move_up_swaps_with_previous()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $m1      = Module::factory()->of($course)->create(['order' => 1]);
        $m2      = Module::factory()->of($course)->create(['order' => 2]);
        $this->actingAs($teacher)
            ->post(route('admin.modules.move', $m2), ['direction' => 'up'])
            ->assertRedirect();
        $this->assertEquals(2, $m1->fresh()->order);
        $this->assertEquals(1, $m2->fresh()->order);
    }

    // Verifica el "caso al límite": mover hacia arriba un módulo que ya está en la primera posición no debe alterar ningún orden.
    public function test_move_at_extreme_does_not_change_orders()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $m1      = Module::factory()->of($course)->create(['order' => 1]);
        $m2      = Module::factory()->of($course)->create(['order' => 2]);
        $this->actingAs($teacher)
            ->post(route('admin.modules.move', $m1), ['direction' => 'up'])
            ->assertRedirect();
        $this->assertEquals(1, $m1->fresh()->order);
        $this->assertEquals(2, $m2->fresh()->order);
    }

    // Este test está diseñado para asegurar el aislamiento entre cursos. Busca verificar que al reordenar los módulos de un curso, no se alteren por error los módulos de otro curso diferente, incluso si comparten los mismos números de orden (por ejemplo, que ambos tengan un módulo con 'order' => 1).
    public function test_move_does_not_affect_modules_from_other_course()
    {
        $teacher = User::factory()->teacher()->create();
        $c1      = Course::factory()->of($teacher)->create();
        $c2      = Course::factory()->of($teacher)->create();
        $m1c1    = Module::factory()->of($c1)->create(['order' => 1]);
        $m2c1    = Module::factory()->of($c1)->create(['order' => 2]);
        $m1c2    = Module::factory()->of($c2)->create(['order' => 1]);
        $this->actingAs($teacher)
            ->post(route('admin.modules.move', $m1c1), ['direction' => 'down'])
            ->assertRedirect();
        $this->assertEquals(1, $m1c2->fresh()->order);
    }

    /* funciones privadas para dentro del archivo */
    // garantiza que un test siempre reciba un juego de datos correctos para simular un módulo.
    // si lo llamas te devuelve los datos por defecto.
    // si le pasas datos en el argumento reemplaza el valor por defecto por el valor del argumento.
    private function validModuleData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Módulo de prueba',
        ], $overrides);
    }
}
