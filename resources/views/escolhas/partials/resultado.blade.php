@if ($resultado)
  <div class="alert alert-info">
    @if ($resultado->prioridade_eleita && $resultado->hab)
      <p class="text-muted">
        Você foi <strong>contemplado</strong> no último ranqueamento
      </p>
      <hr>
      <p><strong>Sua nota:</strong> {{ number_format($resultado->nota, 2, ',') }}</p>
      @if ($notaCorte)
        <p><strong>Nota de corte:</strong> {{ number_format($notaCorte, 2, ',') }}</p>
      @endif
      <hr>
      <p><strong>Habilitação:</strong> {{ $resultado->hab->nomhab }}</p>
      <p><strong>Período:</strong> {{ $resultado->hab->perhab }}</p>
      <p><strong>Vagas totais:</strong> {{ $resultado->hab->vagas }}</p>
      <p><strong>Status:</strong> Contemplado(a)</p>
    @else
      <p class="text-muted">
        Você participou do ranqueamento, mas <strong>não foi contemplado(a)</strong> na sua escolha.
      </p>
      <hr>
      <p><strong>Sua nota:</strong> {{ number_format($resultado->nota, 2, ',') }}</p>
      @if ($resultado->hab && $notaCorte)
        <p><strong>Nota de corte:</strong> {{ number_format($notaCorte, 2, ',') }}</p>
      @endif
      @if ($resultado->hab)
        <hr>
        <p><strong>Habilitação escolhida:</strong> {{ $resultado->hab->nomhab }}</p>
        <p><strong>Período:</strong> {{ $resultado->hab->perhab }}</p>
        <p><strong>Vagas totais:</strong> {{ $resultado->hab->vagas }}</p>
      @endif
      <p><strong>Status:</strong> Não contemplado(a)</p>
    @endif

    {{-- tabela com todas as opções escolhidas --}}
    @if ($notasCortePorHabilitacao->isNotEmpty())
      <hr>
      <p><strong>Notas de corte das habilitações escolhidas:</strong></p>
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Opção</th>
            <th>Habilitação</th>
            <th>Período</th>
            <th>Vagas</th>
            <th>Nota de corte</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($notasCortePorHabilitacao as $item)
            <tr>
              <td>{{ $item['prioridade'] }}</td>
              <td>{{ $item['habilitacao'] }}</td>
              <td>{{ $item['periodo'] }}</td>
              <td>{{ $item['vagas'] }}</td>
              <td>{{ $item['nota_corte'] !== null ? number_format($item['nota_corte'], 2, ',') : '-' }}</td>
              <td>{{ $item['contemplado'] ? 'Contemplado(a)' : 'Não contemplado(a)' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

  </div>
@else
  <p>Resultado ficará disponível em breve</p>
@endif