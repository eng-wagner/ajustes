<?php
// source/ItensCota.php

namespace Source\Models;

use PDO;
use Source\Core\Model;

class ItensCota extends Model
{   
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM gerar_cotas");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }
    
    public function findById(int $id): ?ItensCota
    {
        $stmt = $this->pdo->prepare("SELECT * FROM gerar_cotas WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);        
        return $stmt->fetch() ?: null;
    }

    public function findByCh(string $ch): ?ItensCota
    {
        $stmt = $this->pdo->prepare("SELECT * FROM gerar_cotas WHERE chName = :ch");
        $stmt->execute(['ch' => $ch]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetch() ?: null;
    }

    public function save(array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idDoc'])) {
            return $this->update($data);
        }
        return $this->create($data);
    }
    
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    private function create(array $data): bool
    {        
        $ativo = 1;
        $stmt = $this->pdo->prepare(
            "INSERT INTO gerar_cotas (documentos, chName, ativo) 
             VALUES (:documentos, :chName, :ativo)"
        );
        
        return $stmt->execute([
            'documentos' => $data['docNome'],
            'chName' => $data['docCh'],
            'ativo' => $ativo
        ]);
    }
    
    private function update(array $data): bool
    {               
        $stmt = $this->pdo->prepare("UPDATE gerar_cotas SET documentos = :documentos, chName = :chName WHERE id = :id");
        return $stmt->execute([
            'documentos' => $data['docNome'],
            'chName' => $data['docCh'],
            'id' => $data['idDoc']
        ]);
    }

    /**
     * Desativa um item do banco de dados.
     * @param int $id
     * @return bool
     */

    public function deactivate(int $id): bool
    {
        $ativo = 0;
        $stmt = $this->pdo->prepare("UPDATE gerar_cotas SET ativo = :ativo WHERE id = :id");  
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);      
        return $stmt->execute(['ativo' => $ativo, 'id' => $id]);
    }

    public function activate(int $id): bool
    {
        $ativo = 1;
        $stmt = $this->pdo->prepare("UPDATE gerar_cotas SET ativo = :ativo WHERE id = :id"); 
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);       
        return $stmt->execute(['ativo' => $ativo, 'id' => $id]);
    }   
}