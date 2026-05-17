<?php
// source/Despesa.php
namespace Source\Models;

require_once __DIR__ . "/../Helpers/Helpers.php"; // Onde está a sua função limparMoedaSQL()

use PDO;
use Source\Core\Model;

class Fornecedor extends Model
{  
    /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM fornecedores");        
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
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