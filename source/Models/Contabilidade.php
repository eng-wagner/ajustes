<?php
// source/Contabilidade.php

namespace Source\Models;

use PDO;
use Source\Core\Model;

class Contabilidade extends Model
{   
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM contabilidades");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca um usuário específico pelo seu ID.
     * @param int $id
     * @return Contabilidade|null
     */
    public function findById(int $id): ?Contabilidade
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contabilidades WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        
        return $stmt->fetch() ?: null;
    }
    
    public function save(array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idCont'])) {
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
        $stmt = $this->pdo->prepare(
            "INSERT INTO contabilidades (c_nome, c_telefone, c_email) 
             VALUES (:c_nome, :c_telefone, :c_email)"
        );

        return $stmt->execute([
            'c_nome' => $data['nome'],
            'c_telefone' => $data['telefone'],
            'c_email' => $data['email']            
        ]);
    }
    
    private function update(array $data): bool
    {
        $query = "UPDATE contabilidades SET c_nome = :c_nome, c_telefone = :c_telefone, c_email = :c_email WHERE id = :id";
        
        $params = [
            'c_nome' => $data['nome'],
            'c_telefone' => $data['telefone'],
            'c_email' => $data['email'],            
            'id' => $data['idCont']
        ];
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }    
}