<ul class="list-group">
@for($prioridade = 1; $prioridade <= $ranqueamento->max; $prioridade++)
    <li class="list-group-item">
        Opção {{ $prioridade }}: <i>{{ \App\Services\Utils::escolha($prioridade) }}</i>
    </li>
@endfor
</ul>