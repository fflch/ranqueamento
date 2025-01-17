<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Declinio;
use App\Models\Ranqueamento;
use App\Models\Hab;
use App\Models\Escolha;
use App\Models\User;
use App\Rules\EscolhaRule;
use App\Services\Utils;
use Maatwebsite\Excel\Excel;
use App\Exports\ExcelExport;
use App\Services\HabilitacaoService;

class EscolhaController extends Controller
{
    public function index(Ranqueamento $ranqueamento){
        Gate::authorize('admin');

        return view('escolhas.index',[
            'grouped' => HabilitacaoService::options($ranqueamento),
            'ranqueamento' => $ranqueamento,
        ]);
    }

    public function excel(Excel $excel, Ranqueamento $ranqueamento){
        Gate::authorize('admin');

        $export = new ExcelExport(
            HabilitacaoService::options($ranqueamento)->toArray(),
            HabilitacaoService::headings($ranqueamento)
        );
        return $excel->download($export, 'ranqueamento' . date('Y') . '.xlsx');
    }

    public function form(){
        Gate::authorize('elegivel');
        $ranqueamento = Ranqueamento::where('status',1)->first();
        $escolhas = Escolha::where('ranqueamento_id',$ranqueamento->id)
                            ->where('user_id', auth()->user()->id)
                            ->get();

        $habs = Hab::where('ranqueamento_id',$ranqueamento->id)
                    ->where(function ($query) {
                        $periodo = Utils::periodo();
                        $query->where('perhab', $periodo)
                              ->orWhere('permite_ambos_periodos', 1);
                    })->get();

        return view('escolhas.form',[
            'habs' => $habs,
            'escolhas' => $escolhas,
            'ranqueamento' => $ranqueamento
        ]);
    }

    public function store(Request $request){
        Gate::authorize('elegivel');
        $ranqueamento = Ranqueamento::where('status',1)->first();

        $request->validate([
            'habs' => [ 'required', new EscolhaRule],
        ]);

        $habs = array_filter($request->habs);

        // deletando opções
        $prioridades_salvas = Escolha::select('prioridade')
            ->where('ranqueamento_id',$ranqueamento->id)
            ->where('user_id', auth()->user()->id)
            ->pluck('prioridade')
            ->toArray();
        $prioridades_escolhidas = array_keys($habs);

        $prioridades_deletar = array_diff($prioridades_salvas,$prioridades_escolhidas);
        Escolha::where('ranqueamento_id',$ranqueamento->id)
                ->where('user_id', auth()->user()->id)
                ->whereIn('prioridade', $prioridades_deletar)
                ->delete();

        $flash = 'Escolha(s) para ranqueamento registrada(s) com sucesso!<br><br>';
        foreach($habs as $prioridade=>$hab_id) {
            $escolha = Escolha::where('ranqueamento_id',$ranqueamento->id)
                                ->where('user_id', auth()->user()->id)
                                ->where('prioridade', $prioridade)
                                ->first();
            if(!$escolha) {
                $escolha = new Escolha;
                $escolha->ranqueamento_id = $ranqueamento->id;
                $escolha->user_id = auth()->user()->id;
                $escolha->prioridade = $prioridade;
            }
            $escolha->hab_id = $hab_id;
            $escolha->save();
            $flash .= "Sua escolha na <b>opção {$prioridade}</b> para o ranqueamento foi a habilitação <span style='color:red;'>{$escolha->hab->nomhab}</span>.<br><br>";
        }

        $flash .= "<b>Atenção:</b> Enquanto o período de inscrição do ranqueamento estiver aberto, ainda é possível refazer a(s) escolha(s).";

        if(empty($habs) or is_null($habs)) {
            $flash = "<b>Atenção:</b> Nenhuma habilitação registrada para ranqueamento. Para participar do ranqueamento, escolha as habilitações que deseja concorrer.";
            $request->session()->flash('alert-danger',$flash);
        } else {
            $request->session()->flash('alert-info',$flash);
        }

        return redirect("/");
    }

    public function declinar(Request $request){
        Gate::authorize('elegivel');
        $ranqueamento = Ranqueamento::where('status',1)->first();

        if(!$ranqueamento) {
            $request->session()->flash('alert-danger','Não há ranqueamento ativo');
            return redirect("/");
        }

        if($request->declinar==1) {
            $declinio = new Declinio;
            $declinio->user_id = auth()->user()->id;
            $declinio->ranqueamento_id = $ranqueamento->id;
            $declinio->save();
        }

        if($request->declinar==0) {
            $declinio = Declinio::where('user_id', auth()->user()->id)
                                ->where('ranqueamento_id',$ranqueamento->id)->first();
            if($declinio) $declinio->delete();
        }

        return redirect("/");
    }
}
