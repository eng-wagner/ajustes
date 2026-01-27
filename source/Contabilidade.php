<?php
// source/Contabilidade.php

namespace Source;

use PDO;
use Source\Database\Connect;

class Contabilidade
{
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        // Ao criar um objeto Contabilidade, já pegamos a conexão com o banco.
        $this->pdo = Connect::getInstance();
    }

    /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT id, c_nome, c_telefone, c_email FROM contabilidades");
        //$stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
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

        $contData = $stmt->fetch();
        return $contData ?: null;
    }

    /**
     * Salva um usuário (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function save(array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idCont'])) {
            return $this->update($data);
        }

        // Se não, é uma criação (INSERT).
        return $this->create($data);
    }

    /**
     * Deleta um usuário do banco de dados.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Método privado para criar um novo usuário.
     * @param array $data
     * @return bool
     */
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

    /**
     * Método privado para atualizar um usuário existente.
     * @param array $data
     * @return bool
     */
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

    /**
     * Desativa um usuário do banco de dados.
     * @param int $id
     * @return bool
     */

    public function deactivate(int $id): bool
    {
        $ativo = 0;
        $stmt = $this->pdo->prepare("UPDATE usuarios SET ativo = :ativo WHERE id = :id");  
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);      
        return $stmt->execute(['ativo' => $ativo, 'id' => $id]);
    }

    public function activate(int $id): bool
    {
        $ativo = 1;
        $stmt = $this->pdo->prepare("UPDATE usuarios SET ativo = :ativo WHERE id = :id"); 
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);       
        return $stmt->execute(['ativo' => $ativo, 'id' => $id]);
    }

    public function renewPass(int $id): bool
    {
        $senha = md5('pmsbc123');
        $stmt = $this->pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id"); 
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);       
        return $stmt->execute(['senha' => $senha, 'id' => $id]);
    }
}