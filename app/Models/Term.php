<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Term extends Model
{
    use HasFactory, HasMedia;

    protected $fillable = ['title', 'description', 'is_active', 'starts_at', 'ends_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TermEnrollment::class);
    }

    public function cover(): MorphOne
    {
        return $this->morphOne(\App\Models\Media::class, 'model')
            ->where('collection_name', 'cover')
            ->latestOfMany();
    }
}
