<?php
// source/Banco.php

namespace Source\Models;

use PDO;
use Source\Core\Model;

class Banco extends Model
{   
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM banco");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function findById(int $id): ?Banco
    {
        $stmt = $this->pdo->prepare("SELECT * FROM banco WHERE id = :idConta");
        $stmt->execute(['idConta' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $bancoData = $stmt->fetch();
        return $bancoData ?: null;
    }

    /**
     * Busca um conta específico pelo seu ID.
     * @param int $id
     * @return Banco|null
     */
    public function findLYById(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT agencia, conta, cc_2024 AS cc_LY, pp_01_2024 AS pp_01_LY, pp_51_2024 AS pp_51_LY, spubl_2024 AS spubl_LY, bb_rf_cp_2024 AS bb_rf_cp_LY FROM banco WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    public function findCYById(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT agencia, conta, cc_2025 AS cc_CY, pp_01_2025 AS pp_01_CY, pp_51_2025 AS pp_51_CY, spubl_2025 AS spubl_CY, bb_rf_cp_2025 AS bb_rf_cp_CY FROM banco WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    public function findByProcId(int $id): array
    {        
        $stmt = $this->pdo->prepare("SELECT *,
            cc_2024 AS cc_LY, 
            cc_2025 AS cc_CY, 
            pp_01_2024 AS pp_01_LY, 
            pp_01_2025 AS pp_01_CY,
            pp_51_2024 AS pp_51_LY, 
            pp_51_2025 AS pp_51_CY, 
            spubl_2024 AS spubl_LY,
            spubl_2025 AS spubl_CY, 
            bb_rf_cp_2024 AS bb_rf_cp_LY,
            bb_rf_cp_2025 AS bb_rf_cp_CY FROM banco WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    public function findByInstId(int $idInst): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM banco WHERE instituicao_id = :id");
        $stmt->execute(['id' => $idInst]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();        
    }

    public function somaBancoLY(int $idProc): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(cc_2024) AS ccSI, SUM(pp_01_2024) AS pp01SI, SUM(pp_51_2024) AS pp51SI, SUM(spubl_2024) AS spublSI, SUM(bb_rf_cp_2024) AS bbrfSI FROM banco WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        if($saldo = $stmt->fetch(PDO::FETCH_OBJ)){
            return (float)$saldo->ccSI + (float)$saldo->pp01SI + (float)$saldo->pp51SI + (float)$saldo->spublSI + (float)$saldo->bbrfSI;
        }
        return 0.0;
    }

    public function somaBancoCY(int $idProc): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(cc_2025) AS ccSF, SUM(pp_01_2025) AS pp01SF, SUM(pp_51_2025) AS pp51SF, SUM(spubl_2025) AS spublSF, SUM(bb_rf_cp_2025) AS bbrfSF FROM banco WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        if ($saldo = $stmt->fetch(PDO::FETCH_OBJ)) {
            return (float)$saldo->ccSF + (float)$saldo->pp01SF + (float)$saldo->pp51SF + (float)$saldo->spublSF + (float)$saldo->bbrfSF;
        }
        return 0.0;
    }

    public function getSaldoFinal(int $idProc): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(cc_2025) AS ccSF, SUM(pp_01_2025) AS pp01SF, SUM(pp_51_2025) AS pp51SF, SUM(spubl_2025) AS spublSF, SUM(bb_rf_cp_2025) AS bbrfSF FROM banco WHERE proc_id = :idProc");        
        $stmt->execute(['idProc' => $idProc]);
        
        if ($saldo = $stmt->fetch(PDO::FETCH_OBJ)) {
            return (float)$saldo->ccSF + (float)$saldo->pp01SF + (float)$saldo->pp51SF + (float)$saldo->spublSF + (float)$saldo->bbrfSF;
        }
        return 0.0;
    }

    /**
     * Salva um processo (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function saveBanco(int $idProc, ?int $idInst = null, array $data = []): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idContaM'])) {
            return $this->update($idProc, $data);
        }

        // Se não, é uma criação (INSERT).
        return $this->create($idProc, $idInst, $data);
    }

    /**
     * Método privado para criar um novo item.
     * @param array $data
     * @return bool
     */
    private function create(int $idProc, int $idInst, array $data): bool
    {           
        $nomeBanco = "Banco do Brasil";
        $saldoFinal = 0;       

        $stmt = $this->pdo->prepare("INSERT INTO banco (instituicao_id, proc_id, banco, agencia, conta, cc_2024, cc_2025, pp_01_2024, pp_01_2025, pp_51_2024, pp_51_2025, spubl_2024, spubl_2025, bb_rf_cp_2024, bb_rf_cp_2025) 
        VALUES (:idInst, :idProc, :banco, :agencia, :conta, :ccLY, :ccCY, :pp01LY, :pp01CY, :pp51LY, :pp51CY, :spubLY, :spubCY, :bbrfLY, :bbrfCY)");
        return $stmt->execute([
            'idInst' => $idInst, 
            'idProc' => $idProc,
            'banco' => $nomeBanco, 
            'agencia' => $data['novaAgencia'], 
            'conta' => $data['novaConta'], 
            'ccLY' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['siCorrente']), 
            'ccCY' => $saldoFinal, 
            'pp01LY' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['siPoup01']),
            'pp01CY' => $saldoFinal, 
            'pp51LY' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['siPoup51']), 
            'pp51CY' => $saldoFinal, 
            'spubLY' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['siInvSPubl']),
            'spubCY' => $saldoFinal,
            'bbrfLY' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['siInvBbRf']), 
            'bbrfCY' => $saldoFinal
        ]);
    }

    /**
     * Método privado para atualizar um item existente.
     * @param array $data
     * @return bool
     */
    private function update(int $idProc, array $data): bool
    {             
        $stmt = $this->pdo->prepare("UPDATE banco SET cc_2025 = :cc, pp_01_2025 = :pp01, pp_51_2025 = :pp51, spubl_2025 = :spubl, bb_rf_cp_2025 = :bbrf WHERE id = :idConta AND proc_id = :idProc");
        return $stmt->execute([
            'cc' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['corrente']),
            'pp01' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['poup01']),
            'pp51' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['poup51']),
            'spubl' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['invSPubl']),
            'bbrf' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['invBbRf']),
            'idConta' => $data['idContaM'],
            'idProc' => $idProc
        ]);
    }
}