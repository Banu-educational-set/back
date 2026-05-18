<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegisterData extends Model
{
    protected $table = 'register_data';

    protected $fillable = ['user_id', 'key', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
