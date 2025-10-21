<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB; // Essencial para a conexão com o Replicado
use App\Models\Hab;
use App\Models\User;
use App\Models\Score;
use Throwable; // Para capturar qualquer tipo de erro que possa acontecer

class SincronizarComReplicado extends Command
{
    /**
     * A "assinatura" do comando.
     * {type} é um argumento obrigatório que nos dirá o que sincronizar: 'users' ou 'habs'.
     */
    protected $signature = 'sync:replicado {type}';

    /**
     * A descrição do comando.
     */
    protected $description = 'Sincroniza dados do banco local com o Replicado. Argumentos: users, habs';

    /**
     * O método principal, que age como um "roteador", direcionando para a tarefa correta.
     */
    public function handle()
    {
        $type = $this->argument('type');

        // Primeiro, garantimos que a conexão com o Replicado está funcionando.
        try {
            DB::connection('replicado')->getPdo();
            $this->info("Conexão com o Banco de Dados Replicado estabelecida com sucesso.");
        } catch (Throwable $e) {
            $this->error("FALHA NA CONEXÃO com o Replicado: " . $e->getMessage());
            $this->error("Verifique suas credenciais no arquivo .env e a configuração do driver/rede.");
            return 1; // Termina o comando com erro.
        }

        // Baseado no argumento passado, chama o método correspondente.
        if ($type === 'users') {
            $this->sincronizarUsuarios();
        } elseif ($type === 'habs') {
            $this->sincronizarHabilitacoes();
        } else {
            $this->error("Tipo de sincronização inválido. Use 'users' ou 'habs'.");
            return 1;
        }

        $this->info("\nProcesso de sincronização finalizado.");
        return 0;
    }

    /**
     * Sub-etapa: Sincroniza os e-mails dos usuários.
     */
    private function sincronizarUsuarios()
    {
        $this->line('');
        $this->info('Iniciando sincronização de e-mails dos usuários...');

        // 1. Pega todos os usuários do nosso banco de dados local.
        $usersLocais = User::all();
        // 2. Cria uma barra de progresso com o total de usuários.
        $progressBar = $this->output->createProgressBar($usersLocais->count());

        // 3. Itera sobre cada usuário local.
        foreach ($usersLocais as $userLocal) {
            // 4. Para cada um, busca o e-mail oficial no Replicado.
            $dadosEmailReplicado = DB::connection('replicado')
                ->table('EMAILPESSOA') // Tabela que descobrimos
                ->where('codpes', $userLocal->codpes)
                ->where('stausp', 'S') // Filtro para pegar apenas o email @usp.br
                ->first();

            // 5. Se encontrou um e-mail, atualiza o registro local.
            if ($dadosEmailReplicado && !empty($dadosEmailReplicado->codema)) {
                $userLocal->update(['email' => strtolower($dadosEmailReplicado->codema)]); // Coluna que descobrimos
            }
            $progressBar->advance(); // Avança a barra de progresso
        }

        $progressBar->finish();
        $this->info("\n -> E-mails sincronizados com sucesso.");
    }

    /**
     * Sub-etapa: Sincroniza os detalhes das habilitações e dos scores.
     */
    private function sincronizarHabilitacoes()
    {
        $this->line('');
        $this->info('Iniciando sincronização de detalhes das habilitações...');

        $habilidadesLocais = Hab::all();
        $progressBar = $this->output->createProgressBar($habilidadesLocais->count());

        foreach ($habilidadesLocais as $habLocal) {
            // Busca os detalhes da habilitação no Replicado
            $dadosHabReplicado = DB::connection('replicado')
                ->table('HABILITACAOGR') // Tabela que descobrimos
                ->where('codhab', $habLocal->codhab)
                ->first();

            if ($dadosHabReplicado) {
                // Atualiza a tabela 'habs' com os dados do replicado.
                $habLocal->update([
                    'perhab' => $dadosHabReplicado->perhab ?? '',       // Coluna que descobrimos
                    'vagas'  => $dadosHabReplicado->numvaghab ?? 0,    // Coluna que descobrimos
                ]);

                // Atualiza a tabela 'scores' com o código do Jupiter (codine).
                Score::where('hab_id_eleita', $habLocal->id)->update([
                    'codhab_jupiterweb' => $dadosHabReplicado->codine ?? null, // Coluna que descobrimos
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\n -> Habilitações e scores sincronizados com sucesso.");
    }
}