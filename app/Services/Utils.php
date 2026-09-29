<?php

namespace App\Services;

use Uspdev\Replicado\DB;
use App\Models\Declinio;
use App\Models\Ranqueamento;
use App\Models\Escolha;
use App\Models\Hab;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Actions\DispensaExterna;
use App\Actions\DispensaUSP;
use App\Actions\MaiorNota;

class Utils
{
    // AND YEAR(V.dtainivin) = 2024
    public static function ciclo_basico_elegiveis(int $ano){
        // codhab=102 : Ciclo Básico matutino
        // codhab=104 : Ciclo Básico noturno
        // Elegiveis: estão matriculados no semestre 2 no ano, ingressaram no ano e são ciclo básico
        // Conversão da data no sybase:
        // https://infocenter.sybase.com/help/index.jsp?topic=/com.sybase.infocenter.dc38151.1520/html/iqrefbb/Convert.htm
       
        $query = "SELECT V.codpes, V.nompes, CONVERT( CHAR( 20 ), V.dtainivin, 103 ) AS dtainivin, V.codhab,
        CASE V.codhab
		    WHEN 102 THEN 'matutino'
		    WHEN 104 THEN 'noturno'
	    END AS nomecodhab
        FROM VINCULOPESSOAUSP V
        INNER JOIN PROGRAMAGR P ON V.codpes = P.codpes
        WHERE V.tipvin = 'ALUNOGR'
            AND (V.codclg = 8)
            AND (V.codhab=102 OR V.codhab=104)
            AND (V.codcurgrd = 8051)
            AND YEAR(dtainivin) = {$ano}
            AND P.stapgm = 'A'
        ORDER BY V.nompes ASC
        ";
        return DB::fetchAll($query);
    }

    // AND YEAR(V.dtainivin) = 2024
    public static function ciclo_basico_nao_elegiveis(int $ano){
        // codhab=102 : Ciclo Básico matutino
        // codhab=104 : Ciclo Básico noturno
        // Elegiveis: estão matriculados no semestre 2 no ano, ingressaram no ano e são ciclo básico
        // Conversão da data no sybase:
        // https://infocenter.sybase.com/help/index.jsp?topic=/com.sybase.infocenter.dc38151.1520/html/iqrefbb/Convert.htm
        
        $query = "SELECT P.codpgm, V.tipvin, V.codpes, V.nompes, CONVERT(CHAR(20), V.dtainivin, 103) AS dtainivin, V.codhab,
        CASE V.codhab
            WHEN 102 THEN 'matutino'
            WHEN 104 THEN 'noturno'
        END AS nomecodhab
        FROM VINCULOPESSOAUSP V
        INNER JOIN PROGRAMAGR P ON V.codpes = P.codpes
        WHERE P.codpgm = (
                        SELECT MAX(P.codpgm)
                        FROM PROGRAMAGR P
                        WHERE P.codpes = V.codpes
                        )
            AND V.tipvin = 'ALUNOGR'
            AND (V.codclg = 8)
            AND (V.codhab=102 OR V.codhab=104)
            AND (V.codcurgrd = 8051)
            AND YEAR(dtainivin) = 2025 
            --AND YEAR(dtainivin) = {$ano}
            AND (P.stapgm <> 'A')
        ORDER BY V.nompes ASC
        ";


        return DB::fetchAll($query);
    }

    public static function ciclo_basico_check(int $codpes, int $ano){
        $anosem = "{$ano}2";

        $query = "SELECT COUNT(*)
                FROM VINCULOPESSOAUSP V
                    INNER JOIN PROGRAMAGR P 
                        ON V.codpes = P.codpes
                WHERE V.tipvin = 'ALUNOGR'
                    AND (V.codclg = 8)
                    AND (V.codcurgrd = 8051)
                    AND YEAR(dtainivin) = {$ano}
                    AND (V.codhab=102 OR V.codhab=104) 
                    AND P.stapgm = 'A'
                    AND V.codpes = $codpes        
        ";
        $record = DB::fetch($query);
        return (bool)$record['computed'];
    }

