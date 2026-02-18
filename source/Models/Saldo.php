<?php
// source/Saldo.php

namespace Source\Models;

use PDO;
use Source\Core\Model;
use DateTime;
use DateTimeZone;

class Saldo extends Model
{   
    /**
     * Busca todos os contas no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM saldo_pdde");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function findById(int $id): ?Saldo
    {
        $stmt = $this->pdo->prepare("SELECT *, saldo24 as saldoLY, rp25 as rpCY, rent25 as rentCY, devl25 as devlCY, saldo25 as saldoCY FROM saldo_pdde WHERE id = :idSaldo");
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
        $stmt = $this->pdo->prepare("SELECT *, saldo24 as saldoLY, rp25 as rpCY, rent25 as rentCY, devl25 as devlCY FROM saldo_pdde WHERE proc_id = :idProc AND categoria = :cat");
        $stmt->execute([
            'idProc' => $idProc,
            'cat' => $cat ]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    public function findSaldoByProcCatAcao(int $idProc, array $data): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM saldo_pdde WHERE proc_id = :idProc AND acao_id = :idAcao AND categoria = :cat");
        $stmt->execute([
            'idProc' => $idProc,
            'idAcao' => $data['acao'],
            'cat' => $data['categoria']
            ]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();
    }

    public function somaByProcId(int $idProc): object
    {
        $stmt = $this->pdo->prepare("SELECT SUM(saldo24) as saldo_anterior, SUM(rp25) as rp, SUM(rent25) as rent, SUM(devl25) as devl FROM saldo_pdde WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    public function findByProcId(int $idProc): array
    {
        $stmt = $this->pdo->prepare("SELECT *, 
                saldo24 as saldo_anterior, 
                rp25 as rp, 
                rent25 as rent, 
                devl25 as devl 
                FROM saldo_pdde WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        return $stmt->fetchAll();
    }

    public function atualizarSaldoFinal(int $idSaldo, float $valor, int $idUser): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $hora = new DateTime('now', $timezone);
        $hora = $hora->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("UPDATE saldo_pdde 
            SET saldo25 = :valor, 
            user_id = :idUser, 
            data_hora = :agora WHERE id = :idSaldo");
        return $stmt->execute([
            'valor' => $valor,
            'idUser' => $idUser,
            'agora' => $hora,
            'idSaldo' => $idSaldo
        ]);              
    }

    public function setSaldoInicial(int $idInst, int $idProc, array $data): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO saldo_pdde (instituicao_id, proc_id, acao_id, categoria, saldo24) VALUES (:idInst, :idProc, :idAcao, :cat, :saldo)");
        return $stmt->execute([
            'idInst' => $idInst,
            'idProc' => $idProc,
            'idAcao' => $data['acao'],
            'cat' => $data['categoria'],
            'saldo' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['saldo24'])
        ]);
    }

    public function updateSaldo(int $idUser, int $idProc, array $data): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $hora = new DateTime('now', $timezone);
        $hora = $hora->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("UPDATE saldo_pdde 
            SET rp25 = :rp, 
            rent25 = :rent,
            devl25 = :devl,
            data_hora = :data_hora,
            user_id = :idUser
            WHERE id = :idSaldo AND proc_id = :idProc");
        return $stmt->execute([
            'rp' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rp25']),
            'rent' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['rent25']),
            'devl' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['devol25']),
            'data_hora' => $hora,
            'idUser' => $idUser,
            'idSaldo' => $data['idSaldoM'],
            'idProc'=> $idProc
        ]);
    }

    public function getSaldoFinalPDDE(int $idProc): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(saldo25) as saldo FROM saldo_pdde WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        if ($res = $stmt->fetch(\PDO::FETCH_OBJ)) {
            return (float) $res->saldo;
        }
        return 0.0;
    }

    public function getRentFinalPDDE(int $idProc): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(rent25) as rentabilidade FROM saldo_pdde WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        if ($res = $stmt->fetch(\PDO::FETCH_OBJ)) {
            return (float) $res->rentabilidade;
        }
        return 0.0;
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

    public function contColumsAF(int $idProc): bool
    {             
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS contagem FROM saldo_pdde WHERE proc_id = :idProc");        
        $stmt->execute(['idProc' => $idProc]);
        return (int) $stmt->fetchColumn();
    }

    public function getRelatorioAF(int $idProc): array
    {             
        $arrayAf = [];

        // 1. Busca os saldos principais para análise financeira
        $sql = "SELECT s.acao_id, p.acao, s.categoria, s.saldo24, s.rp25, s.rent25, s.devl25, s.saldo25 
                FROM saldo_pdde s 
                JOIN programaspdde p ON s.acao_id = p.id 
                WHERE proc_id = :idProc 
                ORDER BY s.acao_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idProc' => $idProc]);
        $saldos = $stmt->fetchAll(PDO::FETCH_OBJ);
                    
        // 2. Loop para buscar os detalhes de cada saldo e montar o array final
        foreach ($saldos as $af) {
            $acaoId = $af->acao_id;            
            $categoria = $af->categoria;

            $despesa = 0.0;
            $glosa = 0.0;
            $repasse = 0.0;

            // Busca despesas e glosas para a ação, processo e categoria
            $stmtDesp = $this->pdo->prepare("SELECT SUM(valor) AS despesa, SUM(valor_gl) AS glosa FROM pdde_despesas_25 WHERE acao_id = :idAcao AND proc_id = :idProc AND categoria = :cat");
            $stmtDesp->execute([
                'idAcao' => $acaoId,
                'idProc' => $idProc,
                'cat' => $categoria
            ]);
            if($val = $stmtDesp->fetch(PDO::FETCH_OBJ)) {
                $despesa = (float) $val->despesa;
                $glosa = (float) $val->glosa;
            }
            
            // Busca Repasses dependendo da categoria (Custeio ou Capital)
            if($categoria == "C")
            {
                $stmtRep = $this->pdo->prepare("SELECT SUM(custeio) AS repasse FROM repasse_25 WHERE acao_id = :idAcao AND proc_id = :idProc");
                $stmtRep->execute([
                    'idAcao' => $acaoId,
                    'idProc' => $idProc
                ]);            
                if($val = $stmtRep->fetch(PDO::FETCH_OBJ)) {                
                    $repasse = (float) $val->repasse;
                }            
            } else if($categoria == "K") {
                $stmtRep = $this->pdo->prepare("SELECT SUM(capital) AS repasse FROM repasse_25 WHERE acao_id = :idAcao AND proc_id = :idProc");
                $stmtRep->execute([
                    'idAcao' => $acaoId,
                    'idProc' => $idProc
                ]);
                if($val = $stmtRep->fetch(PDO::FETCH_OBJ)) {                
                    $repasse = (float) $val->repasse;
                }                
            }

            // Monta o array para a ação atual
            $arrayAf[] = [
                "acao" => $af->acao,
                "categoria" => $categoria,                
                "saldoInicial" => (float) $af->saldo24,
                "repasse" => $repasse,
                "rp" => (float) $af->rp25,
                "rent" => (float) $af->rent25,
                "devol" => (float) $af->devl25,
                "despesa" => $despesa,
                "glosa" => $glosa,
                "saldoParcial" => ((float) $af->saldo25) - $glosa,
                "saldoFinal" => (float) $af->saldo25
            ];
        }

        return $arrayAf;
    }
}