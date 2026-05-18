<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamOption extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'option_text', 'is_correct'];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    protected $hidden = ['is_correct'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }
}
