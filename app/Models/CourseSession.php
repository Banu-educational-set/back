<?php

namespace App\Models;

use App\Enums\SessionType;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSession extends Model
{
    use HasFactory, HasMedia;

    protected $table = 'course_sessions';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'type',
        'starts_at',
        'location',
        'link',
        'prerequisite_session_ids',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'type' => SessionType::class,
        'prerequisite_session_ids' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'session_id');
    }

    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'session_id');
    }
}
