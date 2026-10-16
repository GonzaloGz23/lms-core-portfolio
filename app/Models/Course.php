<?php

namespace App\Models;

use App\Enums\CourseLevel;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'syllabus',
        'level',
        'base_price',
        'offer_price',
        'duration_months',
        'extension_price_month',
        'is_published',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Course $course) {
            if (! $course->isForceDeleting()) {
                foreach ($course->modules as $module) {
                    $module->delete();
                }
            }
        });

        static::restoring(function (Course $course) {
            foreach ($course->modules()->withTrashed()->get() as $module) {
                $module->restore();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'level'                 => CourseLevel::class,
            'base_price'            => 'decimal:2',
            'offer_price'           => 'decimal:2',
            'extension_price_month' => 'decimal:2',
            'is_published'          => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopeForTeacher(Builder $query, User $user): Builder
    {
        return $query->where('created_by', $user->id);
    }
}
