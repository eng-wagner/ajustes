<?php
// source/Programa.php

namespace Source;

use PDO;
use Source\Database\Connect;

class Programa
{   
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        // Ao criar um objeto Programas, já pegamos a conexão com o banco.
        $this->pdo = Connect::getInstance();
    }

    /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM programaspdde");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca uma despesa específica pelo seu ID.
     * @param int $id
     * @return Programa|null
     */ 
    public function findById(int $id): ?Programa
    {
        $stmt = $this->pdo->prepare("SELECT * FROM programaspdde WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $despesaData = $stmt->fetch();
        return $despesaData ?: null;
    }    

    public function findByProgName(string $programa): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM programaspdde WHERE programa = :programa");
        $stmt->execute(['programa' => $programa]);
       
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);        
    }
}