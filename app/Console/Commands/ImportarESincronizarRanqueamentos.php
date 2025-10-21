<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ranqueamento;
use App\Models\User;
use App\Models\Hab;
use App\Models\Score;
use Throwable;

class ImportarESincronizarRanqueamentos extends Command
{
    protected $signature = 'ranqueamento:processar-tudo';
    protected $description = 'Executa o processo completo de importação de TODOS os CSVs de ranqueamento e depois sincroniza os dados com o Replicado.';

    // Array para guardar as estatísticas do processo
    private $stats = [
        'users_created' => 0,
        'scores_created' => 0,
        'duplicates_found' => 0,
        'duplicates_deleted' => 0,
        'users_updated' => 0,
        'habs_updated' => 0,
        'scores_updated_jupiter_code' => 0,
    ];

    public function handle()
    {
        $this->line('================================================================');
        $this->info('== INICIANDO PROCESSO DE IMPORTAÇÃO E SINCRONIZAÇÃO DOS CSVs ==');
        $this->line('================================================================');

        // Solicita ao usuário o diretório dos arquivos CSV

        $diretorioInput = $this->ask(
            'Por favor, informe o caminho completo para o diretório onde os arquivos CSV estão localizados',
            storage_path('app/csv_ranqueamento') // Valor padrão
        );

        // Passa o diretório escolhido pelo usuário como um argumento para a Etapa 1.
        if (!$this->etapa1_ImportarCSVs($diretorioInput)) {
            return 1;
        }
        // ETAPA 1.5: VERIFICAÇÃO E EXCLUSÃO DE DUPLICATAS
        $this->etapa1_5_ProcessarDuplicatas();

        if (!$this->etapa2_SincronizarComReplicado()) {
            return 1;
        }

        $this->line('');
        $this->info('================================================================');
        $this->info('== PROCESSO FINALIZADO! ==');
        $this->line('================================================================');
        
        // Exibe o relatório final com as estatísticas coletadas
        $this->info("Relatório Final:");
        $this->line("- Scores criados a partir dos CSVs: " . $this->stats['scores_created']);
        $this->line("- Usuários novos criados: " . $this->stats['users_created']);
        $this->line("- Registros de score duplicados excluídos: " . $this->stats['duplicates_deleted']);
        $this->line("- E-mails de usuários atualizados via Replicado: " . $this->stats['users_updated']);
        $this->line("- Habilitações atualizadas via Replicado: " . $this->stats['habs_updated']);
        $this->line("- Scores atualizados com o código Jupiter: " . $this->stats['scores_updated_jupiter_code']);

        return 0;
    }

    private function etapa1_ImportarCSVs(string $diretorioInput): bool
    {
        $this->info("\n-- ETAPA 1: Importando dados dos arquivos CSV... --");
        $this->line("Vasculhando o diretório: {$diretorioInput}");

        // Verificação de segurança: o diretório existe?
        if (!is_dir($diretorioInput)) {
            $this->error("O diretório especificado não foi encontrado: {$diretorioInput}");
            return false;
        }

        // Monta o caminho de busca e encontra os arquivos.
        $caminhoBusca = rtrim($diretorioInput, '/') . '/*.csv';
        $csvFiles = glob($caminhoBusca);

        if (empty($csvFiles)) {
            $this->error('Nenhum arquivo CSV encontrado no diretório especificado.');
            return false;
        }

        foreach ($csvFiles as $filePath) {
            $this->line("\nProcessando arquivo: " . basename($filePath));
            
            preg_match('/(\d{4})/', $filePath, $matches);
            $ano = $matches[1] ?? null;
            if (!$ano) {
                $this->warn("AVISO: Não foi possível extrair o ano do nome do arquivo. Pulando...");
                continue;
            }

            $rowCount = count(file($filePath));
            $progressBar = $this->output->createProgressBar($rowCount - 1);
            $fileHandle = fopen($filePath, 'r');
            DB::beginTransaction();
            try {
                $isHeader = true;
                while (($row = fgetcsv($fileHandle, 2000, ';')) !== false) {
                    if ($isHeader) { $isHeader = false; continue; }
                    if (empty(implode('', $row))) { continue; }

                    $complemento = trim($row[1] ?? '');
                    preg_match('/^(\d+)\s(.*)$/', $complemento, $habMatches);
                    $codhab = $habMatches[1] ?? 0;
                    $nomhab = mb_convert_encoding(trim($habMatches[2] ?? 'Habilitação não especificada'), 'UTF-8', 'auto');

                    $codpesOriginal = $row[2] ?? null;
                    preg_match('/(\d+)/', $codpesOriginal, $codpesMatches);
                    $codpes = $codpesMatches[1] ?? null;
                    if (!$codpes) { continue; }

                    $nome = mb_convert_encoding(trim($row[3] ?? ''), 'UTF-8', 'auto');
                    $media = str_replace(',', '.', ($row[5] ?? '0'));
                    $classificacaoObtido = $row[7] ?? null;

                    $ranqueamento = Ranqueamento::firstOrCreate(['ano' => $ano], ['tipo' => 'Ingresso Complementar', 'status' => 1]);
                    $user = User::firstOrCreate(['codpes' => $codpes], ['name' => $nome, 'email' => $codpes . '@email.import']);
                    if($user->wasRecentlyCreated) {
                        $this->stats['users_created']++;
                    }
                    $hab = Hab::firstOrCreate(['ranqueamento_id' => $ranqueamento->id, 'codhab' => $codhab], ['nomhab' => $nomhab]);
                    
                    Score::create([
                        'ranqueamento_id' => $ranqueamento->id, 'user_id' => $user->id,
                        'nota' => is_numeric($media) ? $media : null,
                        'posicao' => is_numeric($classificacaoObtido) ? $classificacaoObtido : null,
                        'codpes' => $user->codpes, 'hab_id_eleita' => $hab->id,
                    ]);
                    $this->stats['scores_created']++;

                    $progressBar->advance();
                }
                DB::commit();
                $progressBar->finish();
                $this->info(" -> Importado com sucesso.");
            } catch (Throwable $e) {
                DB::rollBack();
                $this->error("\nERRO ao processar {$filePath}: " . $e->getMessage());
                return false;
            } finally {
                fclose($fileHandle);
            }
        }
        return true;
    }

