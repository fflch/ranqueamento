<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Ranqueamento extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ano',
        'tipo',
        'status',
    ];
    

    public function escolhas(): HasMany
    {
        return $this->hasMany(Escolha::class);
    }

    public function habs(): HasMany
    {
        return $this->hasMany(Hab::class);
    }

    public function declinios(): HasMany
    {
        return $this->hasMany(Declinio::class);
    }

    protected function max(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tipo=='ingressantes' ? 7:1,
        );
    }

}