    public static function reranqueamento_check(int $codpes){
        // verificamos se é aluno(a) de graduação ativo
       /* $query = "SELECT codpgm FROM PROGRAMAGR WHERE codpes = {$codpes} AND stapgm = 'A'";
        $record = DB::fetch($query);
        if(!$record) return false;

        // verificamos se é aluno(a) de letras
        $query = "SELECT V.codpes FROM VINCULOPESSOAUSP V
                    WHERE V.tipvin = 'ALUNOGR'
                        AND V.codpes= {$codpes}
                        AND V.codclg = 8
                        AND V.codcurgrd = 8051";
        $record = DB::fetch($query);
        if(!$record) return false;
        
        // podem participar do reranqueamento os alunos:
        // 1. Cursaram no máximo oito semestres retroativos
        // 2. Não estão com o curso trancado 
        $query = "SELECT * FROM SITALUNOATIVOGR 
                    WHERE codpes = {$codpes}
                    AND staalu='M'
                    AND codclg=8
                    AND codcur=8051
                    AND codpgm = (
                                    SELECT codpgm
                                    FROM PROGRAMAGR
                                    WHERE codpes = {$codpes} AND stapgm = 'A'
                                )
                   ";
        $records = DB::fetchAll($query);
        if(count($records)<=9) return true;
        return false;*/
        




  // verificamos se é aluno(a) de graduação ativo
        $query = "SELECT codpgm FROM PROGRAMAGR WHERE codpes = {$codpes} AND stapgm = 'A'";
        $record = DB::fetch($query);
        if(!$record) return false;
        


        // verificamos se é aluno(a) de letras
        $query = "SELECT V.codpes FROM VINCULOPESSOAUSP V
                    WHERE V.tipvin = 'ALUNOGR'
                        AND V.codpes= {$codpes}
                        AND V.codclg = 8
                        AND V.codcurgrd = 8051";
        $record = DB::fetch($query);
        if(!$record) return false;
        
        // podem participar do reranqueamento os alunos:
        // 1. Cursaram no máximo oito semestres retroativos
        // 2. Não estão com o curso trancado


        $anoreranqueamento = 2026;

   $query = "SELECT COUNT(DISTINCT SUBSTRING(CONVERT(VARCHAR, H.codtur), 1, 5)) AS SEMESTRES
FROM HISTESCOLARGR H 
--INNER JOIN VINCULOPESSOAUSP V
	--ON V.codpes = H.codpes
WHERE H.codpes = $codpes
AND H.codpgm = (SELECT MAX(G.codpgm) FROM HISTESCOLARGR G WHERE G.codpes = H.codpes)
		--AND V.tipvin = 'ALUNOGR'
		AND H.codtur != '0' AND H.codtur NOT LIKE '%{$anoreranqueamento}%'
		AND H.stamtr = 'M'";


//$records = DB::fetchAll($query);
$record = DB::fetch($query);
return (!empty($record) && $record['SEMESTRES'] <= 8);




    return true;

    }

    public static function lista_habs(){
        $query = "SELECT H.codhab, H.nomhab, H.perhab
            FROM CURSOGR C, HABILITACAOGR H
            WHERE C.codclg = 8
            AND C.codcur = 8051
            AND H.nomhab NOT LIKE '%Portugu%'
            AND H.tiphab = 'I'
            AND C.codcur = H.codcur
            AND ( (H.dtaatvhab IS NOT NULL) AND (H.dtadtvhab IS NULL) )
            ORDER BY H.nomhab ASC
        ";
        return DB::fetchAll($query);
    }

