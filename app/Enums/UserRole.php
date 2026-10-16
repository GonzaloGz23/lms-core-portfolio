<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Admin   = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Estudiante',
            self::Teacher => 'Profesor',
            self::Admin   => 'Administrador',
        };
    }

    /**
     * Ruta a la que se redirige al usuario según su rol tras autenticarse.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Student => 'dashboard',
            self::Teacher => 'admin.dashboard',
            self::Admin   => 'admin.dashboard',
        };
    }
}
