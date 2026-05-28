<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = ['session_id', 'title', 'description', 'score', 'minimum_score', 'duration_minutes', 'is_active', 'is_random'];

    protected $casts = [
        'score' => 'integer',
        'minimum_score' => 'integer',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'is_random' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

}
