<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ranqueamento;
use App\Models\Score;
use App\Models\Escolha;
use App\Models\Hab;


class IndexController extends Controller
{
    public function index(){

       /* $ranqueamento = Ranqueamento::where('status',1)->first();

        return view('index',[
            'ranqueamento' => $ranqueamento
        ]);*/

        //$exibirResultados = true;

        $user = auth()->user();
        // ranqueamento ativo
        $ranqueamentoAtivo = Ranqueamento::where('status', 1)->first();

        // último ranqueamento encerrado
        $ranqueamentoFinalizado = Ranqueamento::where('status', 0)
            ->where('id', '!=', 0)  // Resultado ficará pronto em breve
            ->orderByDesc('ano')
            ->orderByDesc('id')
            ->first();

        $resultado = null;
        $notaCorte = null;
        $notasCortePorHabilitacao = collect();
        

        if ($user && $ranqueamentoFinalizado) {
            $resultado = Score::where('user_id', $user->id)
                ->where('ranqueamento_id', $ranqueamentoFinalizado->id)
                ->first();

        if ($resultado) {
            // Se foi classificado, usa hab_id_eleita
            if ($resultado->hab_id_eleita) {
                $habId = $resultado->hab_id_eleita;
                
                if (!$resultado->hab) {
                    $resultado->load('hab');
                }
            } 
            // Se não foi classificado, busca a escolha de prioridade 1
            else {
                $escolha = Escolha::where('ranqueamento_id', $ranqueamentoFinalizado->id)
                    ->where('user_id', $user->id)
                    ->where('prioridade', 1)
                    ->first();
                
                if ($escolha) {
                    $habId = $escolha->hab_id;
                    $resultado->hab = Hab::find($habId);
                }
            }
            
            // Calcular nota de corte se tiver habilitação
            if (isset($habId) && $habId && $resultado->hab) {
                $notaCorte = $this->calcularNotaCorte($ranqueamentoFinalizado->id, $habId, $resultado->hab->vagas);
            }

            // nota de corte de TODAS as habilitações que o aluno escolheu
            $notasCortePorHabilitacao = Escolha::with('hab')
                ->where('ranqueamento_id', $ranqueamentoFinalizado->id)
                ->where('user_id', $user->id)
                ->orderBy('prioridade')
                ->get()
                ->filter(fn($escolha) => $escolha->hab)
                ->map(function ($escolha) use ($ranqueamentoFinalizado, $resultado) {
                    return [
                        'prioridade'  => $escolha->prioridade,
                        'habilitacao' => $escolha->hab->nomhab,
                        'periodo'     => $escolha->hab->perhab,
                        'vagas'       => $escolha->hab->vagas,
                        'contemplado' => $resultado->hab_id_eleita == $escolha->hab_id,
                        'nota_corte'  => $this->calcularNotaCorte($ranqueamentoFinalizado->id, $escolha->hab_id, $escolha->hab->vagas),
                    ];
                })
                ->values();
        }
    }

        return view('index', [
            'ranqueamento' => $ranqueamentoAtivo,
            'ranqueamentoFinalizado' => $ranqueamentoFinalizado,
            'resultado' => $resultado,
            'notaCorte' => $notaCorte,
            'notasCortePorHabilitacao' => $notasCortePorHabilitacao,
        ]);
    }

    private function calcularNotaCorte(int $ranqueamentoId, int $habId, int $vagas)
    {
        $ultimoClassificado = Score::where('ranqueamento_id', $ranqueamentoId)
            ->where('hab_id_eleita', $habId)
            ->where('posicao', $vagas)
            ->first();

        return $ultimoClassificado ? $ultimoClassificado->nota : null;
    }
}