    public static function periodo($codpes = null){
        if(is_null($codpes)) $codpes = auth()->user()->codpes;

        $query = "SELECT H.perhab FROM VINCULOPESSOAUSP V
                    INNER JOIN HABILITACAOGR H ON V.codhab=H.codhab
                    WHERE V.tipvin = 'ALUNOGR'
                        AND (V.codclg = 8)
                        AND (V.codcurgrd = 8051 AND H.codcur = 8051)
                        AND V.codpes = {$codpes}
                        AND ((H.dtaatvhab IS NOT NULL) AND (H.dtadtvhab IS NULL))";

        $record = DB::fetch($query);
        if($record) return $record['perhab'];
        return '';
    }

    public static function get_hab($codhab){
        $query = "SELECT H.codhab, H.nomhab, H.perhab
            FROM CURSOGR C, HABILITACAOGR H
            WHERE C.codclg = 8
            AND C.codcur = 8051
            AND H.tiphab = 'I'
            AND C.codcur = H.codcur
            AND H.codhab = $codhab
            AND ( (H.dtaatvhab IS NOT NULL) AND (H.dtadtvhab IS NULL) )
        ";
        return DB::fetch($query);
    }

    public static function limpa_string_de_codpes($string){
        $array = explode(',',$string);
        $array = array_map('trim', $array);
        $array = array_unique($array);
        sort($array);
        return implode(',',$array);
    }

    public static function escolha($prioridade){
        $ranqueamento = Ranqueamento::where('status',1)->first();

        $hab_id = Escolha::select('hab_id')
                            ->where('ranqueamento_id',$ranqueamento->id)
                            ->where('user_id',auth()->user()->id)
                            ->where('prioridade',$prioridade)
                            ->first();

        if(!$hab_id) return 'Não definido';
        $hab = Hab::find($hab_id->hab_id);
        return $hab->nomhab . ' - ' . $hab->perhab;
    }

    public static function get_codpgm(int $codpes) {
        $query = "SELECT codpgm
                    FROM PROGRAMAGR
                    WHERE codpes = $codpes AND stapgm = 'A'";
        $result = DB::fetch($query);
        if($result) return $result['codpgm'];
        return null;
    }

    public static function getNotas(int $codpes, array $disciplinas, $reranqueamento=false) {
        if(empty($disciplinas)) return collect([]);

        $disciplinas = collect($disciplinas)->map(function($disciplina) {
                return "'" . $disciplina . "'";
            })->implode(',', $disciplinas);

        $query = "SELECT coddis, rstfim, notfim, notfim2, codpgm
            FROM HISTESCOLARGR
            WHERE codpgm = (
                SELECT codpgm
                FROM PROGRAMAGR
                WHERE codpes = $codpes AND (stapgm = 'A' OR stapgm = 'R')
            ) AND codpes = $codpes AND stamtr = 'M' AND coddis IN($disciplinas)";

        if($reranqueamento) {
            $query .= " AND (rstfim='A' OR rstfim='D')";
        }

        $resultados = DB::fetchAll($query);

        [$disciplinas, $aproveitamentos] = collect($resultados)->partition(function($disciplina) {
            return $disciplina['rstfim'] <> 'D';
        });

        $notas = $disciplinas->map(function($disciplina) {
            return [
                'coddis' => $disciplina['coddis'],
                'nota'   => MaiorNota::handle($disciplina)
            ];

        });

        $codpgm = $aproveitamentos->select('codpgm')->first();

        $notasEquivalencia = $aproveitamentos->isEmpty() ? collect([]) :
            self::getAproveitamentos($codpes, $codpgm['codpgm']);

        return $notas->merge($notasEquivalencia);

    }

