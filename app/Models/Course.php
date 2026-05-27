<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Course extends Model
{
    use HasFactory, HasMedia;

    protected $fillable = ['term_id', 'teacher_id', 'title', 'description', 'capacity', 'is_active', 'prerequisite_course_ids'];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'prerequisite_course_ids' => 'array',
    ];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class);
    }

    public function cover(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection_name', 'cover')
            ->latestOfMany();
    }
}
