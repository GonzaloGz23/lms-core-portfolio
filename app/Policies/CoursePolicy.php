<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

/**
 * Centraliza la autorización de cursos y de su contenido.
 * Laravel resuelve la policy por convención: App\Models\Course → App\Policies\CoursePolicy.
 */
class CoursePolicy
{
    /**
     * Se ejecuta antes que cualquier ability y corta la evaluación.
     * Devuelve true  → el usuario siempre pasa (admin).
     * Devuelve false → el usuario siempre falla.
     * Devuelve null  → se continúa evaluando la ability específica.
     */
    public function before(User $user): ?bool
    {
        // null (y no false) para no denegar al dueño cuando no es admin.
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Course $course): bool
    {
        return $user->id === $course->created_by;
    }

    public function update(User $user, Course $course): bool
    {
        return $user->id === $course->created_by;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->id === $course->created_by;
    }

    public function manageContent(User $user, Course $course): bool
    {
        return $user->id === $course->created_by;
    }
}
