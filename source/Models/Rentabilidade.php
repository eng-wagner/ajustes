<?php
// source/Banco.php

namespace Source\Models;

use PDO;
use Source\Core\Model;
use DateTimeZone;
use DateTime;

$timezone = new DateTimeZone("America/Sao_Paulo");

class Rentabilidade extends Model
{   
    // public function all(): array
    // {
    //     $stmt = $this->pdo->query("SELECT * FROM banco");        
    //     return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    // }

    public function findById(int $id): ?Rentabilidade
    {
        $stmt = $this->pdo->prepare("SELECT * FROM rendimentos_aplfin_2025 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $rentData = $stmt->fetch();
        return $rentData ?: null;
    }

    // /**
    //  * Busca um conta específico pelo seu ID.
    //  * @param int $id
    //  * @return Banco|null
    //  */
    // public function findLYById(int $id): array
    // {
    //     $stmt = $this->pdo->prepare("SELECT agencia, conta, cc_2024 AS cc_LY, pp_01_2024 AS pp_01_LY, pp_51_2024 AS pp_51_LY, spubl_2024 AS spubl_LY, bb_rf_cp_2024 AS bb_rf_cp_LY FROM banco WHERE proc_id = :id");
    //     $stmt->execute(['id' => $id]);
    //     $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

    //     return $stmt->fetchAll();
    // }

    // public function findCYById(int $id): array
    // {
    //     $stmt = $this->pdo->prepare("SELECT agencia, conta, cc_2025 AS cc_CY, pp_01_2025 AS pp_01_CY, pp_51_2025 AS pp_51_CY, spubl_2025 AS spubl_CY, bb_rf_cp_2025 AS bb_rf_cp_CY FROM banco WHERE proc_id = :id");
    //     $stmt->execute(['id' => $id]);
    //     $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

    //     return $stmt->fetchAll();
    // }

    public function findByProcId(int $id): array
    {        
        $stmt = $this->pdo->prepare("SELECT * FROM rendimentos_aplfin_2025 WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    // public function findByInstId(int $idInst): array
    // {
    //     $stmt = $this->pdo->prepare("SELECT * FROM banco WHERE instituicao_id = :id");
    //     $stmt->execute(['id' => $idInst]);
    //     $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

    //     return $stmt->fetchAll();        
    // }

    // public function somaBancoLY(int $idProc): ?Banco
    // {
    //     $stmt = $this->pdo->prepare("SELECT SUM(cc_2024) AS ccSI, SUM(pp_01_2024) AS pp01SI, SUM(pp_51_2024) AS pp51SI, SUM(spubl_2024) AS spublSI, SUM(bb_rf_cp_2024) AS bbrfSI FROM banco WHERE proc_id = :idProc");
    //     $stmt->execute(['idProc' => $idProc]);
    //     $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

    //     $bancoData = $stmt->fetch();
    //     return $bancoData ?: null;
    // }

    public function somaRendimentosCY(int $idProc): ?float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(jan) AS tJan, SUM(fev) AS tFev, SUM(mar) AS tMar, SUM(abr) AS tAbr, SUM(mai) AS tMai, SUM(jun) AS tJun, SUM(jul) AS tJul, SUM(ago) AS tAgo, SUM(setb) AS tSet, SUM(outb) AS tOut, SUM(nov) AS tNov, SUM(dez) AS tDez FROM rendimentos_aplfin_2025 WHERE proc_id = :idProc");        
        $stmt->execute(['idProc' => $idProc]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? (float) array_sum($resultado) : 0.0;
    }

    public function saveRentabilidade(int $idProc, int $idUser, array $data): bool
    {        
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now', $timezone);
        $agora = $agora->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            "INSERT INTO rendimentos_aplfin_2025 (conta_id, proc_id, variacao, jan, fev, mar, abr, mai, jun, jul, ago, setb, outb, nov, dez, data_hora, user_id) 
             VALUES (:conta_id, :proc_id, :variacao, :jan, :fev, :mar, :abr, :mai, :jun, :jul, :ago, :setb, :outb, :nov, :dez, :data_hora, :idUser)"
        );
        
        return $stmt->execute([
            'conta_id' => $data['agConta'],
            'proc_id' => $idProc,
            'variacao' => $data['variacao'],
            'jan' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rJan']),
            'fev' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rFev']),
            'mar' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rMar']),
            'abr' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rAbr']),
            'mai' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rMai']),
            'jun' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rJun']),
            'jul' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rJul']),
            'ago' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rAgo']),
            'setb' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rSet']),
            'outb' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rOut']),
            'nov' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rNov']),            
            'dez' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rDez']),
            'data_hora' => $agora,      
            'idUser' => $idUser
        ]);
    }



    public function updateRentabilidade(int $idUser, array $data): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now', $timezone);
        $agora = $agora->format('Y-m-d H:i:s');

        $rJanSQL = str_replace("R$ ", "", $data['rJan']);
        $rJanSQL = str_replace(".", "", $rJanSQL);
        $rJanSQL = str_replace(",", ".", $rJanSQL);

        $rFevSQL = str_replace("R$ ", "", $data['rFev']);
        $rFevSQL = str_replace(".", "", $rFevSQL);
        $rFevSQL = str_replace(",", ".", $rFevSQL);

        $rMarSQL = str_replace("R$ ", "", $data['rMar']);
        $rMarSQL = str_replace(".", "", $rMarSQL);
        $rMarSQL = str_replace(",", ".", $rMarSQL);

        $rAbrSQL = str_replace("R$ ", "", $data['rAbr']);
        $rAbrSQL = str_replace(".", "", $rAbrSQL);
        $rAbrSQL = str_replace(",", ".", $rAbrSQL);

        $rMaiSQL = str_replace("R$ ", "", $data['rMai']);
        $rMaiSQL = str_replace(".", "", $rMaiSQL);
        $rMaiSQL = str_replace(",", ".", $rMaiSQL);

        $rJunSQL = str_replace("R$ ", "", $data['rJun']);
        $rJunSQL = str_replace(".", "", $rJunSQL);
        $rJunSQL = str_replace(",", ".", $rJunSQL);

        $rJulSQL = str_replace("R$ ", "", $data['rJul']);
        $rJulSQL = str_replace(".", "", $rJulSQL);
        $rJulSQL = str_replace(",", ".", $rJulSQL);

        $rAgoSQL = str_replace("R$ ", "", $data['rAgo']);
        $rAgoSQL = str_replace(".", "", $rAgoSQL);
        $rAgoSQL = str_replace(",", ".", $rAgoSQL);

        $rSetSQL = str_replace("R$ ", "", $data['rSet']);
        $rSetSQL = str_replace(".", "", $rSetSQL);
        $rSetSQL = str_replace(",", ".", $rSetSQL);

        $rOutSQL = str_replace("R$ ", "", $data['rOut']);
        $rOutSQL = str_replace(".", "", $rOutSQL);
        $rOutSQL = str_replace(",", ".", $rOutSQL);

        $rNovSQL = str_replace("R$ ", "", $data['rNov']);
        $rNovSQL = str_replace(".", "", $rNovSQL);
        $rNovSQL = str_replace(",", ".", $rNovSQL);

        $rDezSQL = str_replace("R$ ", "", $data['rDez']);
        $rDezSQL = str_replace(".", "", $rDezSQL);
        $rDezSQL = str_replace(",", ".", $rDezSQL);

        $stmt = $this->pdo->prepare("UPDATE rendimentos_aplfin_2025 SET 
        variacao = :variacao, 
        jan = :jan, 
        fev = :fev, 
        mar = :mar, 
        abr = :abr, 
        mai = :mai, 
        jun = :jun, 
        jul = :jul, 
        ago = :ago, 
        setb = :setb, 
        outb = :outb, 
        nov = :nov, 
        dez = :dez, 
        data_hora = :data_hora, 
        user_id = :idUser 
        WHERE id = :id");
        return $stmt->execute([
            'variacao' => $data['variacao'],
            'jan' => $rJanSQL,
            'fev' => $rFevSQL,
            'mar' => $rMarSQL,
            'abr' => $rAbrSQL,
            'mai' => $rMaiSQL,
            'jun' => $rJunSQL,
            'jul' => $rJulSQL,
            'ago' => $rAgoSQL,
            'setb' => $rSetSQL,
            'outb' => $rOutSQL,
            'nov' => $rNovSQL,
            'dez' => $rDezSQL,
            'data_hora' => $agora,
            'idUser' => $idUser,
            'id' => $data['idRentM']
        ]);        
    }

    // /**
    //  * Salva um processo (cria um novo ou atualiza um existente).
    //  * @param array $data (dados vindos do formulário, ex: $_POST)
    //  * @return bool
    //  */
    // public function save(array $data): bool
    // {
    //     // Se o ID existir nos dados, é uma atualização (UPDATE).
    //     if (!empty($data['idProc'])) {
    //         return $this->update($data);
    //     }

    //     // Se não, é uma criação (INSERT).
    //     return $this->create($data);
    // }

    // /**
    //  * Método privado para criar um novo item.
    //  * @param array $data
    //  * @return bool
    //  */
    // private function create(array $data): bool
    // {           
    //     $stmt = $this->pdo->prepare(
    //         "INSERT INTO banco (orgao, numero, ano, digito, assunto, tipo, detalhamento, instituicao_id) 
    //          VALUES (:orgao, :numero, :ano, :digito, :assunto, :tipo, :detalhamento, :instituicao_id)"
    //     );
        
    //     return $stmt->execute([
    //         'orgao' => $data['docNome'],
    //         //'numero' => $checkTc,
    //         //'ano' => $checkPdde
    //     ]);
    // }

    // /**
    //  * Método privado para atualizar um item existente.
    //  * @param array $data
    //  * @return bool
    //  */
    // private function update(array $data): bool
    // {             
    //     $query = "UPDATE banco SET orgao = :orgao, numero = :numero, ano = :ano, digito = :digito, assunto = :assunto, detalhamento = :detalhamento, instituicao_id = :instituicao_id WHERE id = :id";
        
    //     $params = [
    //         'orgao' => $data['docNome'],
    //         //'numero' => $checkTcU,
    //         //'ano' => $checkPddeU,
    //         'id' => $data['idDoc']
    //     ];
        
    //     $stmt = $this->pdo->prepare($query);
    //     return $stmt->execute($params);
    // }
}