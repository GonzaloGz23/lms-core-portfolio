<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// Google OAuth
Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::prefix('admin')->middleware(['auth', 'role:'.UserRole::Admin->value.','.UserRole::Teacher->value])->group(function () {
    Route::get('dashboard', function () {
        return inertia('admin/dashboard');
    })->name('admin.dashboard');
});

// Panel de administración: solo admin y teacher
Route::prefix('admin')->middleware(['auth', 'role:admin,teacher'])->group(function () {
    // Cursos
    Route::get('/courses', [CourseController::class, 'index'])->name('admin.courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('admin.courses.create');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('admin.courses.show');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('admin.courses.edit');
    Route::get('/courses/{course}/content', [CourseController::class, 'content'])->name('admin.courses.content');
    Route::post('/courses', [CourseController::class, 'store'])->name('admin.courses.store');
    Route::put('courses/{course}', [CourseController::class, 'update'])->name('admin.courses.update');
    Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('admin.courses.destroy');

    // Módulos
    Route::post('/courses/{course}/modules', [ModuleController::class, 'store'])->name('admin.modules.store');
    Route::put('/modules/{module}', [ModuleController::class, 'update'])->name('admin.modules.update');
    Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('admin.modules.destroy');
    Route::post('/modules/{module}/move', [ModuleController::class, 'move'])->name('admin.modules.move');

    // Lecciones
    Route::post('/modules/{module}/lessons', [LessonController::class, 'store'])->name('admin.modules.lessons.store');
    Route::put('/lessons/{lesson}', [LessonController::class, 'update'])->name('admin.lessons.update');
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->name('admin.lessons.destroy');
    Route::post('/lessons/{lesson}/move', [LessonController::class, 'move'])->name('admin.lessons.move');
});
