<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Term extends Model
{
    use HasFactory, HasMedia;

    protected $fillable = ['title', 'description', 'is_active', 'starts_at', 'ends_at', 'score', 'minimum_score'];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'score' => 'integer',
        'minimum_score' => 'integer',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TermEnrollment::class);
    }

    public function termSessions(): HasManyThrough
    {
        return $this->hasManyThrough(CourseSession::class, Course::class, 'term_id', 'course_id');
    }

    public function cover(): MorphOne
    {
        return $this->morphOne(\App\Models\Media::class, 'model')
            ->where('collection_name', 'cover')
            ->latestOfMany();
    }

    public function scopeOpenNow($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /**
     * True when the term's is_active flag is set AND now() falls within
     * [starts_at, ends_at]. Null bounds are treated as open-ended.
     */
    public function isOpenNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
