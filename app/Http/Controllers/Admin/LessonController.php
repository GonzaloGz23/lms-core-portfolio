<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
    public function store(StoreLessonRequest $request, Module $module): RedirectResponse
    {
        $validated = $request->validated();
        $lastOrder = $module->lessons()->max('order') ?? 0;
        $lesson    = $module->lessons()->create([
            ...$validated,
            'order' => $lastOrder + 1,
        ]);

        return back()->with('toast', [
            'type'    => 'success',
            'message' => "La lección '{$lesson->title}' fue creada exitosamente.",
        ]);
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->update($request->validated());

        return back()->with('toast', [
            'type'    => 'success',
            'message' => 'Lección actualizada correctamente.',
        ]);
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $this->authorize('manageContent', $lesson->module->course);

        $lesson->delete();

        return back()->with('toast', [
            'type'    => 'success',
            'message' => 'Lección eliminada correctamente.',
        ]);
    }

    public function move(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorize('manageContent', $lesson->module->course);

        $direction = $request->input('direction');

        $neighbor = $direction === 'up'
            ? Lesson::where('module_id', $lesson->module_id)
                ->where('order', '<', $lesson->order)
                ->orderByDesc('order')
                ->first()
            : Lesson::where('module_id', $lesson->module_id)
                ->where('order', '>', $lesson->order)
                ->orderBy('order')
                ->first();

        if (! $neighbor) {
            return back()->with('toast', [
                'type'    => 'info',
                'message' => 'No hay lección para mover en esa dirección.',
            ]);
        }

        DB::transaction(function () use ($lesson, $neighbor) {
            [$lesson->order, $neighbor->order] = [$neighbor->order, $lesson->order];
            $lesson->save();
            $neighbor->save();
        });

        return back()->with('toast', [
            'type'    => 'success',
            'message' => 'Orden actualizado correctamente.',
        ]);
    }
}
