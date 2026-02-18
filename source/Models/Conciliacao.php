<?php
// source/Conciliacao.php

namespace Source\Models;

use PDO;
use Source\Core\Model;
use DateTime;
use DateTimeZone;

class Conciliacao extends Model
{
    public function listarOcorrencias(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM tipo_ocorrencia");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function getRelatorio(int $idProc): array
    {
        $stmt = $this->pdo->prepare("SELECT occ_id, descricao, dataOcc, valorOcc FROM conciliacao WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        $ocorrencias = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Prepara as gavetas para organizar os dados
        $resultado = [
            'transito' => 0.0,
            'debitos' => [],
            'totalDebito' => 0.0,
            'creditos' => [],
            'totalCredito' => 0.0
        ];

        // Varre os dados e guarda nas gavetas certas
        foreach ($ocorrencias as $occ) {
            $valor = (float) $occ->valorOcc;
            
            if ($occ->occ_id == 10) {
                $resultado['transito'] += $valor;
            } elseif ($occ->occ_id <= 7) {
                $resultado['debitos'][] = $occ;
                $resultado['totalDebito'] += $valor;
            } elseif (in_array($occ->occ_id, [8, 9, 11, 12])) {
                $resultado['creditos'][] = $occ;
                $resultado['totalCredito'] += $valor;
            }
        }

        return $resultado;
    }

    public function getSaldoConciliacao(int $idProc): float
    {
        $stmt = $this->pdo->prepare("SELECT t.natureza, c.valorOcc FROM conciliacao25 c JOIN tipo_ocorrencia t ON c.occ_id = t.id WHERE proc_id = :idProc");                                     
        $stmt->execute(['idProc' => $idProc]);
        $sdConc = 0.0;

        while ($conc = $stmt->fetch(PDO::FETCH_OBJ)) {
            $valor = (float) $conc->valorOcc;
            if ($conc->natureza == "C") {
                $valor = -$valor;
            }
            $sdConc += $valor;
        }
        return $sdConc;
    }

    public function getOcorrencias(int $idProc): array
    {
        $stmt = $this->pdo->prepare("SELECT c.id, t.ocorrencia, t.natureza, c.descricao, c.dataOcc, c.valorOcc FROM conciliacao25 c JOIN tipo_ocorrencia t ON c.occ_id = t.id WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        
        // Vamos criar gavetas para organizar tudo em uma única viagem ao banco
        $dados = [
            'debitos' => [],
            'creditos' => [],
            'totalD' => 0.0,
            'totalC' => 0.0
        ];

        while ($occ = $stmt->fetch(PDO::FETCH_OBJ)) {
            // Formata a data direto aqui, para não poluir o HTML
            $data = new DateTime($occ->dataOcc);
            $occ->dataOccFormatada = $data->format("d/m/Y");

            if ($occ->natureza == 'D') {
                $dados['debitos'][] = $occ;
                $dados['totalD'] += $occ->valorOcc;
            } elseif ($occ->natureza == 'C') {
                $dados['creditos'][] = $occ;
                $dados['totalC'] += $occ->valorOcc;
            }
        }
        return $dados;
    }

    public function deleteOcc(int $idOcc): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM conciliacao25 WHERE id = :idOcc");                   
        return $stmt->execute(['idOcc' => $idOcc]);        
    }

    public function saveOcorrencia(?int $idProc, int $idUser, array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idOccM'])) {
            return $this->update($idUser, $data);
        }

        // Se não, é uma criação (INSERT).
        return $this->create($idProc, $idUser, $data);
    }

    /**
     * Método privado para criar um novo item.
     * @param array $data
     * @return bool
     */
    private function create(int $idProc, int $idUser, array $data): bool
    {           
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $hora = new DateTime('now', $timezone);
        $hora = $hora->format('Y-m-d H:i:s');        

        $dataOcc = new DateTime($data['dataOcc'], $timezone);
        $dataOcc = $dataOcc->format("Y-m-d");
       
        $stmt = $this->pdo->prepare("INSERT INTO conciliacao25 (proc_id, occ_id, descricao, dataOcc, valorOcc, user_id, data_hora) 
            VALUES (:idProc, :ocorrencia, :descricao, :dataOcc, :valor, :idUser, :hora)");
        return $stmt->execute([
            'idProc' => $idProc, 
            'ocorrencia' => $data['ocorrencia'],
            'descricao' => $data['descricao'], 
            'dataOcc' => $dataOcc, 
            'valor' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['valorOcc']), 
            'idUser' => $idUser,
            'hora' => $hora
        ]);
    }

    /**
     * Método privado para atualizar um item existente.
     * @param array $data
     * @return bool
     */
    private function update(int $idUser, array $data): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $hora = new DateTime('now', $timezone);
        $hora = $hora->format('Y-m-d H:i:s');        

        $dataOcc = new DateTime($data['dataOcc'], $timezone);
        $dataOcc = $dataOcc->format("Y-m-d");

        $stmt = $this->pdo->prepare("UPDATE conciliacao25 
            SET occ_id = :ocorrencia, 
            descricao = :descricao, 
            dataOcc = :dataOcc, 
            valorOcc = :valor, 
            user_id = :idUser,
            data_hora = :hora WHERE id = :idOcc");
        return $stmt->execute([
            'ocorrencia' => $data['ocorrencia'],
            'descricao' => $data['descricao'], 
            'dataOcc' => $dataOcc, 
            'valor' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['valorOcc']), 
            'idUser' => $idUser,
            'hora' => $hora,
            'idOcc' => $data['idOccM']
        ]);
    }
}