<?php
// source/Saldo.php

namespace Source;

use PDO;
use Source\Database\Connect;

class Saldo
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

    public function findById(int $id): ?Saldo
    {
        $stmt = $this->pdo->prepare("SELECT *, saldo23 as saldoLY, rp24 as rpCY, rent24 as rentCY, devl24 as devlCY, saldo24 as saldoCY FROM saldo_pdde WHERE id = :idSaldo");
        $stmt->execute(['idSaldo' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $saldoData = $stmt->fetch();
        return $saldoData ?: null;
    }

    /**
     * Busca um conta específico pelo seu ID.
     * @param int $id
     * @return Banco|null
     */
    public function findSaldoByProcCat(int $idProc, string $cat): array
    {
        $stmt = $this->pdo->prepare("SELECT *, saldo23 as saldoLY, rp24 as rpCY, rent24 as rentCY, devl24 as devlCY FROM saldo_pdde WHERE proc_id = :idProc AND categoria = :cat");
        $stmt->execute([
            'idProc' => $idProc,
            'cat' => $cat ]);
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

    public function findByInstId(int $idInst): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM banco WHERE instituicao_id = :id");
        $stmt->execute(['id' => $idInst]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();        
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