<?php

namespace App\Models;

use App\Enums\MissionaryRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionaryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'missionary_id', 'external_source', 'external_reference_id',
        'requester_name', 'requester_phone',
        'title', 'subject', 'description', 'location', 'requested_date', 'status',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'status' => MissionaryRequestStatus::class,
    ];

    public function missionary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'missionary_id');
    }
}
