@extends('laravel-usp-theme::master')

@section('content')
  @can('elegivel')
    <div class="card">
      <div class="card-header">
        Meu ranqueamento
      </div>
      <div class="card-body">
        <h5 class="card-title"></h5>
        <p class="card-text">
          @include('escolhas.partials.info')

          @if($ranqueamento && $ranqueamento->tipo=='ingressantes')
            @include('escolhas.partials.declinio')
            <br>
            <a href="{{route('escolhas_form')}}" class="btn btn-primary">Editar opções de habilitações para ranqueamento</a>
            <br><br>
            @include('escolhas.partials.show')
          @endif

          @if($ranqueamento && $ranqueamento->tipo=='reranqueamento')
            Raphael vai fazer
          @endif

          <br>
          <div class="card">
      <div class="card-header">
        Classificações
      </div>
      <div class="card-body">
        @if($scores->isEmpty())
          <p>Não há ranqueamentos.</p>
        @else
          <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Ano</th>
                    <th>Habilitação Eleita</th>
                    <th>Posição</th>
                    <th>Tipo de Ranqueamento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scores as $score)
                <tr>
                  <td>{{ $score->ano ?? 'não informado' }}</td>
                  <td>{{ $score->nomhab ?? 'não informado' }}</td>
                  <td>{{ $score->posicao }}</td>
                  <td>{{ $score->tipo ?? 'não informado' }}</td>
                </tr>
                @endforeach
            </tbody>
          </table>
        @endif
      </div>
        </p>
        <br>
      </div>
    </div>
  @else
    @auth
      <div class="card">
        <div class="card-body">
          <p class="card-text">Você não está apto(a) a participar do ranqueamento atual ou não há ranqueamento em aberto</p>
        </div>
      </div>
    @else
      <div class="card">
        <div class="card-body">
          <p class="card-text">Sistema para ranqueamento de habilitações no curso de Letras</p>
          <a href="/login" class="btn btn-primary">Acessar sistema</a>
        </div>
      </div>
    @endauth
  @endcan('elegivel')
@endsection