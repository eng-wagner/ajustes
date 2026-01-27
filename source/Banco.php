<?php
// source/Banco.php

namespace Source;

use PDO;
use Source\Database\Connect;

class Banco
{   
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        // Ao criar um objeto Banco, já pegamos a conexão com o banco.
        $this->pdo = Connect::getInstance();
    }

    /**
     * Busca todos os contas no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM banco");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function findById(int $id): ?Banco
    {
        $stmt = $this->pdo->prepare("SELECT * FROM banco WHERE id = :idConta");
        $stmt->execute(['idConta' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $bancoData = $stmt->fetch();
        return $bancoData ?: null;
    }

    /**
     * Busca um conta específico pelo seu ID.
     * @param int $id
     * @return Banco|null
     */
    public function findLYById(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT agencia, conta, cc_2023 AS cc_LY, pp_01_2023 AS pp_01_LY, pp_51_2023 AS pp_51_LY, spubl_2023 AS spubl_LY, bb_rf_cp_2023 AS bb_rf_cp_LY FROM banco WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    public function findCYById(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT agencia, conta, cc_2024 AS cc_CY, pp_01_2024 AS pp_01_CY, pp_51_2024 AS pp_51_CY, spubl_2024 AS spubl_CY, bb_rf_cp_2024 AS bb_rf_cp_CY FROM banco WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    public function findByProcId(int $id): array
    {        
        $stmt = $this->pdo->prepare("SELECT *,
            cc_2023 AS cc_LY, 
            cc_2024 AS cc_CY, 
            pp_01_2023 AS pp_01_LY, 
            pp_01_2024 AS pp_01_CY,
            pp_51_2023 AS pp_51_LY, 
            pp_51_2024 AS pp_51_CY, 
            spubl_2023 AS spubl_LY,
            spubl_2024 AS spubl_CY, 
            bb_rf_cp_2023 AS bb_rf_cp_LY,
            bb_rf_cp_2024 AS bb_rf_cp_CY FROM banco WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    public function findByInstId(int $idInst): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM banco WHERE instituicao_id = :id");
        $stmt->execute(['id' => $idInst]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();        
    }

    public function somaBancoCY(int $idProc): ?Banco
    {
        $stmt = $this->pdo->prepare("SELECT SUM(cc_2024) AS ccSF, SUM(pp_01_2024) AS pp01SF, SUM(pp_51_2024) AS pp51SF, SUM(spubl_2024) AS spublSF, SUM(bb_rf_cp_2024) AS bbrfSF FROM banco WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $bancoData = $stmt->fetch();
        return $bancoData ?: null;
    }

    /**
     * Salva um processo (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function save(array $data): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idProc'])) {
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
        $stmt = $this->pdo->prepare(
            "INSERT INTO banco (orgao, numero, ano, digito, assunto, tipo, detalhamento, instituicao_id) 
             VALUES (:orgao, :numero, :ano, :digito, :assunto, :tipo, :detalhamento, :instituicao_id)"
        );
        
        return $stmt->execute([
            'orgao' => $data['docNome'],
            //'numero' => $checkTc,
            //'ano' => $checkPdde
        ]);
    }

    /**
     * Método privado para atualizar um item existente.
     * @param array $data
     * @return bool
     */
    private function update(array $data): bool
    {             
        $query = "UPDATE banco SET orgao = :orgao, numero = :numero, ano = :ano, digito = :digito, assunto = :assunto, detalhamento = :detalhamento, instituicao_id = :instituicao_id WHERE id = :id";
        
        $params = [
            'orgao' => $data['docNome'],
            //'numero' => $checkTcU,
            //'ano' => $checkPddeU,
            'id' => $data['idDoc']
        ];
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }
}