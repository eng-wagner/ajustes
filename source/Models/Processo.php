<?php
// source/Processo.php

namespace Source\Models;

use PDO;
use Source\Core\Model;
use DateTimeZone;
use DateTime;
 
class Processo extends Model
{   
    const STATUS_AGUARDANDO_ENTREGA = 1;
    const STATUS_RECEBIDO = 2;
    const STATUS_ANALISE_EXECUCAO = 3;
    const STATUS_PENDENCIA_AE= 4;
    const STATUS_ANALISE_FINANCEIRA = 5;
    const STATUS_AF_CONCLUIDO = 6;
    const STATUS_CONCLUIDO = 7;
    const STATUS_ERRO = 99;
    
    /**
     * Busca todos os processos no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM processo");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca um processo específico pelo seu ID.
     * @param int $id
     * @return Processo|null
     */
    public function findById(int $id): ?Processo
    {
        $stmt = $this->pdo->prepare("SELECT * FROM processos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $processoData = $stmt->fetch();
        return $processoData ?: null;
    }

    public function findByInstId(int $idInst): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM processos WHERE instituicao_id = :id");
        $stmt->execute(['id' => $idInst]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll();        
    }
    
    public function findProcByInstName(string $term): array
    {        
        $sql = "SELECT p.id as idProc, p.*, i.* FROM processos p JOIN instituicoes i ON p.instituicao_id = i.id WHERE i.instituicao LIKE :nomeInst";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['nomeInst' => '%' . $term . '%']);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetchAll();        
    }

    public function allProcs(): array
    {
        $sql = "SELECT p.id as idProc, p.*, i.* FROM processos p JOIN instituicoes i ON p.instituicao_id = i.id";
        $stmt = $this->pdo->query($sql);  
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function procStatus(int $id): ?Processo
    {
        $stmt = $this->pdo->prepare("SELECT a.status_id, s.status_pc FROM analise_pdde_25 a JOIN status_processo s ON a.status_id = s.id WHERE a.proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $statusData = $stmt->fetch();
        return $statusData ?: null;
    }

    public function receberProcesso(int $id): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now', $timezone);
        $agora = $agora->format('Y-m-d');
        $idSts = 2;

        $stmt = $this->pdo->prepare("INSERT INTO analise_pdde_25 (proc_id, status_id, data_ent) VALUES (:id,:idStatus,:dataEnt)");
        
        return $stmt->execute([
            'id' => $id,
            'idStatus' => $idSts,
            'dataEnt' => $agora
        ]);
    }

    public function abrirTramitacao(int $id): ?Processo
    {
        $stmt = $this->pdo->prepare("SELECT * FROM analise_pdde_25 WHERE proc_id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        
        return $stmt->fetch() ?: null;
    }

    public function saveExecucao(array $data, int $idSts, int $idUserEx, bool $pendente, int $idProc): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now',$timezone);
        $agora = $agora->format('Y-m-d');
        $svFlag = 1;

        $stmt = $this->pdo->prepare("UPDATE analise_pdde_25 SET 
            status_id = :idStatus, 
            usuario_ex_id= :idUser, 
            data_analise_ex = :dtAnalEx,
            obs_analise_ex = :obsAnEx,
            s_movimento = :sMovimento, 
            saved_flag = :svFlag,
            pendente = :pendente WHERE proc_id = :idProc");
        
        $params = [
            'idStatus' => $idSts,
            'idUser' => $idUserEx,
            'dtAnalEx' => $agora,
            'obsAnEx' => $data['analObs'],
            'sMovimento' => $data['checkMov'],
            'svFlag' => $svFlag,
            'pendente' => $pendente,
            'idProc' => $idProc
        ];
        
        return $stmt->execute($params);          
    }

    public function encaminharFin(int $idProc): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now',$timezone);
        $agora = $agora->format('Y-m-d');
        $idSts = 5;

        $stmt = $this->pdo->prepare("UPDATE analise_pdde_25 SET 
            status_id = :idStatus,
            data_enc_af = :agora 
            WHERE proc_id = :idProc");
        
        $params = [
            'idStatus' => $idSts,
            'agora' => $agora,            
            'idProc' => $idProc
        ];
        
        return $stmt->execute($params);          
    }

    public function atualizarFinan(array $data, int $idProc): bool
    {
        if(isset($data['checkEmailFin']) && $data['checkEmailFin'] == "1"){ $chEmailFin = 1; } else { $chEmailFin = 0; }        
        
        $stmt = $this->pdo->prepare("UPDATE analise_pdde_25 SET 
            obs_analise_fin = :obsAnFin, 
            email_af = :chEmailFin WHERE proc_id = :idProc");
        
        $params = [
            'obsAnFin' => $data['finObs'],
            'chEmailFin' => $chEmailFin,
            'idProc' => $idProc
        ];
        
        return $stmt->execute($params);
    }

    public function registrarSIGPC(int $idProc): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $agora = new DateTime('now', $timezone);
        $agora = $agora->format('Y-m-d');
        $idSts = 7;

        $stmt = $this->pdo->prepare("UPDATE analise_pdde_25 SET 
            status_id = :idStatus,
            data_sigpc = :agora WHERE proc_id = :idProc");
        
        $params = [
            'idStatus' => $idSts,
            'agora' => $agora,
            'idProc' => $idProc
        ];
        
        return $stmt->execute($params); 
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
            "INSERT INTO processos (orgao, numero, ano, digito, assunto, tipo, detalhamento, instituicao_id) 
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
        $query = "UPDATE processos SET orgao = :orgao, numero = :numero, ano = :ano, digito = :digito, assunto = :assunto, detalhamento = :detalhamento, instituicao_id = :instituicao_id WHERE id = :id";
        
        $params = [
            'orgao' => $data['docNome'],
            //'numero' => $checkTcU,
            //'ano' => $checkPddeU,
            'id' => $data['idDoc']
        ];
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function formatarProcesso($p): string
    {
        return "{$p->orgao}.{$p->numero}/{$p->ano}-{$p->digito}";
    }


}