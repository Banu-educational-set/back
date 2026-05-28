<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'exam_id', 'score', 'is_passed', 'started_at', 'submitted_at'];

    protected $casts = [
        'is_passed' => 'boolean',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'attempt_id');
    }

    /**
     * The hard cutoff for this attempt: min(started_at + duration, term.ends_at).
     * Null if neither constraint applies (no duration, no term end).
     */
    public function getDeadlineAtAttribute(): ?\Carbon\Carbon
    {
        $byDuration = null;
        $duration = $this->exam?->duration_minutes;
        if ($this->started_at && $duration) {
            $byDuration = $this->started_at->copy()->addMinutes((int) $duration);
        }

        $byTerm = $this->exam?->session?->course?->term?->ends_at;

        return collect([$byDuration, $byTerm])->filter()->min();
    }
}
