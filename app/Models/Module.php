<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Module $module) {
            // Cascada hacia abajo: al eliminar un módulo se eliminan (lógicamente) sus lecciones.
            if (! $module->isForceDeleting()) {
                $module->lessons()->delete();
            }
        });

        static::restoring(function (Module $module) {
            $module->lessons()->withTrashed()->restore();
        });
    }

    /**
     * @param  Builder<Module>  $query
     * @return Builder<Module>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
