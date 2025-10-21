<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ranqueamento;
use App\Models\User;
use App\Models\Hab;
use App\Models\Score;
use Throwable;

class ImportRanqueamentoCommand extends Command
{
    /**
     * A assinatura do comando, agora aceitando um argumento {filepath}.
     */
    protected $signature = 'import:ranqueamento {filepath}';

    /**
     * A descrição do comando.
     */
    protected $description = 'Importa os dados de um arquivo CSV de ranqueamento para o banco de dados.';

    /**
     * A lógica principal do comando.
     */
    public function handle()
    {
        // Pega o caminho do arquivo que foi passado como argumento
        $filePath = $this->argument('filepath');

        // Verifica se o arquivo realmente existe
        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado no caminho especificado: {$filePath}");
            return 1;
        }

        $this->info("Iniciando processamento do arquivo: {$filePath}");

        // Extrai o ano do nome do arquivo
        preg_match('/(\d{4})/', $filePath, $matches);
        $ano = $matches[1] ?? null;

        if (!$ano) {
            $this->error("Não foi possível extrair o ano do nome do arquivo. O arquivo deve conter o ano com 4 dígitos (ex: 2023).");
            return 1;
        }

        // Abre o arquivo para leitura
        $fileHandle = fopen($filePath, 'r');
        
        DB::beginTransaction();
        try {
            $isHeader = true;
            while (($row = fgetcsv($fileHandle, 2000, ';')) !== false) {
                if ($isHeader) { $isHeader = false; continue; }
                if (empty(implode('', $row))) { continue; }

            
                $complemento = trim($row[1] ?? '');
                $codpesOriginal = $row[2] ?? null;
                $nome = mb_convert_encoding(trim($row[3] ?? ''), 'UTF-8', 'auto');
                $media = str_replace(',', '.', ($row[5] ?? '0'));
                $classificacaoObtido = $row[7] ?? null;
                
                preg_match('/(\d+)/', $codpesOriginal, $codpesMatches);
                $codpes = $codpesMatches[1] ?? null;
                if (!$codpes || empty($nome)) { continue; }
                
                

                preg_match('/^(\d+)\s(.*)$/', $complemento, $habMatches);
                $codhab = $habMatches[1] ?? 0;
                $nomhab = mb_convert_encoding(trim($habMatches[2] ?? 'Habilitação não especificada'), 'UTF-8', 'auto');

                $ranqueamento = Ranqueamento::firstOrCreate(['ano' => $ano], ['tipo' => 'Ingresso Complementar', 'status' => 1]);
                $user = User::firstOrCreate(['codpes' => $codpes], ['name' => $nome, 'email' => $codpes]);
                $hab = Hab::firstOrCreate(['ranqueamento_id' => $ranqueamento->id, 'codhab' => $codhab], ['nomhab' => $nomhab,
                'perhab' => '', // Usamos uma string vazia em vez de nulo
                'permite_ambos_periodos' => 0,
                'vagas' => 0,]);

                Score::create([
                    'ranqueamento_id' => $ranqueamento->id,
                    'user_id' => $user->id,
                    'nota' => is_numeric($media) ? $media : null,
                    'posicao' => is_numeric($classificacaoObtido) ? $classificacaoObtido : null,
                    'codpes' => $user->codpes,
                    'hab_id_eleita' => $hab->id,
                ]);
            }

            DB::commit();
            $this->info("Arquivo {$filePath} processado e importado com sucesso!");

        } catch (Throwable $e) {
            DB::rollBack();
            $this->error("ERRO ao processar {$filePath}: " . $e->getMessage());
        } finally {
            fclose($fileHandle);
        }

        return 0;
    }
}
