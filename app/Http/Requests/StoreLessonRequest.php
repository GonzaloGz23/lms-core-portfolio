<?php

namespace App\Http\Requests;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $module = $this->route('module');

        return $module instanceof Module
            && $this->user()->can('manageContent', $module->course);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'              => ['required', 'string', 'max:255'],
            'content'            => ['nullable', 'string'],
            'video_url'          => ['nullable', 'string', 'max:2048'],
            'video_duration_sec' => ['nullable', 'integer', 'min:1'],
            'external_video_id'  => ['nullable', 'string', 'max:255'],
            'is_free_preview'    => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título de la lección es obligatorio.',
        ];
    }
}
