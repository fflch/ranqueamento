<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Declinio extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ranqueamento(): BelongsTo
    {
        return $this->belongsTo(Ranqueamento::class);
    }
}
