<?php
// source/Documento.php

namespace Source\Models;

use PDO;
use Source\Core\Model;

class Documento extends Model
{   
    /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM tipo_documento");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca um usuário específico pelo seu ID.
     * @param int $id
     * @return Documento|null
     */
    public function findById(int $id): ?Documento
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tipo_documento WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $documentoData = $stmt->fetch();
        return $documentoData ?: null;
    }

    /**
     * Salva um usuário (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function save(array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idDoc'])) {
            return $this->update($data);
        }

        // Se não, é uma criação (INSERT).
        return $this->create($data);
    }

    /**
     * Método privado para criar um novo item.
     * @param array $data
     * @return bool
     */
    private function create(array $data): bool
    {       
        $checkTc = 0;
        $checkPdde = 0;

        if($data['checkTc'] == "1") {$checkTc = 1;}
        if($data['checkPdde'] == "1") {$checkPdde = 1;}

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

    /**
     * Método privado para atualizar um item existente.
     * @param array $data
     * @return bool
     */
    private function update(array $data): bool
    {       
        $checkTcU = 0;
        $checkPddeU = 0;

        if($data['checkTcU'] == "1") {$checkTcU = 1;}
        if($data['checkPddeU'] == "1") {$checkPddeU = 1;}
        
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