    private static function getAproveitamentos(int $codpes, int $codpgm) {

        $requerimento = "SELECT R2.coddis, R.tiprqm, R.codrqm FROM REQUERIMENTOGR R
                         INNER JOIN REQUERHISTESC R2 ON (R.codrqm = R2.codrqm)
                         WHERE R.codpes = $codpes AND R.codpgm = $codpgm AND R.rstfim = 'D'
                         AND R.starqm = 'C'
                         AND (R.tiprqm LIKE '%AEL%' OR R.tiprqm LIKE '%Dispensa%')";
                         //AND R.tiprqm IN ('Dispensa Externa','Dispensa USP','AEL extra-grade USP ')";
        $requerimentos = DB::fetchAll($requerimento);

        /*[$rqmExternos, $rqmInternos] = collect($requerimentos)->partition(function($requerimento) {
            return $requerimento['tiprqm'] === 'Dispensa Externa';
        });*/

        [$rqmExternos, $rqmInternos] = collect($requerimentos)->partition(function($requerimento) {
        return in_array($requerimento['tiprqm'], ['Dispensa Externa', 'AEL extra-grade EXT']);
        });

        $externo = $rqmExternos->isEmpty() ? collect([]) : DispensaExterna::handle($codpes, $codpgm, $rqmExternos);
        $interno = $rqmInternos->isEmpty() ? collect([]) : DispensaUSP::handle($codpes, $rqmInternos);

        return $externo->merge($interno);
    }

    public static function getMedia($notas) {
        $disciplinas = Escolha::disciplinas();

        [$primeiro, $segundo] = $notas->partition(function($nota) use($disciplinas) {
            return in_array($nota['coddis'], $disciplinas);
        });

        return ((($primeiro->sum('nota') / 4) + ($segundo->sum('nota') / 2)) / 3);
    }

    public static function declinou($user_id = null, $ranqueamento = null){
        if(is_null($user_id)) $user_id = auth()->user()->id;
        if(is_null($ranqueamento))  $ranqueamento = Ranqueamento::where('status',1)->first();
       
        $declinio = Declinio::where('ranqueamento_id',$ranqueamento->id)
                            ->where('user_id',$user_id)->first();

        if($declinio) return true;
        return false;
    }

    /**
     * No Re-ranqueamento, essa função prepara as displicinas que dever ser passadas
     * para o método getNotas.
     */
    public static function disciplinas_aprovadas_ou_dispensadas($codpes){
        $query = "SELECT D.coddis, D.nomdis, D.creaul, D.cretrb, H.rstfim
                    FROM HISTESCOLARGR H
                    INNER JOIN DISCIPLINAGR D ON H.coddis = D.coddis AND H.verdis = D.verdis
                    WHERE H.codpes = {$codpes}
                        AND (H.rstfim='A' OR H.rstfim='D')
                        AND codpgm = (
                                    SELECT codpgm
                                    FROM PROGRAMAGR
                                    WHERE codpes = {$codpes} AND (stapgm = 'A' OR stapgm = 'R')
                                )";
        return DB::fetchAll($query);
    }

    // combina os arrays disciplinas e notas para ficar mais fácil de usar
    // melhorar essas abordagem
    public static function combina_disciplinas_notas($disciplinas, $notas){
        $novo_array = [];

        if(empty($disciplinas)) return $novo_array;

        $novo_array = $notas->map(function($nota) use ($disciplinas){

            $disciplina = array_filter($disciplinas, function($disciplina) use ($nota){
                if($disciplina['coddis'] == $nota['coddis']){
                    return $disciplina;
                }
            });

            $disciplina = array_pop($disciplina);

            return [
                'coddis' => $nota['coddis'],
                'nota'   => $nota['nota'],
                'nomdis' => $disciplina['nomdis'],
                'creaul' => $disciplina['creaul'],
                'cretrb' => $disciplina['cretrb'],
                'rstfim' => $disciplina['rstfim'],
            ];
        });

        return $novo_array;
    }

    public static function obterMediaPonderada($disciplinas)
    {
        $creditos = 0;
        $soma = 0;
        foreach ($disciplinas as $disciplina) {
            $creditos += $disciplina['creaul'] + $disciplina['cretrb'];
            $nota = $disciplina['nota'];
            $soma += $nota * ($disciplina['creaul'] + $disciplina['cretrb']);
        }
        return empty($soma) ? 0 : round($soma / $creditos, 1);
    }
}
