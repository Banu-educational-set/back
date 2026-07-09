<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Traits\HasMedia;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasMedia, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'national_code', 'password', 'province_id', 'city_id',
        'marriage_status', 'birthday', 'gender', 'address', 'bio', 'status', 'block_reason',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthday' => 'date',
            'status' => UserStatus::class,
        ];
    }

    public function termEnrollments(): HasMany
    {
        return $this->hasMany(TermEnrollment::class);
    }

    public function completedTerms(): HasMany
    {
        return $this->hasMany(TermEnrollment::class)
            ->where('status', \App\Enums\EnrollmentStatus::Completed->value);
    }

    public function homeworkSubmissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class);
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'student_id');
    }

    public function registerData(): HasMany
    {
        return $this->hasMany(RegisterData::class);
    }

    public function memories(): HasMany
    {
        return $this->hasMany(MissionaryMemory::class, 'missionary_id');
    }

    public function avatar(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection_name', 'avatar')
            ->latestOfMany();
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
