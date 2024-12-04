@extends('laravel-usp-theme::master')

@section('content')

<table class="table table-bordered">
  <thead>
    <tr>
      <th scope="col">Nome</th>
      <th scope="col">Número USP</th>
      <th scope="col">Declinou do português?</th>
      @for($prioridade = 1; $prioridade <=7; $prioridade++)
        <th>
            Opção {{ $prioridade }}
        </th>
      @endfor
    </tr>
  </thead>
  <tbody>
  @can("admin")
    <a class="btn btn-success" href="/excel" style="margin-bottom:5px;">Exportar Para Excel</a>
  @endcan
    @foreach($grouped as $group)
        @php 
            $nome = '-';
            $codpes = '-';
            $declinou = 'não';
            $first = $group->first(); 
            if($first) {
                $user = \App\Models\User::find($first->user_id);
                $nome = $user->name;
                $codpes = $user->codpes;
                if(\App\Service\Utils::declinou($first->user_id, $ranqueamento)) $declinou = 'sim';
            }
        
        @endphp
        <tr>
        <td><a href="notas/{{$codpes}}">{{ $nome }}</a></td>
        <td>{{ $codpes }}</td>
        <td>{{ $declinou }}</td>
        @for($prioridade = 1; $prioridade <=7; $prioridade++)
            <td>
                @php 
                    $escolha = $group->where('prioridade', $prioridade)->first();
                    $nomhab = '-';
                    if($escolha) {
                        $hab = \App\Models\Hab::find($escolha->hab_id);
                        $nomhab = str_replace('Bacharelado - Habilitação:','', $hab->nomhab) . ' - ' . $hab->perhab;
                    }
                @endphp
                {{ $nomhab }}
            </td>
        @endfor
        </tr>
    @endforeach

  </tbody>
</table





@endsection