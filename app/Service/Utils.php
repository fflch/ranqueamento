<?php

namespace App\Service;

use Uspdev\Replicado\DB;
use App\Models\Declinio;
use App\Models\Ranqueamento;
use App\Models\Escolha;
use App\Models\Hab;

class Utils
{
    // AND YEAR(V.dtainivin) = 2024
    public static function ciclo_basico_elegiveis(int $ano){
        // codhab=102 : Ciclo Básico matutino
        // codhab=104 : Ciclo Básico noturno
        // Elegiveis: estão matriculados no semestre 2 no ano, ingressaram no ano e são ciclo básico
        // Conversão da data no sybase:
        // https://infocenter.sybase.com/help/index.jsp?topic=/com.sybase.infocenter.dc38151.1520/html/iqrefbb/Convert.htm
        $anosem = "{$ano}2";
        $query = "SELECT V.codpes, V.nompes, S.perhab, CONVERT( CHAR( 20 ), V.dtainivin, 103 ) AS dtainivin
        FROM VINCULOPESSOAUSP V
        INNER JOIN SITALUNOATIVOGR S ON (V.codpes = S.codpes) AND (V.codclg = S.codclg)
        WHERE V.tipvin = 'ALUNOGR'
            AND (V.codclg = 8)
            AND ((V.codhab=102 OR V.codhab=104) AND (S.codhab=102 OR S.codhab=104))
            AND (V.codcurgrd = 8051)
            AND YEAR(dtainivin) = {$ano}
            AND S.anosem = {$anosem}
            AND (S.staalu = 'M' OR S.staalu = 'A' OR S.staalu = 'R') 
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
        $anosem = "{$ano}2";
        $query = "SELECT V.codpes, V.nompes, S.perhab, S.staalu, CONVERT( CHAR( 20 ), V.dtainivin, 103 ) AS dtainivin
        FROM VINCULOPESSOAUSP V
        INNER JOIN SITALUNOATIVOGR S ON (V.codpes = S.codpes) AND (V.codclg = S.codclg)
        WHERE V.tipvin = 'ALUNOGR'
            AND (V.codclg = 8)
            AND ((V.codhab=102 OR V.codhab=104) AND (S.codhab=102 OR S.codhab=104))
            AND (V.codcurgrd = 8051)
            AND S.anosem = {$anosem}
            AND 
                (YEAR(dtainivin) <> {$ano} OR (S.staalu <> 'M' AND S.staalu <> 'A' AND S.staalu <> 'R'))
            ORDER BY V.nompes ASC
        ";
        return DB::fetchAll($query);    
    }

    public static function ciclo_basico_check(int $codpes, int $ano){
        $anosem = "{$ano}2";

        $query = "SELECT COUNT(*)
        FROM VINCULOPESSOAUSP V
        INNER JOIN SITALUNOATIVOGR S ON (V.codpes = S.codpes) AND (V.codclg = S.codclg)
        WHERE V.tipvin = 'ALUNOGR'
            AND (V.codclg = 8)
            AND ((V.codhab=102 OR V.codhab=104) AND (S.codhab=102 OR S.codhab=104))
            AND (V.codcurgrd = 8051)
            AND YEAR(dtainivin) = {$ano}
            AND S.anosem = {$anosem}
            AND (S.staalu = 'M' OR S.staalu = 'A' OR S.staalu = 'R') 
            AND V.codpes = $codpes
        ";
        $record = DB::fetch($query);
        return (bool)$record['computed'];
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

    public static function periodo(){
        $codpes = auth()->user()->codpes;

        $query = "SELECT V.codhab
        FROM VINCULOPESSOAUSP V
        WHERE V.tipvin = 'ALUNOGR'
            AND (V.codclg = 8)
            AND (V.codhab=102 OR V.codhab=104)
            AND (V.codcurgrd = 8051)
            AND V.codpes = $codpes
        ";
        $record = DB::fetch($query);
        if(!$record) return '';
        if($record['codhab'] == 102) return 'matutino';
        if($record['codhab'] == 104) return 'noturno';
    }

    public static function get_hab($codhab){
        $query = "SELECT H.codhab, H.nomhab, H.perhab
            FROM CURSOGR C, HABILITACAOGR H
            WHERE C.codclg = 8
            AND C.codcur = 8051
            AND H.nomhab NOT LIKE '%Portugu%'
            AND H.tiphab = 'I'
            AND C.codcur = H.codcur
            AND H.codhab = $codhab
            AND ( (H.dtaatvhab IS NOT NULL) AND (H.dtadtvhab IS NULL) )
            ORDER BY H.nomhab ASC
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

    public static function declinou($user_id = null, $ranqueamento = null){
        if(is_null($user_id)) $user_id = auth()->user()->id;
        if(is_null($ranqueamento))  $ranqueamento = Ranqueamento::where('status',1)->first();
       
        $declinio = Declinio::where('ranqueamento_id',$ranqueamento->id)
                            ->where('user_id',$user_id)->first();

        if($declinio) return true;
        return false;
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

    public static function query($codpes, $coddis){
        $query = "SELECT t4.nomdisexr, t3.notdisexr, t5.nomrazsoc, t2.codrqm
        FROM REQUERHISTESC t1
        INNER JOIN APROVEITEXTGR t2 ON t1.codrqm = t2.codrqm
        INNER JOIN HISTESCOLAREXTGR t3 ON
        (t2.coduspdisexr = t3.coduspdisexr AND t1.codpes = t3.codpes)
        INNER JOIN DISCIPEXTGR t4 ON t3.coduspdisexr= t4.coduspdisexr
        INNER JOIN ORGANIZACAO t5 ON t4.codorg = t5.codorg
        WHERE t1.codpes=$codpes
        AND t1.codpgm=t1.codpgm AND t1.coddis='$coddis'
        ";
        $q = DB::fetchAll($query);
        return $q;
    }

    public static function get_nota($codpes, $coddis){
        $query = "SELECT notfim, notfim2, coddis, rstfim, codpes
        FROM HISTESCOLARGR
        WHERE codpes = $codpes AND coddis = '$coddis'
        ";
        $resultado = DB::fetch($query);
        
        if($resultado != false){
            //nota do HISTESCOLARGR deve ser igual à nota da transf.externa            
            if($resultado['notfim'] && !$resultado['notfim2']){ //nota única da matéria no semestre
                return [$resultado['notfim'], $resultado['coddis']];
            }elseif($resultado['notfim2']){ //nota da prova de recuperação
                return [$resultado['notfim2'], $resultado['coddis']];
            }elseif($resultado['rstfim'] == 'D'){
                $codrqm = self::query($codpes, $coddis);
                if(count($codrqm) > 1){ //duas ou mais disciplinas externas para aproveitar uma na usp
                    $total = array_sum(array_map(fn($nota) => (float) $nota['notdisexr'], $codrqm));
                    $count = count($codrqm);
                    $media = $count > 0 ? $total / $count : 0; //média das disciplinas externas
                return [
                    $resultado['notfim2'] = $media, //média da nota das disciplinas que valerá por uma disc. USP
                    $resultado['coddis'],
                    ];
                }
                return [
                    $resultado['notfim'] = array_merge(...$codrqm)['notdisexr'],
                    $resultado['coddis'] . ' - ' . array_merge(...$codrqm)['nomdisexr']
                ];
            }elseif(!$resultado['notfim'] && !$resultado['notfim2']){ //não retornou nada ou não cursou a matéria
                return [$resultado['notfim2'] = 0, $resultado['coddis']];
            }
        }
    }
}