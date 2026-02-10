<?php
// source/Local.php

namespace Source\Models;

use PDO;
use Source\Core\Model;

class Local extends Model
{
    /**
     * Busca todos os locais no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT id, sigla, nome_local FROM localexercicio ORDER BY id ASC");
        //$stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca um local específico pelo seu ID.
     * @param int $id
     * @return Local|null
     */
    public function findById(int $id): ?Local
    {
        $stmt = $this->pdo->prepare("SELECT * FROM localexercicio WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $localData = $stmt->fetch();
        return $localData ?: null;
    }   
}