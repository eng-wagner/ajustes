<?php
// source/Logs.php

namespace Source;

use PDO;
use Source\Database\Connect;
use DateTimeZone;
use DateTime;

class Logs
{
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        // Ao criar um objeto Logs, já pegamos a conexão com o banco.
        $this->pdo = Connect::getInstance();
    }

    /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM action_logs");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca um usuário específico pelo seu ID.
     * @param int $id
     * @return Logs|null
     */
    /*
    public function findById(int $id): ?Logs
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $logsData = $stmt->fetch();
        return $logsData ?: null;
    }
    */
    /**
     * Salva um usuário (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function save(array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['id'])) {
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
        $stmt = $this->pdo->prepare("DELETE FROM action_logs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Método privado para criar um novo usuário.
     * @param array $data
     * @return bool
     */
    private function create(array $data): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");

        $hora = new DateTime('now', $timezone);
        $hora = $hora->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            "INSERT INTO action_logs (usuario, acao, hora) 
             VALUES (:usuario, :acao, :hora)"
        );

        return $stmt->execute([
            'usuario' => $data['usuario'],
            'acao' => $data['acao'],
            'hora' => $hora            
        ]);
    }

    /**
     * Método privado para atualizar um usuário existente.
     * @param array $data
     * @return bool
     */
    private function update(array $data): bool
    {
        $query = "UPDATE usuarios SET nome = :nome, matricula = :matricula, email = :email, perfil = :perfil WHERE id = :id";
        
        $params = [
            'nome' => $data['nome'],
            'matricula' => $data['matricula'],
            'email' => $data['email'],
            'perfil' => $data['perfil'],
            'id' => $data['id']
        ];

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }
}