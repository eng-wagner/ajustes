<?php
// source/Despesa.php

namespace Source\Models;

use PDO;
use Source\Core\Model;
use DateTimeZone;
use DateTime;

class Despesa extends Model
{  
    /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM pdde_despesas_25");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Busca uma despesa específica pelo seu ID.
     * @param int $id
     * @return Despesa|null
     */ 
    public function findById(int $id): ?Despesa
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pdde_despesas_25 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        $despesaData = $stmt->fetch();
        return $despesaData ?: null;
    }

    public function findByProcId(int $procId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pdde_despesas_25 WHERE proc_id = :procId");
        $stmt->execute(['procId' => $procId]);
        //$stmt->setFetchMode(PDO::FETCH_CLASS, self::class);

        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);        
    }

    /**
     * Salva um usuário (cria um novo ou atualiza um existente).
     * @param array $data (dados vindos do formulário, ex: $_POST)
     * @return bool
     */
    public function save(array $data, int $idProc, int $idUser): bool
    {
        // Se o ID existir nos dados, é uma atualização (UPDATE).
        if (!empty($data['idDespM'])) {
            return $this->update($data, $idUser);
        }

        // Se não, é uma criação (INSERT).
        return $this->create($data, $idProc, $idUser);
    }    

    /**
     * Método privado para criar um novo usuário.
     * @param array $data
     * @param int $idProc
     * @param int $idUser
     * @return bool
     */
    private function create(array $data, int $idProc, int $idUser): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $hora = new DateTime('now', $timezone);
        $hora = $hora->format('Y-m-d H:i:s');

        $valDespSQL = str_replace("R$ ","",$data['valDesp']);
        $valDespSQL = str_replace(".", "", $valDespSQL);
        $valDespSQL = str_replace(",", ".", $valDespSQL);
        
        if(isset($data['checkProg']) && $data['checkProg'] == "1"){ $chProg = 1; } else { $chProg = 0; }
        if(isset($data['checkAta']) && $data['checkAta'] == "1"){ $chAta = 1; } else { $chAta = 0; }
        if(isset($data['checkEnquad']) && $data['checkEnquad'] == "1"){ $chEnq = 1; } else { $chEnq = 0; }
        if(isset($data['checkConso']) && $data['checkConso'] == "1"){ $chCs = 1; } else { $chCs = 0; }

       
        $stmt = $this->pdo->prepare(
            "INSERT INTO pdde_despesas_25(proc_id, acao_id, categoria, fornecedor, cnpj_forn, descricao, documento, pagamento, data_desp, valor, check_prog, check_ata, check_enq, check_cons, usuario_id, datahora) 
            VALUES (:proc_id, :acao_id, :categoria, :fornecedor, :cnpj_forn, :descricao, :documento, :pagamento, :data_desp, :valor, :check_prog, :check_ata, :check_enq, :check_cons, :usuario_id, :datahora)"
        );

        return $stmt->execute([
            'proc_id' => $idProc,
            'acao_id' => $data['acaoId'],
            'categoria' => $data['categoria'],
            'fornecedor' => $data['fornecedor'],
            'cnpj_forn' => $data['cnpjForn'],
            'descricao' => $data['descDesp'],
            'documento' => $data['numDoc'],
            'pagamento' => $data['numPgto'],
            'data_desp' => $data['dataDoc'],
            'valor' => $valDespSQL,
            'check_prog' => $chProg,
            'check_ata' => $chAta,
            'check_enq' => $chEnq,
            'check_cons' => $chCs,
            'usuario_id' => $idUser,
            'datahora' => $hora
        ]);
    }

    /**
     * Método privado para atualizar um usuário existente.
     * @param array $data
     * @param int $idUser
     * @return bool
     */
    private function update(array $data, int $idUser): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $hora = new DateTime('now', $timezone);
        $hora = $hora->format('Y-m-d H:i:s');
        
        if(isset($data['checkProg']) && $data['checkProg'] == "1"){ $chProg = 1; } else { $chProg = 0; }
        if(isset($data['checkAta']) && $data['checkAta'] == "1"){ $chAta = 1; } else { $chAta = 0; }
        if(isset($data['checkEnquad']) && $data['checkEnquad'] == "1"){ $chEnq = 1; } else { $chEnq = 0; }
        if(isset($data['checkConso']) && $data['checkConso'] == "1"){ $chCs = 1; } else { $chCs = 0; }

        $query = "UPDATE pdde_despesas_25 SET 
            acao_id = :acao_id, 
            categoria = :categoria, 
            fornecedor = :fornecedor, 
            cnpj_forn = :cnpj_forn, 
            descricao = :descricao, 
            documento = :documento, 
            pagamento = :pagamento, 
            data_desp = :data_desp, 
            valor = :valor, 
            check_prog = :check_prog, 
            check_ata = :check_ata, 
            check_enq = :check_enq, 
            check_cons = :check_cons, 
            usuario_id = :usuario_id, 
            datahora = :datahora 
            WHERE id = :idDespM";
        
        $params = [
            'acao_id' => $data['acaoId'],
            'categoria' => $data['categoria'],
            'fornecedor' => $data['fornecedor'],
            'cnpj_forn' => $data['cnpjForn'],
            'descricao' => $data['descDesp'],
            'documento' => $data['numDoc'],
            'pagamento' => $data['numPgto'],
            'data_desp' => $data['dataDoc'],
            'valor' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['valDesp']),
            'check_prog' => $chProg,
            'check_ata' => $chAta,
            'check_enq' => $chEnq,
            'check_cons' => $chCs,
            'usuario_id' => $idUser,
            'datahora' => $hora,
            'idDespM' => $data['idDespM']
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
        $stmt = $this->pdo->prepare("DELETE FROM pdde_despesas_25 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function liquidarDespesa(array $data, string $pagamento, int $idProc): bool
    {
        $timezone = new DateTimeZone("America/Sao_Paulo");
        $hora = new DateTime('now', $timezone);
        $hora = $hora->format('Y-m-d H:i:s');

        $query = "UPDATE pdde_despesas_25 SET 
            data_pg = :dataPg, 
            valor_pg = :valorPg                
            WHERE pagamento = :pagamento AND proc_id = :idProc";
        
        $params = [
            'dataPg' => $data['dataPg'],
            'valorPg' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['valPago']),
            'pagamento' => $pagamento,
            'idProc' => $idProc            
        ];        

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function glosarDespesa(array $data, int $idDesp): bool
    {                
        $query = "UPDATE pdde_despesas_25 SET 
            valor_gl = :valorGl, 
            motivo_gl = :motivoGl                
            WHERE id = :idDesp";
        
        $params = [            
            'valorGl' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['valGlosa']),
            'motivoGl' => $data['motivoGlosa'],
            'idDesp' => $idDesp 
        ];        

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function getResumoDespesas(int $idProc): array
    {
        // Pega Valor, Pago e Glosado de uma vez só!
        $stmt = $this->pdo->prepare("SELECT SUM(valor) AS despesa, SUM(valor_pg) AS pagamento, SUM(valor_gl) AS glosas FROM pdde_despesas_25 WHERE proc_id = :idProc");
        $stmt->execute(['idProc' => $idProc]);
        if ($res = $stmt->fetch(\PDO::FETCH_OBJ)) {
            return [
                'despesa' => (float) $res->despesa,
                'pagamento' => (float) $res->pagamento,
                'glosas' => (float) $res->glosas
            ];
        }
        return ['despesa' => 0.0, 'pagamento' => 0.0, 'glosas' => 0.0];
    }

    public function somaByCatAcaoProc(int $idProc, int $idAcao, string $cat): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(valor) AS despesa FROM pdde_despesas_25 WHERE acao_id = :idAcao AND proc_id = :idProc AND categoria = :cat");
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $stmt->execute([
            'idProc' => $idProc,
            'idAcao' => $idAcao,
            'cat' => $cat
        ]);
        
        return (float) $stmt->fetchColumn();
    }

    public function somaGlosaByAcaoProc(int $idProc, int $idAcao, string $cat): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(valor_gl) AS glosa FROM pdde_despesas_25 WHERE acao_id = :idAcao AND proc_id = :idProc AND categoria = :cat");
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $stmt->execute([
            'idProc' => $idProc,
            'idAcao' => $idAcao,
            'cat' => $cat
        ]);
        
        return (float) $stmt->fetchColumn();
    }
}