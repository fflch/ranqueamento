<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model

 {

    use HasFactory;

    protected $fillable = [
        'ranqueamento_id',
        'user_id',
        'nota',
        'posicao',
        'codpes',
        'hab_id_eleita',
        'prioridade_eleita',
        'codhab_jupiterweb',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ranqueamento(): BelongsTo
    {
        return $this->belongsTo(Ranqueamento::class);
    }

    public function hab(): BelongsTo
    {
        return $this->belongsTo(Hab::class, 'hab_id_eleita', 'id');
    }
}
