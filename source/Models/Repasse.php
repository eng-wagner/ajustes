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
    public function somaRepasseByProc(int $idProc): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(custeio + capital) AS repasse FROM repasse_25 WHERE proc_id = :idProc");
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $stmt->execute(['idProc' => $idProc]);
        return (float) $stmt->fetchColumn();
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

    /**
     * Soma o repasse filtrando por Processo e Ação.
     * Decide qual coluna somar (custeio ou capital) baseada na string $categoria passada.
     */
    public function getSomaRepasse(int $idProc, int $idAcao, string $categoria): float
    {
        // 1. Define qual coluna do banco vamos somar
        // Normalizamos para minúsculas para evitar erros (Ex: "CUSTEIO" vira "custeio")
        $colunaParaSomar = '';

        // Verifica se a string contém "custeio" ou "capital"
        if ($categoria == 'C') { 
            $colunaParaSomar = 'custeio';
        } elseif ($categoria == 'K') {
            $colunaParaSomar = 'capital';
        } else {
            // Se a categoria do saldo não for nem Custeio nem Capital, não tem repasse
            return 0.00;
        }

        // 2. Monta a Query dinâmica
        // AVISO: Como definimos a variável $colunaParaSomar manualmente acima, não há risco de SQL Injection aqui.
       
        $stmt = $this->pdo->prepare("SELECT SUM($colunaParaSomar) as total 
                FROM repasse_25 
                WHERE proc_id = :idProc 
                AND acao_id = :idAcao");
        $stmt->execute([
            'idProc' => $idProc,
            'idAcao' => $idAcao]);        
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        return $result ? (float)$result->total : 0.00;
    }

}