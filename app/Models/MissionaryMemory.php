<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionaryMemory extends Model
{
    use HasFactory, HasMedia;

    protected $fillable = ['missionary_id', 'missionary_request_id', 'title', 'description'];

    public function missionary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'missionary_id');
    }

    public function missionaryRequest(): BelongsTo
    {
        return $this->belongsTo(MissionaryRequest::class);
    }
}
