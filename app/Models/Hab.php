<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Hab extends Model
{

    use HasFactory;

    protected $fillable = [
        'ranqueamento_id',
        'codhab',
        'nomhab',
        'perhab',
        'permite_ambos_periodos',
        'vagas',
    ];

    public static function mapeamento(){ //primeiro semestre
        return [
            502 => 2502,
            504 => 2504,
            1202 => 3202,
            1302 => 3302,
            1702 => 3702,
            602 => 2602,
            604 => 2604,
            702 => 2702,
            704 => 2704,
            402 => 2402,
            404 => 2404,
            1404 => 3404,
            802 => 2802,
            804 => 2804,
            902 => 2902,
            904 => 2904,
            1502 => 3502,
            1504 => 3504,
            302 => 2302,
            304 => 2304,
            1602 => 3602,
            1604 => 3604,
            1002 => 3002,
            1102 => 3102
        ];
    }

    public function escolhas(): HasMany
    {
        return $this->hasMany(Escolha::class);
    }

    public function ranqueamentos(): BelongsTo
    {
        return $this->belongsTo(Escolha::class);
    }

    protected function nomhab(): Attribute
    {
        return Attribute::make(
            get:fn (string $value) => trim(str_replace('Bacharelado - Habilitação:','', $value)),
        );
    }
}
