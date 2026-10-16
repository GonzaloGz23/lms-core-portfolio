<?php

namespace App\Http\Requests;

use App\Enums\CourseLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Solo el admin o el dueño del curso pueden editarlo.
     * A diferencia de StoreCourseRequest (cualquier admin/teacher), aquí se comprueba
     * que el teacher sea el creador del curso.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('course'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // unique con ignore: el slug puede ser el mismo del propio curso, pero no el de otro.
            'slug'                  => ['nullable', 'string', 'max:255', Rule::unique('courses', 'slug')->ignore($this->route('course'))],
            'description'           => ['nullable', 'string'],
            'syllabus'              => ['nullable', 'string'],
            'level'                 => ['required', Rule::enum(CourseLevel::class)],
            'base_price'            => ['required', 'numeric', 'min:0'],
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
            'slug.unique'         => 'Ya existe otro curso con esa URL.',
        ];
    }

    /**
     * Si el slug viene vacío, se deja como null para que el controller lo genere desde el título.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug') && empty($this->slug)) {
            $this->merge(['slug' => null]);
        }
    }
}
