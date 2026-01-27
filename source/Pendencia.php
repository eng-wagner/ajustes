<?php
// source/Pendencia.php

namespace Source;

use PDO;
use Source\Database\Connect;
use DateTimeZone;
use DateTime;

class Pendencia
{   
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        // Ao criar um objeto Pendencia, já pegamos a conexão com o banco.
        $this->pdo = Connect::getInstance();
    }

    /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM pendencias_24");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function allTipos(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM tipo_pendencia");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function findPendAtivas(): array
    {
        $stmt = $this->pdo->query("SELECT s.id, u.id AS iduser, u.nome, s.dataPend, s.itemDRD, t.documento, s.favorecido, d.pendencia, s.providencias, s.resolvido, s.dataResolvido, p.tipo, i.instituicao FROM pendencias_24 s JOIN processos p ON s.proc_id = p.id JOIN instituicoes i ON p.instituicao_id = i.id JOIN usuarios u ON s.usuario_id = u.id JOIN tipo_documento t ON s.docPend_id = t.id JOIN tipo_pendencia d ON s.pend_id = d.id WHERE s.ativado = 1");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);        
    }

    public function findPendAtivasByProg(string $programa): array
    {
        $stmt = $this->pdo->prepare("SELECT s.id, u.id AS iduser, u.nome, s.dataPend, s.itemDRD, t.documento, s.favorecido, d.pendencia, s.providencias, s.resolvido, s.dataResolvido, p.tipo, i.instituicao FROM pendencias_24 s JOIN processos p ON s.proc_id = p.id JOIN instituicoes i ON p.instituicao_id = i.id JOIN usuarios u ON s.usuario_id = u.id JOIN tipo_documento t ON s.docPend_id = t.id JOIN tipo_pendencia d ON s.pend_id = d.id WHERE s.ativado = 1 AND p.tipo = :programa");
        $stmt->execute(['programa' => $programa]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);        
    }

    /**
     * Busca uma despesa específica pelo seu ID.
     * @param int $id
     * @return Pendencia|null
     */ 
    public function findById(int $id): ?Pendencia
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pendencias_24 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $pendenciaData = $stmt->fetch();
        return $pendenciaData ?: null;
    }

    public function findTipoById(int $id): ?Pendencia
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tipo_pendencia WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $tipoData = $stmt->fetch();
        return $tipoData ?: null;
    }

    public function contarPendencias(int $procId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS pendencias FROM pendencias_24 WHERE proc_id = :procId AND resolvido = 0 AND ativado = 1");
        $stmt->execute(['procId' => $procId]);
        return (int) $stmt->fetchColumn();        
    }

    public function findByProcId(int $procId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pendencias_24 WHERE proc_id = :procId");
        $stmt->execute(['procId' => $procId]);        

        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);        
    }

    public function regularizarPendencia(int $id): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now',$timezone);
        $agora = $agora->format("Y-m-d H:i:s");                
                        
        $resolvido = 1;

        $query = "UPDATE pendencias_24 SET 
            resolvido = :resolvido,
            dataResolvido = :agora
            WHERE id = :id";
        
        $params = [
            'resolvido' => $resolvido,
            'agora' => $agora,
            'id' => $id
        ];

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function deletarPendencia(int $id, int $idUser): bool
    {        
        $ativado = 0;

        $query = "UPDATE pendencias_24 SET 
            usuario_id = :userId, 
            ativado = :ativado
            WHERE id = :id";
        
        $params = [
            'userId' => $idUser,
            'ativado' => $ativado,
            'id' => $id
        ];

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Salva um usuário (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function save(array $data, int $idProc, int $idUser): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idPendM'])) {
            return $this->update($data, $idUser);
        }

        // Se não, é uma criação (INSERT).
        return $this->create($data, $idProc, $idUser);
    }    

    /**
     * Método privado para criar um novo usuário.
     * @param array $data
     * @return bool
     */
    private function create(array $data, int $idProc, int $idUser): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now', $timezone);
        $agora = $agora->format('Y-m-d H:i:s');

        $dataDocPend = new DateTime($data['dataDocP'],$timezone);
        $dataDocPend = $dataDocPend->format("Y-m-d");
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO pendencias_24 (proc_id, usuario_id, dataPend, itemDRD, docPend_id, favorecido, dataDocPend, numDocPend, pend_id, providencias, etapa_id) 
            VALUES (:proc_id, :usuario_id, :dataPend, :itemDRD, :docPend_id, :favorecido, :dataDocPend, :numDocPend, :pend_id, :providencias, :etapa_id)"
        );

        return $stmt->execute([
            'proc_id' => $idProc,
            'usuario_id' => $idUser,
            'dataPend' => $agora,
            'itemDRD' => $data['itemDRD'],
            'docPend_id' => $data['docPend'],
            'favorecido' => $data['favorecido'],
            'dataDocPend' => $dataDocPend,
            'numDocPend' => $data['numDocP'],
            'pend_id' => $data['pendencia'],
            'providencias' => $data['providencias'],
            'etapa_id' => $data['etapaPend']
        ]);
    }

    /**
     * Método privado para atualizar um usuário existente.
     * @param array $data
     * @return bool
     */
    private function update(array $data, int $idUser): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now', $timezone);
        $agora = $agora->format('Y-m-d H:i:s');

        $dataDocPend = new DateTime($data['dataDocP'],$timezone);
        $dataDocPend = $dataDocPend->format("Y-m-d");

        $query = "UPDATE pendencias_24 SET 
            usuario_id = :usuario_id, 
            dataPend = :dataPend, 
            itemDRD = :itemDRD, 
            docPend_id = :docPend_id, 
            favorecido = :favorecido, 
            dataDocPend = :dataDocPend, 
            numDocPend = :numDocPend, 
            pend_id = :pend_id, 
            providencias = :providencias, 
            etapa_id = :etapa_id
            WHERE id = :idPend";
        
        $params = [
            'usuario_id' => $idUser,
            'dataPend' => $agora,
            'itemDRD' => $data['itemDRD'],
            'docPend_id' => $data['docPend'],
            'favorecido' => $data['favorecido'],
            'dataDocPend' => $dataDocPend,
            'numDocPend' => $data['numDocP'],
            'pend_id' => $data['pendencia'],
            'providencias' => $data['providencias'],
            'etapa_id' => $data['etapaPend'],
            'idPend' => $data['idPendM']
        ];
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Deleta uma despesa do banco de dados.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM pdde_despesas_24 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
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