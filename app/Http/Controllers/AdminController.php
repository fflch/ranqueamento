<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ranqueamento;
use App\Services\Utils;

class AdminController extends Controller
{
    public function ciclo_basico(Request $request){

        $ranqueamento = Ranqueamento::where('status',1)->first();
        if(!$ranqueamento){
            $request->session()->flash('alert-danger','Não há ranqueamento ativo');
            return redirect('/');
        }

        $ciclo_basico_elegiveis = Utils::ciclo_basico_elegiveis($ranqueamento->ano);
        $ciclo_basico_nao_elegiveis = Utils::ciclo_basico_nao_elegiveis($ranqueamento->ano);

        return view('admin.ciclo_basico', [
            'ciclo_basico_elegiveis' => $ciclo_basico_elegiveis,
            'ciclo_basico_nao_elegiveis' => $ciclo_basico_nao_elegiveis,
        ]);
    }
}
