<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escolha extends Model
{
    #ver possibilidade de trazer os dados do replicado
    public static function disciplinas(){ //primeiro semestre
        return [
            'FLC0112',
            'FLC0114',
            'FLL0433',
            'FLT0123',
        ];
    } 

    public static function disciplinas_segundo(){ //segundo semestre
        return [
            'FLC0113',
            'FLC0115',
            'FLL0434',
            'FLT0124'
        ];
    }
    /* 
        Busca a chave do item do array caso o aluno não tenha pego
        alguma disciplina, e no array $notas_segundo[]
        fique como null
    */
    public static function verifica_null(array $keyNull): array{
        $keyNull = array_keys(array_filter($keyNull, fn($value) => empty($value))); //pega a key do array vazio
        return $keyNull;
    }
}