    /**
     * NOVA ETAPA: Encontra e oferece a opção de excluir duplicatas.
     */
    private function etapa1_5_ProcessarDuplicatas()
    {
        $this->line('');
        $this->info('-- ETAPA 1.5: Verificando a integridade dos dados (duplicatas)... --');

        $queryDuplicatas = "
            SELECT user_id, ranqueamento_id, hab_id_eleita, COUNT(*) as total
            FROM scores
            GROUP BY user_id, ranqueamento_id, hab_id_eleita
            HAVING total > 1
        ";
        $duplicatas = DB::select($queryDuplicatas);

        $totalLinhasParaExcluir = 0;
        foreach ($duplicatas as $duplicata) {
            $totalLinhasParaExcluir += $duplicata->total - 1;
        }
        $this->stats['duplicates_found'] = $totalLinhasParaExcluir;

        if ($totalLinhasParaExcluir > 0) {
            $this->warn("\nForam encontrados {$totalLinhasParaExcluir} registros duplicados.");
            
            // Pergunta ao usuário se ele deseja excluir
            if ($this->confirm('Deseja excluir esses registros duplicados, mantendo apenas uma cópia de cada?', true)) {
                $queryDelete = "
                    DELETE t1 FROM scores t1
                    INNER JOIN scores t2 
                    WHERE 
                        t1.id > t2.id AND
                        t1.user_id = t2.user_id AND
                        t1.ranqueamento_id = t2.ranqueamento_id AND
                        t1.hab_id_eleita = t2.hab_id_eleita;
                ";
                $linhasExcluidas = DB::delete($queryDelete);
                $this->stats['duplicates_deleted'] = $linhasExcluidas;
                $this->info("{$linhasExcluidas} registros duplicados foram excluídos com sucesso.");
            } else {
                $this->info("Nenhum registro duplicado foi excluído.");
            }
        } else {
            $this->info("Nenhum registro duplicado foi encontrado. A base está íntegra.");
        }
    }

    private function etapa2_SincronizarComReplicado(): bool
    {
        $this->line('');
        $this->info('-- ETAPA 2: Sincronizando dados com o Banco de Dados Replicado da... --');
        try {
            DB::connection('replicado')->getPdo();
            $this->line('Conexão com o Replicado estabelecida com sucesso.');
        } catch (Throwable $e) {
            $this->error("FALHA NA CONEXÃO com o Replicado: " . $e->getMessage());
            return false;
        }

        $this->sincronizarUsuarios();
        $this->sincronizarHabilitacoes();

        return true;
    }

    private function sincronizarUsuarios()
    {
        $this->line("\nSincronizando e-mails dos usuários...");
        $usersLocais = User::all();
        $progressBar = $this->output->createProgressBar($usersLocais->count());

        foreach ($usersLocais as $userLocal) {
            $dadosEmailReplicado = DB::connection('replicado')
                ->table('EMAILPESSOA')->where('codpes', $userLocal->codpes)->where('stausp', 'S')->first();
            if ($dadosEmailReplicado && !empty($dadosEmailReplicado->codema)) {
                if ($userLocal->email != strtolower($dadosEmailReplicado->codema)) {
                    $userLocal->update(['email' => strtolower($dadosEmailReplicado->codema)]);
                    $this->stats['users_updated']++;
                }
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->info(" -> E-mails sincronizados.");
    }
    
    private function sincronizarHabilitacoes()
    {
        $this->line("\nSincronizando detalhes das habilitações...");
        $habilidadesLocais = Hab::all();
        $progressBar = $this->output->createProgressBar($habilidadesLocais->count());

        foreach ($habilidadesLocais as $habLocal) {
            $dadosHabReplicado = DB::connection('replicado')
                ->table('HABILITACAOGR')->where('codhab', $habLocal->codhab)->first();
            if ($dadosHabReplicado) {
                $habLocal->update([
                    'perhab' => $dadosHabReplicado->perhab ?? '',
                    'vagas'  => $dadosHabReplicado->numvaghab ?? 0,
                ]);
                $this->stats['habs_updated']++; // Conta como uma habilitação atualizada

                $updatedScoresCount = Score::where('hab_id_eleita', $habLocal->id)->update([
                    'codhab_jupiterweb' => $dadosHabReplicado->codine ?? null,
                ]);
                $this->stats['scores_updated_jupiter_code'] += $updatedScoresCount;
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->info(" -> Habilitações sincronizadas.");
    }
}
