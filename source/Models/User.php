<?php
// source/User.php

namespace Source\Models;

use PDO;
use Source\Core\Model;

class User extends Model
{   
     /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT id, nome, matricula, dv, id_local, funcao, ativo, perfil FROM usuarios ORDER BY nome ASC");
        //$stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca um usuário específico pelo seu ID.
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $userData = $stmt->fetch();
        return $userData ?: null;
    }

    /**
     * Salva um usuário (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function save(array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idUser'])) {
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
        $nome = strtoupper($data['nome']);
        $ativo = 1;
        
        // Hash da senha antes de salvar
        $senha = md5('pmsbc123');
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (matricula, dv, nome, id_local, funcao, ativo, perfil, senha) 
             VALUES (:matricula, :dv, :nome, :id_local, :funcao, :ativo, :perfil, :senha)"
        );

        return $stmt->execute([
            'matricula' => $data['matricula'],
            'dv' => $data['dv'],
            'nome' => $nome,
            'id_local' => $data['localId'],
            'funcao' => $data['funcao'],
            'ativo' => $ativo,
            'perfil' => $data['perfil'],
            'senha' => $senha
        ]);
    }

    /**
     * Método privado para atualizar um usuário existente.
     * @param array $data
     * @return bool
     */
    private function update(array $data): bool
    {
        $nome = strtoupper($data['nome']);
        $query = "UPDATE usuarios SET nome = :nome, id_local = :id_local, funcao = :funcao, perfil = :perfil WHERE id = :id";
        
        $params = [
            'nome' => $nome,
            'id_local' => $data['localId'],
            'funcao' => $data['funcao'],
            'perfil' => $data['perfil'],
            'id' => $data['idUser']
        ];

        // Opcional: só atualiza a senha se uma nova foi fornecida.
        /*
        if (!empty($data['senha'])) {
            $query = "UPDATE usuarios SET nome = :nome, matricula = :matricula, email = :email, perfil = :perfil, senha = :senha WHERE id = :id";
            $params['senha'] = md5($data['senha']);
        }
            */

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

    public function loginIn(array $data): ?User
    {
        $senha = md5($data['senha']);
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE matricula = :matricula AND senha = :senha");
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);      
        $stmt->execute(['matricula' => $data['matricula'], 'senha' => $senha]);
        $userData = $stmt->fetch();
        return $userData ?: null;
    }

    public function usuariosComPendencias(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT p.usuario_id, u.nome FROM pendencias_25 p JOIN usuarios u ON p.usuario_id = u.id");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }
}