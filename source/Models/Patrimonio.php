<?php
namespace Source\Models;
require_once __DIR__ . "/../Helpers/Helpers.php"; // Onde está a sua função limparMoedaSQL()

use PDO;
use PDOException;
use Source\Core\Model;
use DateTimeZone;
use DateTime;

class Patrimonio extends Model {
    
    /**
     * Lista todos os bens de uma instituição específica, 
     * já trazendo o nome do fornecedor e o número do processo digital.
     */
    public function listarPorProcesso(int $idProcesso): array
    {
        // Usamos LEFT JOIN para que o bem apareça mesmo se o fornecedor ou processo for apagado no futuro
        $sql = "SELECT * FROM bens_patrimoniais WHERE processo_id = :processo_id ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['processo_id' => $idProcesso]);        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Salva um novo bem patrimonial no banco de dados
     */
    public function save(array $data): array
    {
        try {
            // 1. Tratamento de valores numéricos e moeda
            $valorUnitario = limparMoedaSQL($data['valor_unitario'] ?? '0');
            $quantidade    = (int)($data['quantidade'] ?? 1);
            
            // Calculamos o total automaticamente para evitar erros do usuário!
            $valorTotal    = $valorUnitario * $quantidade;

            // 2. Prepara a query
            $sql = "INSERT INTO bens_patrimoniais (
                        instituicao_id, processo_id, fornecedor_id, origem_recurso,
                        numero_termo_doacao, numero_patrimonio, nir, categoria,
                        descricao_item, marca_modelo, quantidade, valor_unitario,
                        valor_total, nota_fiscal, data_aquisicao, local_guarda, status
                    ) VALUES (
                        :instituicao_id, :processo_id, :fornecedor_id, :origem_recurso,
                        :numero_termo_doacao, :numero_patrimonio, :nir, :categoria,
                        :descricao_item, :marca_modelo, :quantidade, :valor_unitario,
                        :valor_total, :nota_fiscal, :data_aquisicao, :local_guarda, :status
                    )";

            $stmt = $this->pdo->prepare($sql);
            
            // 3. Executa a query tratando campos vazios (transformando '' em NULL para o MySQL não chiar)
            $stmt->execute([
                'instituicao_id'      => $data['instituicao_id'],
                'processo_id'         => !empty($data['processo_id']) ? $data['processo_id'] : null,
                'fornecedor_id'       => !empty($data['fornecedor_id']) ? $data['fornecedor_id'] : null,
                'origem_recurso'      => $data['origem_recurso'] ?? null,
                'numero_termo_doacao' => $data['numero_termo_doacao'] ?? null,
                'numero_patrimonio'   => $data['numero_patrimonio'] ?? null,
                'nir'                 => $data['nir'] ?? null,
                'categoria'           => $data['categoria'] ?? 'Não classificado',
                'descricao_item'      => mb_strtoupper($data['descricao_item'], 'UTF-8'), // Salva a descrição sempre em MAIÚSCULO
                'marca_modelo'        => $data['marca_modelo'] ?? null,
                'quantidade'          => $quantidade,
                'valor_unitario'      => $valorUnitario,
                'valor_total'         => $valorTotal,
                'nota_fiscal'         => $data['nota_fiscal'] ?? null,
                'data_aquisicao'      => !empty($data['data_aquisicao']) ? $data['data_aquisicao'] : null,
                'local_guarda'        => $data['local_guarda'] ?? null,
                'status'              => $data['status'] ?? 'Ativo'
            ]);

            return [
                'status' => true,
                'message' => 'Bem patrimonial cadastrado com sucesso!'
            ];

        } catch (PDOException $e) {
            return [
                'status' => false,
                'message' => 'Erro ao salvar patrimônio: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Exclui um bem patrimonial pelo ID
     */
    public function delete(int $id): array
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM bens_patrimoniais WHERE id = ?");
            $stmt->execute([$id]);
            return ['status' => true, 'message' => 'Bem excluído com sucesso!'];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()];
        }
    }

    public function buscarUltimoStatus(int $idProcesso): ?object
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tramitacao_patrimonio WHERE processo_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$idProcesso]);
        $bemData = $stmt->fetch(PDO::FETCH_OBJ);
        return $bemData ?: null;
    }

    public function salvarTramitacao(array $dados): array
    {
        try {
            $sql = "INSERT INTO tramitacao_patrimonio (processo_id, status, observacoes, usuario_id) VALUES (:processo_id, :status, :observacoes, :usuario_id)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($dados);
            return ['status' => true, 'message' => 'Tramitação salva com sucesso!'];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Erro ao salvar tramitação: ' . $e->getMessage()];
        }
    }
}