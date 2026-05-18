<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = ['session_id', 'title', 'description', 'pass_score', 'is_active'];

    protected $casts = [
        'pass_score' => 'integer',
        'is_active' => 'boolean',
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

    public function effectivePassScore(): int
    {
        return (int) ($this->pass_score ?? config('education.default_exam_pass_score'));
    }
}
