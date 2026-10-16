<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'module_id',
        'title',
        'content',
        'video_url',
        'video_duration_sec',
        'external_video_id',
        'order',
        'is_free_preview',
    ];

    protected function casts(): array
    {
        return [
            'video_duration_sec' => 'integer',
            'is_free_preview'    => 'boolean',
        ];
    }

    /**
     * @param  Builder<Lesson>  $query
     * @return Builder<Lesson>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
