<?php

namespace App\Http\Requests;

use App\Enums\CourseLevel;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    /**
     * Solo admin y teacher pueden crear cursos.
     * El middleware de rutas ya filtra esto, pero se refuerza aquí.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Course::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // Opcional: si no viene, el controller lo genera desde el título.
            'slug'        => ['nullable', 'string', 'max:255', 'unique:courses,slug'],
            'description' => ['nullable', 'string'],
            'syllabus'    => ['nullable', 'string'],
            'level'       => ['required', Rule::enum(CourseLevel::class)],
            'base_price'  => ['required', 'numeric', 'min:0'],
            // El precio de oferta, si existe, debe ser menor que el base.
            'offer_price'           => ['nullable', 'numeric', 'min:8', 'lt:base_price'],
            'duration_months'       => ['required', 'integer', 'min:1', 'max:24'],
            'extension_price_month' => ['nullable', 'numeric', 'min:0'],
            'is_published'          => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required'      => 'El título del curso es obligatorio.',
            'level.required'      => 'Tenés que seleccionar un nivel.',
            'base_price.required' => 'El precio base es obligatorio.',
            'offer_price.lt'      => 'El precio de oferta debe ser menor al precio base.',
            'duration_months.max' => 'La duración no puede superar los 24 meses.',
        ];
    }

    /**
     * Si el slug viene vacío, se deja como null para que el controller lo genere.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug') && empty($this->input('slug'))) {
            $this->merge(['slug' => null]);
        }
    }
}
