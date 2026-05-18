<?php

namespace App\Models;

use App\Enums\HomeworkSubmissionStatus;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    use HasFactory, HasMedia;

    protected $fillable = [
        'homework_id', 'user_id', 'status', 'teacher_feedback', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'status' => HomeworkSubmissionStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
