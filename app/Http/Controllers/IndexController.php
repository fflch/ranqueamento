<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Score;
use App\Models\Ranqueamento;
use Illuminate\Support\Facades\DB; 

class IndexController extends Controller
{
    public function index(){

        $ranqueamento = Ranqueamento::where('status',1)->first();

        $scores = DB::table('scores')
        ->join('ranqueamentos', 'scores.ranqueamento_id', '=', 'ranqueamentos.id')
        ->join('habs', 'scores.hab_id_eleita', '=', 'habs.id') 
        ->where('scores.user_id', auth()->user()->id) 
        ->select(
            'scores.hab_id_eleita', 
            'scores.posicao',
            'ranqueamentos.ano',  
            'ranqueamentos.tipo',  
            'habs.nomhab'          
            )
        ->get();

        return view('index',[
            'ranqueamento' => $ranqueamento,
            'scores' => $scores,
        ]);
    }
}
