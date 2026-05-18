<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermEnrollment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'term_id', 'status', 'completed_at'];

    protected $casts = [
        'status' => EnrollmentStatus::class,
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', EnrollmentStatus::Completed->value);
    }
}
