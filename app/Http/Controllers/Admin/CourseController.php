<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Lista de cursos.
     * El admin ve todos; el teacher solo los que creó.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Course::class);

        $query = Course::with('creator.profile')->withCount('modules');

        if ($request->user()->isTeacher()) {
            $query->forTeacher($request->user());
        }

        $courses = $query->latest()->paginate(10);

        return Inertia::render('admin/courses/index', [
            'courses' => $courses,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Course::class);

        return Inertia::render('admin/courses/create', [
            'levels' => array_map(
                fn (CourseLevel $level) => ['value' => $level->value, 'label' => $level->label()],
                CourseLevel::cases()
            ),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug']       = $validated['slug'] ?? $this->generateUniqueSlug($validated['title']);
        $validated['created_by'] = $request->user()->id;

        $course = Course::create($validated);

        return redirect()->route('admin.courses.index')
            ->with('toast', [
                'type'    => 'success',
                'message' => "El curso '{$course->title}' fue creado exitosamente.",
            ]);
    }

    public function show(Course $course): Response
    {
        $this->authorize('view', $course);

        $course->load('creator.profile', 'modules.lessons');

        return Inertia::render('admin/courses/show', [
            'course' => $course,
        ]);
    }

    public function edit(Course $course): Response
    {
        $this->authorize('update', $course);

        return Inertia::render('admin/courses/edit', [
            'course' => $course,
            'levels' => array_map(
                fn (CourseLevel $level) => ['value' => $level->value, 'label' => $level->label()],
                CourseLevel::cases()
            ),
        ]);
    }

    /**
     * Builder de contenido del curso (módulos + lecciones).
     */
    public function content(Course $course): Response
    {
        $this->authorize('manageContent', $course);

        $course->load([
            'modules' => fn ($query) => $query->ordered()->with([
                'lessons' => fn ($query) => $query->ordered(),
            ]),
        ]);

        return Inertia::render('admin/courses/content', [
            'course' => $course,
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validated();

        // Regenera el slug solo si cambió el título y no se envió uno nuevo.
        if (empty($validated['slug'])) {
            $validated['slug'] = ($validated['title'] != $course->title)
               ? $this->generateUniqueSlug($validated['title'])
               : $course->slug;
        }

        $course->update($validated);

        return redirect()->route('admin.courses.index')->with(
            'toast',
            [
                'type'    => 'success',
                'message' => 'Curso actualizado correctamente',
            ]
        );
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $course->delete();

        return redirect()->route('admin.courses.index')->with(
            'toast',
            [
                'type'    => 'success',
                'message' => 'Curso eliminado correctamente',
            ]
        );
    }

    /*
     * Helpers
     */

    /**
     * Genera un slug único a partir del título, añadiendo un sufijo numérico si ya existe.
     */
    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug     = $baseSlug;
        $counter  = 2;

        while (Course::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
