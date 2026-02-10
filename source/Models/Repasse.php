<?php
// source/Repasse.php

namespace Source\Models;

use PDO;
use Source\Core\Model;

class Repasse extends Model
{   
    /**
     * Busca todos os contas no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM repasse_25");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca um processo específico pelo seu ID.
     * @param int $id
     * @return Repasse|null
     */
   
    public function findById(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM repasse_25 WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();        
    }

    public function somaRepasseCByProcAcao(int $idProc, int $idAcao): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(custeio) AS repasse FROM repasse_25 WHERE acao_id = :idAcao AND proc_id = :idProc");
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $stmt->execute([
            'idProc' => $idProc,
            'idAcao' => $idAcao]);
        
        return (float) $stmt->fetchColumn();        
    }

    public function somaRepasseKByProcAcao(int $idProc, int $idAcao): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(capital) AS repasse FROM repasse_25 WHERE acao_id = :idAcao AND proc_id = :idProc");
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $stmt->execute([
            'idProc' => $idProc,
            'idAcao' => $idAcao]);
        
        return (float) $stmt->fetchColumn();        
    }

}