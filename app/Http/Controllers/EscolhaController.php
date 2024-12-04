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
use App\Service\Utils;
use Maatwebsite\Excel\Excel;
use App\Exports\ExcelExport;

class EscolhaController extends Controller
{
    public function index(Ranqueamento $ranqueamento){
        Gate::authorize('admin');
        $grouped = Escolha::where('ranqueamento_id',$ranqueamento->id)
                            ->get()
                            ->groupBy('user_id');
        return view('escolhas.index',[
            'grouped' => $grouped,
            'ranqueamento' => $ranqueamento
        ]); 
    }

    public function excel(Excel $excel, Ranqueamento $ranqueamento){
        $headings = [
            'Número USP','Nome','Declinou do Português?','Opção 1','Opção 2','Opção 3','Opção 4','Opção 5','Opção 6','Opção 7'
        ];

        $grouped = Escolha::join('habs','escolhas.hab_id','habs.id')
        ->join('users','escolhas.user_id','users.id')
        ->where('escolhas.ranqueamento_id',Ranqueamento::first()->id)
        ->get()
        ->groupBy('user_id');

        $data = []; // Inicializando array para exportação

        foreach ($grouped as $userId => $choices) {
            $user = User::find($userId);
            $codpes = $user->codpes ?? '-';
            $nome = $user->name ?? '-';
            $declinou = Utils::declinou($userId, $ranqueamento->id) ? 'Sim' : 'Não';

            $opcoes = []; // Guardar as opções do usuário
            for ($prioridade = 1; $prioridade <= 7; $prioridade++) {
                $choice = $choices->where('prioridade', $prioridade)->first();
                $opcoes[] = $choice ? Hab::find($choice->hab_id)->nomhab ?? '-' : '-';
            }
            // Adicionar linha para exportação
            $data[] = array_merge([$codpes, $nome, $declinou], $opcoes);
        }
        $export = new ExcelExport($data, $headings);
        return $excel->download($export, 'lista_de_nomes.xlsx');
    }

    public function form(){
        Gate::authorize('ciclo_basico');
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
            'escolhas' => $escolhas
        ]); 
    }

    public function store(Request $request){
        Gate::authorize('ciclo_basico');
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
        }
        return redirect("/");
    }

    public function declinar(Request $request){
        Gate::authorize('ciclo_basico');
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
