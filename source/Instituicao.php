<?php
// source/Instituicao.php

namespace Source;

use PDO;
use Source\Database\Connect;

class Instituicao
{
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        // Ao criar um objeto Instituicao, já pegamos a conexão com o banco.
        $this->pdo = Connect::getInstance();
    }

        /**
     * Busca instituição específica pelo seu ID.
     * @param int $id
     * @return Instituicao|null
     */
    public function findById(int $id): ?Instituicao
    {
        $stmt = $this->pdo->prepare("SELECT * FROM instituicoes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $instData = $stmt->fetch();
        return $instData ?: null;
    }

        /**
     * Busca instituições pelo nome (busca parcial).
     * @param string $term O termo a ser buscado.
     * @return array
     */

    public function findByName(string $term): array
    {        
        $stmt = $this->pdo->prepare("SELECT * FROM instituicoes WHERE instituicao LIKE :nomeInst");
        $stmt->execute(['nomeInst' => '%' . $term . '%']);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetchAll();        
    }

    /**
     * Busca todos as instituições no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM instituicoes");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Salva um usuário (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function save(array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idInst'])) {
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
        $stmt = $this->pdo->prepare("DELETE FROM instituicoes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Método privado para criar uma nova instituição.
     * @param array $data
     * @return bool
     */
    private function create(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO instituicoes (instituicao, cnpj, endereco, telefone, email, inep, cont_id) 
             VALUES (:instituicao, :cnpj, :endereco, :telefone, :email, :inep, :cont_id)"
        );

        return $stmt->execute([
            'instituicao' => $data['instituicao'],
            'cnpj' => $data['cnpj'],
            'endereco' => $data['endereco'],
            'telefone' => $data['telefone'],
            'email' => $data['email'],
            'inep' => $data['inep'],
            'cont_id' => $data['contId']
        ]);
    }

    /**
     * Método privado para atualizar uma instituição existente.
     * @param array $data
     * @return bool
     */
    private function update(array $data): bool
    {
        $query = "UPDATE instituicoes SET instituicao = :instituicao, cnpj = :cnpj, endereco = :endereco, telefone = :telefone, email = :email, inep = :inep, cont_id = :cont_id 
                WHERE id = :id";
        
        $params = [
            'instituicao' => $data['instituicao'],
            'cnpj' => $data['cnpj'],
            'endereco' => $data['endereco'],
            'telefone' => $data['telefone'],
            'email' => $data['email'],
            'inep' => $data['inep'],
            'cont_id' => $data['contId'],            
            'id' => $data['idInst']
        ];
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }    
}