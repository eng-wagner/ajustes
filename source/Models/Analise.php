<?php
// source/Models/Analise.php
namespace Source\Models;

use PDO;
use Source\Core\Model;

class Analise extends Model
{  
    public function findByProcessoId(int $idProc): ?Analise
    {
         $stmt = $this->pdo->prepare("SELECT * FROM analise_pdde_25 WHERE proc_id = :id");
         $stmt->execute(['id' => $idProc]);
         $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        
         return $stmt->fetch() ?: null;
    }
}