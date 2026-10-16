<?php

namespace Tests\Feature\Admin;

use App\Enums\CourseLevel;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase; // borra y recrea la BD antes de cada prueba.
use Inertia\Testing\AssertableInertia as Assert; // comprueba qué le envía el servidor a la página web.
use Tests\TestCase; // clase base del que heredan todos los test.

class CourseTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------------ */
    /* Acceso y middleware */
    /* ------------------------------------------------------------------ */

    // los invitados son redirigidos al lógin
    /* simula visitar la página sin haber iniciado sesión. Comprueba que te redirige al login */
    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('admin.courses.index'))->assertRedirect(route('login'));
    }

    // Los estudiantes son redirigidos a su dashboard
    public function test_students_are_redirected_to_their_dashboard()
    {
        $student = User::factory()->create(); // crea un usuario en la BD ("estudiante" por defecto).

        $this->actingAs($student) // simula que este usuario inició sesión.
            ->get(route('admin.courses.index')) // visita la página de cursos (no debería poder)
            ->assertRedirect(route('dashboard')); // lo manda a su panel normal
    }

    // los administradores pueden ver la lista de cursos
    /* Crea un usuario administrador y comprueba que si puede entrar (assertOk() = 200 ) */
    public function test_admin_can_visit_the_courses_index()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk();
    }

    // los profesores pueden ver la lista de cursos
    public function test_teacher_can_visit_the_courses_index()
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('admin.courses.index'))
            ->assertOk();
    }

    /* ------------------------------------------------------------------ */
    /* index */
    /* ------------------------------------------------------------------ */

    // muestra la lista de cursos
    /* Comprueba que la página cargada es exactamente el compornente frontend llamado */
    public function test_index_returns_the_courses_component()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertInertia(fn (Assert $page) => $page->component('admin/courses/index'));
    }

    // el admin ve todos los cursos
    public function test_admin_sees_all_courses()
    {
        $admin   = User::factory()->admin()->create(); // crea un admin
        $teacher = User::factory()->teacher()->create(); // crea un profesor
        Course::factory()->of($teacher)->count(3)->create(); // crea 3 cursos para un profesor

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertInertia(fn (Assert $page) => $page->has('courses.data', 3)); // comprueba que el admin ve los 3 cursos (has... hay 3 elementos dentro de courses.data)
    }

    // los profesores solo ven los cursos propios
    /* crea 2 profesores. UNo tiene 2 cursos, el otro tiene 1. Cuando el primer profesor entra, solo debería ver sus 2 cursos, no el otro. Comprueba que un profesor no ve los cursos ajenos. */
    public function test_teacher_only_sees_their_own_courses()
    {
        $teacher = User::factory()->teacher()->create();
        $other   = User::factory()->teacher()->create();
        Course::factory()->of($teacher)->count(2)->create();
        Course::factory()->of($other)->create();

        $this->actingAs($teacher)
            ->get(route('admin.courses.index'))
            ->assertInertia(fn (Assert $page) => $page->has('courses.data', 2));
    }

    /* ------------------------------------------------------------------ */
    /* create / store */
    /* ------------------------------------------------------------------ */

    // el admin puede visitar el formulario de creación de cursos.
    /* comprueba que la página "crear curso" carga el componente correcto y que envía una lista de 3 niveles(principiante,intermedio/avanzado, por ejemplo) para el formulario. */
    public function test_admin_can_visit_the_create_form()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.courses.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/courses/create')
                ->has('levels', 3));
    }

    // el admin puede crear un curso
    /* Envía (post) datos válidos de un curso (usando el método validCourseData() de abajo).
    Comprueba que después te redirige a la lista de cursos.
    Comprueba que en la base de datos realmente se guardó un curso con ese título, ese "slug" (una versión del título para usar en URLs, tipo curso-de-laravel) y que quedó registrado que lo creó el admin. */
    public function test_admin_can_create_a_course()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.courses.store'), $this->validCourseData());

        $response->assertRedirect(route('admin.courses.index'));
        $this->assertDatabaseHas('courses', [
            'title'      => 'Curso de Laravel',
            'slug'       => 'curso-de-laravel',
            'created_by' => $admin->id,
        ]);
    }

    // los profesores pueden crear cursos
    public function test_teacher_can_create_a_course()
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->post(route('admin.courses.store'), $this->validCourseData())
            ->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'title'      => 'Curso de Laravel',
            'created_by' => $teacher->id,
        ]);
    }

    // slug se genera de manera única a partir de título
    /* primero crea un curso ya existente con slug curso-de-laravel. Luego el admin intenta crear otro curso con el mismo título. Como el slug ya existía, el sistema debe generar uno distinto automáticamente: curso-de-laravel-2. Esto evita que dos cursos tengan la misma URL. */
    public function test_slug_is_generated_uniquely_from_the_title()
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->create(['title' => 'Curso de Laravel', 'slug' => 'curso-de-laravel']);

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), $this->validCourseData());

        $this->assertDatabaseHas('courses', ['slug' => 'curso-de-laravel-2']);
    }

    // el título es obligatorio
    /* Intenta crear un curso sin título (título vacío). Comprueba que da un error de validación en title y que no se guardó nada en la base de datos. */
    public function test_course_requires_a_title()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), $this->validCourseData(['title' => '']))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('courses', 0);
    }

    // el precio de oferta debe ser menor que el precio base
    /* Prueba una regla de negocio: el precio de oferta (offer_price) debe ser menor que el precio base (base_price). Aquí pone la oferta más cara que el precio normal (¡al revés!), así que debe fallar. */
    public function test_offer_price_must_be_lower_than_base_price()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), $this->validCourseData([
                'base_price'  => 1000,
                'offer_price' => 2000,
            ]))
            ->assertSessionHasErrors('offer_price');

        $this->assertDatabaseCount('courses', 0);
    }

    // el nivel debe ser un valor válidos
    /* Intenta poner un nivel 'expert' que no existe en la lista CourseLevel (recuerda, había 3 niveles válidos). Debe rechazarlo. */
    public function test_level_must_be_a_valid_enum_value()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), $this->validCourseData(['level' => 'expert']))
            ->assertSessionHasErrors('level');

        $this->assertDatabaseCount('courses', 0);
    }

    /* ------------------------------------------------------------------ */
    /* show */
    /* ------------------------------------------------------------------ */

    // el admin puede ver cualquier curso
    /* El admin ve el detalle de cualquier curso (no importa quién lo creó). Comprueba que carga el componente correcto y que el curso mostrado es el que corresponde (where('course.id', ...) compara el id). */
    public function test_admin_can_view_any_course()
    {
        $admin  = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.courses.show', $course))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/courses/show')
                ->where('course.id', $course->id));
    }

    // el profesor puede ver su propio curso
    public function test_teacher_can_view_their_own_course()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();

        $this->actingAs($teacher)
            ->get(route('admin.courses.show', $course))
            ->assertOk();
    }

    // el profesor no puede ver el curso de otro profesor
    /* Un profesor ($other) intenta ver el curso de otro profesor ($owner). Debe recibir "prohibido" (assertForbidden = error 403). */
    public function test_teacher_cannot_view_another_teachers_course()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();

        $this->actingAs($other)
            ->get(route('admin.courses.show', $course))
            ->assertForbidden();
    }

    /* ------------------------------------------------------------------ */
    /* edit / update */
    /* ------------------------------------------------------------------ */

    // el admin puede ver el formulario de edición de cualquier curso
    public function test_admin_can_visit_the_edit_form_of_any_course()
    {
        $admin  = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.courses.edit', $course))
            ->assertInertia(fn (Assert $page) => $page->component('admin/courses/edit'));
    }

    // Un profesor no puede abrir el formulario de edición de un curso que no es suyo (403 prohibido).
    public function test_teacher_cannot_edit_another_teachers_course()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();

        $this->actingAs($other)
            ->get(route('admin.courses.edit', $course))
            ->assertForbidden();
    }

    // el admin puede actualizar cualquier curso
    /* Crea un curso con título viejo, el admin lo actualiza (put = método HTTP para "actualizar") a "Nuevo título". Comprueba que se guardó el cambio y que el slug también se actualizó (nuevo-titulo). */
    public function test_admin_can_update_any_course()
    {
        $admin  = User::factory()->admin()->create();
        $course = Course::factory()->create(['title' => 'Viejo título']);

        $this->actingAs($admin)
            ->put(route('admin.courses.update', $course), $this->validCourseData([
                'title' => 'Nuevo título',
            ]))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'id'    => $course->id,
            'title' => 'Nuevo título',
            'slug'  => 'nuevo-titulo',
        ]);
    }

    // El profesor dueño del curso puede actualizarlo.
    public function test_owner_teacher_can_update_their_course()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();

        $this->actingAs($teacher)
            ->put(route('admin.courses.update', $course), $this->validCourseData())
            ->assertRedirect(route('admin.courses.index'));
    }

    // Un profesor no puede actualizar un curso que no es suyo (403 prohibido).
    public function test_teacher_cannot_update_another_teachers_course()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();

        $this->actingAs($other)
            ->put(route('admin.courses.update', $course), $this->validCourseData())
            ->assertForbidden();
    }

    // El slug se mantiene cuando el título no cambia.
    /* Si actualizas un curso pero el título no cambia, el slug debe quedarse igual que antes (slug-original), no regenerarse. */
    public function test_slug_is_preserved_when_the_title_does_not_change()
    {
        $admin  = User::factory()->admin()->create();
        $course = Course::factory()->create(['title' => 'Curso de Laravel', 'slug' => 'slug-original']);

        $this->actingAs($admin)
            ->put(route('admin.courses.update', $course), $this->validCourseData([
                'title' => 'Curso de Laravel',
            ]));

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'slug' => 'slug-original']);
    }

    // Al contrario: si sí cambias el título, el slug se debe recalcular según el nuevo título.
    public function test_slug_is_regenerated_when_the_title_changes()
    {
        $admin  = User::factory()->admin()->create();
        $course = Course::factory()->create(['title' => 'Viejo título', 'slug' => 'viejo-titulo']);

        $this->actingAs($admin)
            ->put(route('admin.courses.update', $course), $this->validCourseData([
                'title' => 'Título nuevo',
            ]));

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'slug' => 'titulo-nuevo']);
    }

    /* ------------------------------------------------------------------ */
    /* destroy */
    /* ------------------------------------------------------------------ */

    // el admin puede eliminar cualquier curso.
    /* El admin puede borrar cualquier curso. assertSoftDeleted comprueba un "borrado suave": el registro no se elimina físicamente de la base de datos, solo se marca como borrado (para poder recuperarlo si hace falta). */
    public function test_admin_can_delete_any_course()
    {
        $admin  = User::factory()->admin()->create();
        $course = Course::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertSoftDeleted($course);
    }

    // el profesor puede eliminar su propio curso.
    public function test_owner_teacher_can_delete_their_course()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();

        $this->actingAs($teacher)
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertSoftDeleted($course);
    }

    // borrar un curso también marca como borrados sus módulos y sus lecciones (cascada)
    public function test_delete_course_also_soft_deletes_its_modules_and_lessons()
    {
        $teacher = User::factory()->teacher()->create();
        $course  = Course::factory()->of($teacher)->create();
        $module  = Module::factory()->of($course)->create();
        $lesson  = Lesson::factory()->inModule($module)->create();

        $this->actingAs($teacher)
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect();

        $this->assertSoftDeleted($course);
        $this->assertSoftDeleted($module);
        $this->assertSoftDeleted($lesson);
    }

    // el profesor no puede eliminar un curso que no es suyo.
    /* Un profesor no puede borrar el curso de otro (403), y se comprueba que efectivamente sigue existiendo (no se borró). */
    public function test_teacher_cannot_delete_another_teachers_course()
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = Course::factory()->of($owner)->create();

        $this->actingAs($other)
            ->delete(route('admin.courses.destroy', $course))
            ->assertForbidden();

        $this->assertNotSoftDeleted($course);
    }

    /* ------------------------------------------------------------------ */
    /* Helper */
    /* ------------------------------------------------------------------ */
    /* Esta función no es una prueba, es una herramienta interna que usan casi todas las pruebas. Devuelve un array con datos "válidos" de ejemplo para crear un curso (título, nivel, precio, duración). El parámetro $overrides permite sobrescribir algunos campos específicos según lo que quiera probar cada test (por ejemplo, poner title vacío, o cambiar el level a algo inválido), sin tener que repetir todos los demás datos cada vez. array_merge combina el array por defecto con esos cambios. */
    private function validCourseData(array $overrides = []): array
    {
        return array_merge([
            'title'           => 'Curso de Laravel',
            'level'           => CourseLevel::Beginner->value,
            'base_price'      => 10000,
            'duration_months' => 6,
        ], $overrides);
    }
}
