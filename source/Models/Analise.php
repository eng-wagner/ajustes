<?php
// source/Models/Analise.php
namespace Source\Models;

use PDO;
use Source\Core\Model;
use DateTime;
use DateTimeZone;

class Analise extends Model
{  
    public function findByProcessoId(int $idProc): ?Analise
    {
         $stmt = $this->pdo->prepare("SELECT * FROM analise_pdde_25 WHERE proc_id = :id");
         $stmt->execute(['id' => $idProc]);
         $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        
         return $stmt->fetch() ?: null;
    }

    public function updateAnalise(int $idStatus, int $idUser, int $idProc): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $hoje = new DateTime('now', $timezone);
        $hoje = $hoje->format('Y-m-d');

        $stmt = $this->pdo->prepare("UPDATE analise_pdde_25 SET status_id = :idStatus, usuario_fin_id = :idUser, data_analise_fin = :dataAf WHERE proc_id = :idProc");    
        return $stmt->execute([
            'idStatus' => $idStatus,
            'idUser' => $idUser,
            'dataAf' => $hoje,
            'idProc' => $idProc
        ]);
    }
}