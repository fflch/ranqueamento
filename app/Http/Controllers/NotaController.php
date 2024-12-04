<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Service\Utils;
use App\Models\Escolha;
use App\Models\User;

class NotaController extends Controller
{
    public function show($codpes){
    Gate::authorize('admin');
    foreach(Escolha::disciplinas() as $disciplina){
        $notas[] = Utils::get_nota($codpes, $disciplina); //$nota[1] é o coddis. $nota[0] é a nota.
        $somente_notas = array_column($notas, 0);
    }
    //gerando variáveis para o blade. É necessário mostrar a soma das notas das disciplinas.
    $soma_notas = array_sum($somente_notas); 
    $media_um = $soma_notas / 4; //Dividir pelo total de disciplinas obrigatórias, e não pelo count() delas

    foreach(Escolha::disciplinas_segundo() as $disciplina_dois){
        $notas_segundo[] = Utils::get_nota($codpes, $disciplina_dois);
        $somente_notas2 = array_column($notas_segundo, 0);
    }
    //no array das disciplinas há itens vazios?
    $vazio = Escolha::verifica_null($notas_segundo);
    if($vazio){
        foreach($vazio as $indice){
            $notas_segundo[$indice] = [0, 'N/A']; //para cada disciplina não pega, a nota será 0 e o coddis = N/A
        }
    }

    if(!Escolha::verifica_null($notas_segundo)){
        $soma_notas2 = array_sum($somente_notas2);
        $media_dois = $soma_notas2 * 2 / 4;
        $media_final = array_sum([$media_dois + $media_um]) / 3;
    }
        return view('notas.show', [
            'user' => User::where('codpes',$codpes)->first(),
            'notas' => $notas,
            'notas_segundo' => $notas_segundo,
            'soma_notas' => $soma_notas,
            'soma_notas2' => $soma_notas2,
            'media_um' => $media_um,
            'media_dois' => $media_dois,
            'media_final' => $media_final
        ]);
    }
}
