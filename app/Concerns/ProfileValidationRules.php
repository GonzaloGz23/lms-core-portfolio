<?php

/**
 *  Este archivo es un trait (código reutilizable) qie centraliza las reglas de validación de los datos del perfil. Como los nombres y emails se validan igual en distintos lugares(registro, edición de perfil), se define aquí una sola vez y se "inyecta" en los formularios que lo necesiten. Existen tres métodos protegidos:
 * profilesRules() - devuelve el array completo de reglas. El $userId se pasa solo para la regla de unicidad del email (para ignorar al propio usuario al editar ya que sino, salta el error de email ya en uso, al igual que en los demás métodos.).
 * nameRules() - reglas de un nombre.
 * emailRules() - reglas del email.
 */

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'first_name' => $this->nameRules(),
            'last_name'  => $this->nameRules(),
            'email'      => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:100'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
