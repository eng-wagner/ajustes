<?php
// source/Despesa.php
namespace Source\Models;

require_once __DIR__ . "/../Helpers/Helpers.php"; // Onde está a sua função limparMoedaSQL()

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
        $stmt = $this->pdo->prepare(
            "INSERT INTO pdde_despesas_25(proc_id, acao_id, categoria, fornecedor_id, descricao, documento, pagamento, data_desp, valor, check_prog, check_ata, check_enq, check_cons, usuario_id, datahora) 
            VALUES (:proc_id, :acao_id, :categoria, :fornecedor_id, :descricao, :documento, :pagamento, :data_desp, :valor, :check_prog, :check_ata, :check_enq, :check_cons, :usuario_id, :datahora)"
        );

        return $stmt->execute([
            'proc_id' => $idProc,
            'acao_id' => $data['acaoId'],
            'categoria' => $data['categoria'],
            'fornecedor_id' => $data['fornecedor_id'],
            'descricao' => $data['descDesp'],
            'documento' => $data['numDoc'],
            'pagamento' => $data['numPgto'],
            'data_desp' => $data['dataDoc'],
            'valor' => limparMoedaSQL($data['valDesp']),
            'check_prog' => parseCheckbox($data['checkProg']),
            'check_ata' => parseCheckbox($data['checkAta']),
            'check_enq' => parseCheckbox($data['checkEnquad']),
            'check_cons' => parseCheckbox($data['checkConso']),
            'usuario_id' => $idUser,
            'datahora' => getCurrentDateTime()
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
        $query = "UPDATE pdde_despesas_25 SET 
            acao_id = :acao_id, 
            categoria = :categoria, 
            fornecedor_id = :fornecedor_id,
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
            'fornecedor_id' => $data['fornecedor_id'],            
            'descricao' => $data['descDesp'],
            'documento' => $data['numDoc'],
            'pagamento' => $data['numPgto'],
            'data_desp' => $data['dataDoc'],
            'valor' => limparMoedaSQL($data['valDesp']),
            'check_prog' => parseCheckbox($data['checkProg']),
            'check_ata' => parseCheckbox($data['checkAta']),
            'check_enq' => parseCheckbox($data['checkEnquad']),
            'check_cons' => parseCheckbox($data['checkConso']),
            'usuario_id' => $idUser,
            'datahora' => getCurrentDateTime(),
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
        $stmt = $this->pdo->prepare("UPDATE pdde_despesas_25 SET 
            data_pg = :dataPg,
            valor_pg = :valorPg
            WHERE pagamento = :pagamento AND proc_id = :idProc");
        
        $params = [
            'dataPg' => $data['dataPg'],
            'valorPg' => limparMoedaSQL($data['valPago']),
            'pagamento' => $pagamento,
            'idProc' => $idProc
        ];        

        return $stmt->execute($params);
    }

    public function glosarDespesa(array $data, int $idDesp): bool
    {                
        $stmt = $this->pdo->prepare("UPDATE pdde_despesas_25 SET 
            valor_gl = :valorGl, 
            motivo_gl = :motivoGl                
            WHERE id = :idDesp");
        
        $params = [            
            'valorGl' => str_replace(["R$ ", ".", ","], ["", "", "."], $data['valGlosa']),
            'motivoGl' => $data['motivoGlosa'],
            'idDesp' => $idDesp 
        ];        

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
        $stmt->execute(['idProc' => $idProc, 'idAcao' => $idAcao,'cat' => $cat]);        
        return (float) $stmt->fetchColumn();
    }

    public function somaGlosaByAcaoProc(int $idProc, int $idAcao, string $cat): float
    {
        $stmt = $this->pdo->prepare("SELECT SUM(valor_gl) AS glosa FROM pdde_despesas_25 WHERE acao_id = :idAcao AND proc_id = :idProc AND categoria = :cat");
        $stmt->execute(['idProc' => $idProc, 'idAcao' => $idAcao, 'cat' => $cat]);        
        return (float) $stmt->fetchColumn();
    }

    public function novoFornecedor(array $data) 
    {
        $stmt = $this->pdo->prepare("INSERT INTO fornecedores (cnpj, razao_social) VALUES (:cnpj, :razao_social)");
        if ($stmt->execute([
            'cnpj' => limparCNPJ($data['cnpj']), 
            'razao_social' => $data['razao_social']
            ])) {
            // Retorna o ID do fornecedor que acabou de ser criado (muito útil pro AJAX!)
            return $this->pdo->lastInsertId(); 
        }
        return false;
    }

    public function findFornecedorById(int $idForn) 
    {
        $stmt = $this->pdo->prepare("SELECT * FROM fornecedores WHERE id = :idForn");
        $stmt->execute(['idForn' => $idForn]);
        return $stmt->fetch(\PDO::FETCH_OBJ); // Mudamos para OBJ para ficar igual ao de baixo
    } 
    
    public function findAllFornecedores() 
    {
        $sql = $this->pdo->prepare("SELECT * FROM fornecedores ORDER BY razao_social ASC");
        $sql->execute();
        return $sql->fetchAll(\PDO::FETCH_OBJ); 
    }

    public function buscarDespesasRelatorio($fornecedorId = null, $programa = null, $categoria = null) 
{
    // Note os JOINs para pegar o nome do programa, razão social e processo
    $sql = "SELECT d.*, 
                   f.razao_social, f.cnpj,
                   p.*, p.tipo as programa_nome
            FROM pdde_despesas_25 d
            INNER JOIN fornecedores f ON d.fornecedor_id = f.id
            INNER JOIN processos p ON d.proc_id = p.id
            WHERE 1=1"; // 1=1 é um truque para facilitar a adição de condições depois
    
    $params = [];

    // Filtro de Fornecedor
    if (!empty($fornecedorId)) {
        $sql .= " AND d.fornecedor_id = :fornId ";
        $params['fornId'] = $fornecedorId;
    }

    // Filtro de Programa (puxando da tabela processos)
    if (!empty($programa)) {
        $sql .= " AND p.tipo = :prog ";
        $params['prog'] = $programa;
    }

    // Filtro de Categoria (C = Custeio, K = Capital)
    if (!empty($categoria)) {
        $sql .= " AND d.categoria = :cat ";
        $params['cat'] = $categoria;
    }

    $sql .= " ORDER BY d.data_desp DESC"; // Ordena das mais recentes para mais antigas

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(\PDO::FETCH_OBJ);
}
}