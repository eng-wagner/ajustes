<?php
// source/Documento.php

namespace Source\Models;

use PDO;
use Source\Core\Model;

class Documento extends Model
{       
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM tipo_documento");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }
   
    public function findById(int $id): ?Documento
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tipo_documento WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);        
        return $stmt->fetch() ?: null;
    }
    
    public function save(array $data): bool
    {        
        if (!empty($data['idDoc'])) {
            return $this->update($data);
        }
        return $this->create($data);
    }
   
    private function create(array $data): bool
    {       
        $checkTc = ($data['checkTc'] == "1") ? 1 : 0;;
        $checkPdde = ($data['checkPdde'] == "1") ? 1 : 0;

        $stmt = $this->pdo->prepare(
            "INSERT INTO tipo_documento (documento, tc, pdde) 
             VALUES (:documento, :tc, :pdde)"
        );
        
        return $stmt->execute([
            'documento' => $data['docNome'],
            'tc' => $checkTc,
            'pdde' => $checkPdde
        ]);
    }
    
    private function update(array $data): bool
    {       
        $checkTcU = ($data['checkTcU'] == "1") ? 1 : 0;;
        $checkPddeU = ($data['checkPddeU'] == "1") ? 1 : 0;;

        $query = "UPDATE tipo_documento SET documento = :documento, tc = :tc, pdde = :pdde WHERE id = :id";
        
        $params = [
            'documento' => $data['docNome'],
            'tc' => $checkTcU,
            'pdde' => $checkPddeU,
            'id' => $data['idDoc']
        ];
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }
}