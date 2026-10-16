<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property UserRole $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'email',
        'password',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            // Solo soft-deleteamos el perfil si el usuario se soft-deletea.
            // En un forceDelete, el cascade de la BD se encarga del borrado permanente.
            if (! $user->isForceDeleting()) {
                $user->profile()->delete();
            }
        });

        static::restoring(function (User $user) {
            $user->profile()->withTrashed()->restore();
        });
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courseCreated(): HasMany
    {
        return $this->hasMany(Course::class, 'created_by');
    }

    /**
     * @return HasOne<Profile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public static function findByGoogle(string $googleId): ?self
    {
        return static::where('google_id', $googleId)->first();
    }

    public static function findByEmail(string $email): ?self
    {
        return static::where('email', $email)->first();
    }
}
