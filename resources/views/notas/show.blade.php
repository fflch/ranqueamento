@extends('laravel-usp-theme::master')

@section('content')

<p><b>Nº USP: </b>{{$user->codpes}}</p>
<p><b>Nome: </b>{{$user->name}}</p>
<!-- media query -->
<link rel="stylesheet" href="{{ asset('css/style.css') }}"> 

<div class="row" style="margin:8px;">
    <table class="table table-striped" style="width:25%; margin-right:10px;">
    <tbody>
        <tr>
            <th>Primeiro Semestre</th>
        </tr>
        <tr>
            <th>Disciplinas</th>
            <th>Nota</th>
        </tr>
        @foreach($notas as $nota)
        <tr>
        <th scope="row">{{$nota[1]}}</th>
        <td>{{$nota[0]}} +</td>
        </tr>
        @endforeach
        <tr>
            <th>Total:</th>
            <td>{{$soma_notas}} / 4 = <b>{{$media_um}}</b></td>
        </tr>
    </tbody>
    </table>

    <table class="table table-striped" style="width:25%;">
    <tbody>
        <tr>
            <th>Segundo Semestre</th>
        </tr>
        <tr>
            <th>Disciplinas</th>
            <th>Nota</th>
        </tr>
            @foreach($notas_segundo as $nota_segundo)
            <tr>
            <th scope="row">{{$nota_segundo[1]}}</th>
            <td>{{$nota_segundo[0]}} +</td>
            </tr>
            @endforeach
        <tr>
            <th>Total:</th>
            <td>{{$soma_notas2}} * 2 = {{$soma_notas2 * 2}} / 4 = <b>{{$media_dois}}</b></td>
        </tr>
    </tbody>
    </table>
    <div class="col">
        <div class="card">
            <div class="card-header">
                <b>Somar</b>
            </div>
            <div class="card-body text-center">
                    1º SEMESTRE ({{$media_um}})<br/>
                    +<br/>
                    2º SEMESTRE ({{$media_dois}})<br/>
                    =<br/>
                    {{$media_um + $media_dois}}<br/>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card">
            <div class="card-header">
                <b>DIVIDIR O TOTAL POR 3</b>
            </div>
            <div class="card-body text-center">
                {{$media_um + $media_dois}} / 3 = <b>MÉDIA FINAL {{round($media_final, 3)}} </b>
            </div>
        </div>
    </div>
</div>
@endsection