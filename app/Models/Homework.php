<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    use HasFactory, HasMedia;

    protected $table = 'homeworks';

    protected $fillable = ['session_id', 'title', 'description', 'deadline', 'is_active', 'is_priority'];

    protected $casts = [
        'deadline' => 'datetime',
        'is_active' => 'boolean',
        'is_priority' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class);
    }

    /**
     * The latest moment a submission is accepted for this homework. Falls
     * back to the parent term's ends_at when the homework's own deadline
     * is null. Null if neither is set (open-ended).
     */
    public function effectiveDeadline(): ?\Carbon\Carbon
    {
        if ($this->deadline) {
            return $this->deadline;
        }

        return $this->session?->course?->term?->ends_at;
    }
}
