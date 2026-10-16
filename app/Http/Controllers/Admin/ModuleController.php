<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModuleRequest;
use App\Http\Requests\UpdateModuleRequest;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
 * Acciones sobre módulos. Ninguna renderiza una página:
 * ejecutan la operación y vuelven a la vista anterior con un toast.
 */
class ModuleController extends Controller
{
    public function store(StoreModuleRequest $request, Course $course): RedirectResponse
    {
        $validated = $request->validated();
        $lastOrder = $course->modules()->max('order') ?? 0;
        $module    = $course->modules()->create([
            ...$validated,
            'order' => $lastOrder + 1,
        ]);

        return back()->with('toast', [
            'type'    => 'success',
            'message' => "El módulo '{$module->title}' fue creado exitosamente.",
        ]);
    }

    public function update(UpdateModuleRequest $request, Module $module): RedirectResponse
    {
        $module->update($request->validated());

        return back()->with('toast', [
            'type'    => 'success',
            'message' => 'Módulo actualizado correctamente.',
        ]);
    }

    public function destroy(Module $module): RedirectResponse
    {
        $this->authorize('manageContent', $module->course);
        $module->delete();

        return back()->with('toast', [
            'type'    => 'success',
            'message' => 'Módulo eliminado correctamente.',
        ]);
    }

    /**
     * Intercambia el orden del módulo con el de su vecino inmediato.
     */
    public function move(Request $request, Module $module): RedirectResponse
    {
        $this->authorize('manageContent', $module->course);

        $direction = $request->input('direction');

        $neighbor = $direction === 'up'
            ? Module::where('course_id', $module->course_id)
                ->where('order', '<', $module->order)
                ->orderByDesc('order')
                ->first()
            : Module::where('course_id', $module->course_id)
                ->where('order', '>', $module->order)
                ->orderBy('order')
                ->first();

        if (! $neighbor) {
            return back()->with('toast', [
                'type'    => 'info',
                'message' => 'No hay módulo para mover en esa dirección.',
            ]);
        }

        DB::transaction(function () use ($module, $neighbor) {
            [$module->order,$neighbor->order] = [$neighbor->order, $module->order];
            $module->save();
            $neighbor->save();
        });

        return back()->with('toast', [
            'type'    => 'success',
            'message' => 'Orden actualizado correctamente.',
        ]);
    }
}
