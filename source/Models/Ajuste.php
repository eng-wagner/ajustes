<?php
namespace Source\Models;
require_once __DIR__ . "/../Helpers/Helpers.php"; // Onde está a sua função limparMoedaSQL()

use PDO;
use Source\Core\Model;
use DateTimeZone;
use DateTime;

class Ajuste extends Model {
    
    /**
     * Salva o Ajuste e suas Contas Bancárias vinculadas
     */
    public function salvarNovoAjuste(array $data): bool {
        try {
            $this->pdo->beginTransaction(); // Inicia a transação

            $stmt = $this->pdo->prepare("INSERT INTO ajustes (instituicao_id, processo_parceria_id, processo_pagamento_id, tipo_ajuste_id, numero_ajuste, objeto, data_assinatura, data_inicio, data_fim, valor_global_inicial) 
            VALUES (:instituicao_id, :processo_parceria_id, :processo_pagamento_id, :tipo_ajuste_id, :numero_ajuste, :objeto, :data_assinatura, :data_inicio, :data_fim, :valor_global_inicial)"); 
        
            $stmt->execute([
                'instituicao_id' => $data['instituicao_id'],
                'processo_parceria_id' => !empty($data['processo_parceria_id']) ? $data['processo_parceria_id'] : null,
                'processo_pagamento_id' => !empty($data['processo_pagamento_id']) ? $data['processo_pagamento_id'] : null,
                'tipo_ajuste_id' => $data['tipo_ajuste_id'],
                'numero_ajuste' => $data['numero_ajuste'],
                'objeto' => $data['objeto'],
                'data_assinatura'  => $data['data_assinatura'],
                'data_inicio'  => $data['data_inicio'],
                'data_fim' => $data['data_fim'],            
                'valor_global_inicial' => limparMoedaSQL($data['valor_global_inicial'])
            ]); 
                
            // Pega o ID do ajuste que acabou de ser criado no banco
            $ajusteId = $this->pdo->lastInsertId();

            //Recebe os arrays das contas bancárias
            $bancos         = $data['banco'] ?? [];
            $agencias       = $data['agencia'] ?? [];
            $contasCorrente = $data['conta_corrente'] ?? [];
            $fontesRecursos = $data['fonte_recursos'] ?? [];
            
            // Salva as contas bancárias na tabela 'ajustes_contas'
            if (!empty($bancos)) {
                $stmt = $this->pdo->prepare("INSERT INTO ajustes_contas (ajuste_id, banco, agencia, conta_corrente, fonte_recursos) 
                    VALUES (:ajuste_id, :banco, :agencia, :conta_corrente, :fonte_recursos)");

                foreach ($bancos as $indice => $nomeBanco) {
                    // Só adiciona se o nome do banco não estiver vazio
                    if (!empty($nomeBanco)) {
                        $stmt->execute([
                            'ajuste_id' => $ajusteId,
                            'banco' => $nomeBanco,
                            'agencia' => $agencias[$indice] ?? '',
                            'conta_corrente' => $contasCorrente[$indice] ?? '',
                            'fonte_recursos' => $fontesRecursos[$indice] ?? ''
                        ]);
                    }
                }
            }      

            // Confirma todas as operações no banco
            $this->pdo->commit();
            return true;
        } catch (\PDOException $e) {
            // Se der qualquer erro em qualquer insert, desfaz tudo
            $this->pdo->rollBack();
            
            return false;
        }        
    }

    /**
     * Atualiza os dados de um Ajuste existente e adiciona novas contas bancárias se informadas
     */
    public function atualizarAjuste(array $data): bool {
        $stmt = $this->pdo->prepare("UPDATE ajustes SET 
            processo_parceria_id = :processo_parceria_id, 
            processo_pagamento_id = :processo_pagamento_id, 
            tipo_ajuste_id = :tipo_ajuste_id, 
            numero_ajuste = :numero_ajuste, 
            objeto = :objeto, 
            data_assinatura = :data_assinatura, 
            data_inicio = :data_inicio, 
            data_fim = :data_fim, 
            valor_global_inicial = :valor_global_inicial
            WHERE id = :id"); 
        
        $stmt->execute([
            'id' => $data['id'],
            'processo_parceria_id' => !empty($data['processo_parceria_id']) ? $data['processo_parceria_id'] : null,
            'processo_pagamento_id' => !empty($data['processo_pagamento_id']) ? $data['processo_pagamento_id'] : null,
            'tipo_ajuste_id' => $data['tipo_ajuste_id'],
            'numero_ajuste' => $data['numero_ajuste'],
            'objeto' => $data['objeto'],
            'data_assinatura'  => $data['data_assinatura'],
            'data_inicio'  => $data['data_inicio'],
            'data_fim' => $data['data_fim'],            
            'valor_global_inicial' => limparMoedaSQL($data['valor_global_inicial'])
        ]); 

        // Verifica se novas contas bancárias foram enviadas no modal de edição
        if (isset($data['banco']) && !empty($data['banco'])) {
            $bancos         = $data['banco'];
            $agencias       = $data['agencia'];
            $contasCorrente = $data['conta_corrente'];
            $fontesRecursos = $data['fonte_recursos'];

            $stmtConta = $this->pdo->prepare("INSERT INTO ajustes_contas (ajuste_id, banco, agencia, conta_corrente, fonte_recursos) 
                VALUES (:ajuste_id, :banco, :agencia, :conta_corrente, :fonte_recursos)");

            foreach ($bancos as $indice => $nomeBanco) {
                if (!empty($nomeBanco)) {
                    $stmtConta->execute([
                        'ajuste_id' => $data['id'],
                        'banco' => $nomeBanco,
                        'agencia' => $agencias[$indice],
                        'conta_corrente' => $contasCorrente[$indice],
                        'fonte_recursos' => $fontesRecursos[$indice]
                    ]);
                }
            }
        }        
        return true;
    }

    public function listarTodos(): array
    {        
        $stmt = $this->pdo->query("SELECT 
            a.id,
            a.numero_ajuste,
            a.data_inicio,
            a.data_fim,
            a.valor_global_inicial,
            a.status,
            i.instituicao AS nome_instituicao,
            t.tipo_ajuste AS tipo_ajuste
            FROM ajustes a
            INNER JOIN instituicoes i ON a.instituicao_id = i.id
            INNER JOIN tipo_ajuste t ON a.tipo_ajuste_id = t.id
            ORDER BY a.id DESC");                
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll() ?: [];        
    }

    /**
     * Busca os dados de UM ajuste específico pelo ID
     */
    public function buscarPorId(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT 
                    a.*, 
                    i.instituicao AS nome_instituicao, 
                    t.tipo_ajuste AS tipo_ajuste
                FROM ajustes a
                INNER JOIN instituicoes i ON a.instituicao_id = i.id
                INNER JOIN tipo_ajuste t ON a.tipo_ajuste_id = t.id
                WHERE a.id = :id");        
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        return $stmt->fetch() ?: [];        
        
        // Retorna apenas uma linha (fetch) ou falso se não achar         
    }

    /**
     * Busca todas as contas bancárias de um Ajuste
     */
    public function buscarContasPorAjuste(int $ajusteId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM ajustes_contas WHERE ajuste_id = :ajuste_id");        
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(['ajuste_id' => $ajusteId]);        
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Salva um novo Empenho vinculado a um Ajuste
     */
    public function salvarEmpenho(array $data): array 
    {        
        $valorEmpenho = limparMoedaSQL($data['valor']);
        $ajusteId = $data['ajuste_id'];

        $sqlSaldo = "SELECT 
            a.valor_global_inicial, 
            (SELECT COALESCE(SUM(valor_aditivo), 0) FROM ajustes_aditivos WHERE ajuste_id = a.id) as total_aditivos,
            (SELECT COALESCE(SUM(valor), 0) FROM ajustes_empenhos WHERE ajuste_id = a.id) as total_empenhos
            FROM ajustes a
            WHERE a.id = :ajuste_id";
        
        $stmtSaldo = $this->pdo->prepare($sqlSaldo);
        $stmtSaldo->execute(['ajuste_id' => $ajusteId]);
        $resultado = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

        if(!$resultado) {
            return ['status' => false, 'message' => "Erro: Ajuste não encontrado no banco de dados."];
        }

        // Faz a matemática para calcular o saldo disponível para novos empenhos
        $valorInicial = $resultado['valor_global_inicial'];
        $totalAditivos = $resultado['total_aditivos'];
        $totalEmpenhos = $resultado['total_empenhos'];

        $saldoDisponivel = ($valorInicial + $totalAditivos) - $totalEmpenhos;

        // 2. Bloquear se o valor do empenho for maior que o saldo disponível (com margem de 0.01 para dízimas)
        if ($valorEmpenho > ($saldoDisponivel + 0.01)) {
            return [
                'status' => false,
                'message' => "Erro: O valor do empenho (R$ " . number_format($valorEmpenho, 2, ',', '.') . ") excede o Saldo do Ajuste disponível (R$ " . number_format($saldoDisponivel, 2, ',', '.') . ")."
            ]; // Indica que o empenho não foi salvo por falta de saldo
        }

        // 3. Se passou na validação, salva o empenho normalmente

        $stmt = $this->pdo->prepare("INSERT INTO ajustes_empenhos 
            (ajuste_id, data_empenho, numero_empenho, descricao, valor) 
            VALUES 
            (:ajuste_id, :data_empenho, :numero_empenho, :descricao, :valor)");
        
        $stmt->execute([
            'ajuste_id' => $ajusteId,
            'data_empenho' => $data['data_empenho'],
            'numero_empenho' => $data['numero_empenho'],
            'descricao' => $data['descricao'],
            'valor' => $valorEmpenho
        ]);

        return ['status' => true, 'message' => "Empenho registrado com sucesso."];        
    }

    public function listarEmpenhosPorAjuste(int $id): array
    {        
        $stmt = $this->pdo->prepare("SELECT * FROM ajustes_empenhos WHERE ajuste_id = :ajuste_id ORDER BY data_empenho DESC, id DESC");            
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(['ajuste_id' => $id]);
        return $stmt->fetchAll() ?: [];        
    }

    public function getEmpenhosComSaldo(int $ajuste_id): array 
    {
        // Esta query calcula a soma dos pagamentos e só retorna empenhos onde o saldo é > 0
        $sql = "SELECT e.*, 
                (e.valor - COALESCE(SUM(p.valor), 0)) as saldo_disponivel
                FROM ajustes_empenhos e
                LEFT JOIN ajustes_pagamentos p ON e.id = p.empenho_id
                WHERE e.ajuste_id = :ajuste_id
                GROUP BY e.id
                HAVING saldo_disponivel > 0";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ajuste_id' => $ajuste_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvarPagamento(array $data): array 
    {
        $valorPagamento = limparMoedaSQL($data['valor']);
        $empenhoId = $data['empenho_id'];
        
        // 1. Verificar o saldo real do empenho no banco agora
        $sqlSaldo = "SELECT (e.valor - COALESCE(SUM(p.valor), 0)) as saldo
                 FROM ajustes_empenhos e
                 LEFT JOIN ajustes_pagamentos p ON e.id = p.empenho_id
                 WHERE e.id = :empenho_id
                 GROUP BY e.id";
        
        $stmtSaldo = $this->pdo->prepare($sqlSaldo);
        $stmtSaldo->execute(['empenho_id' => $empenhoId]);
        $resultado = $stmtSaldo->fetch(PDO::FETCH_ASSOC);
        $saldoAtual = $resultado['saldo'] ?? 0;

        // 2. Bloquear se o pagamento for maior que o saldo (com margem de 0.01 para dízimas)
        if ($valorPagamento > ($saldoAtual + 0.01)) { // Adiciona uma pequena margem para evitar problemas de arredondamento
            return [
                'status' => false,
                'message' => "Erro: O valor (R$ " . number_format($valorPagamento, 2, ',', '.') . ") excede o saldo disponível do empenho (R$ " . number_format($saldoAtual, 2, ',', '.') . ")."
            ]; // Indica que o pagamento não foi salvo por falta de saldo
        }

        // 3. Se passou na validação, salva o pagamento normalmente
        $stmt = $this->pdo->prepare("INSERT INTO ajustes_pagamentos 
            (ajuste_id, empenho_id, data_pagamento, valor, descricao) 
            VALUES 
            (:ajuste_id, :empenho_id, :data_pagamento, :valor, :descricao)");
        
        $stmt->execute([
            'ajuste_id' => $data['ajuste_id'],
            'empenho_id' => $empenhoId,
            'data_pagamento' => $data['data_pagamento'],
            'valor' => $valorPagamento,
            'descricao' => $data['descricao'] ?? null
        ]);

        return [
                'status' => true,
                'message' => "Pagamento registrado com sucesso."
            ]; // Indica que o pagamento foi salvo com sucesso        
    }

    public function listarPagamentosPorAjuste(int $id): array
    {        
        $stmt = $this->pdo->prepare("SELECT p.*, e.numero_empenho 
        FROM ajustes_pagamentos p 
        LEFT JOIN ajustes_empenhos e ON p.empenho_id = e.id
        WHERE p.ajuste_id = :ajuste_id 
        ORDER BY p.data_pagamento DESC, p.id DESC");            
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(['ajuste_id' => $id]);
        return $stmt->fetchAll() ?: [];        
    }

    /**
     * Salva um Termo Aditivo / Apostilamento
     */
    public function salvarAditivo(array $data): bool 
    {        
        // 1. Limpa a formatação da moeda (tira pontos e troca vírgula por ponto)
        $valorAditivo = !empty($data['valor_aditivo']) ? limparMoedaSQL($data['valor_aditivo']) : 0;

        // 2. A MÁGICA: Se o usuário marcou "Supressão" (-), transforma o valor em negativo
        if (isset($data['operacao_valor']) && $data['operacao_valor'] === '-') {
            $valorAditivo = -$valorAditivo;
        }

        $stmt = $this->pdo->prepare("INSERT INTO ajustes_aditivos 
            (ajuste_id, sequencia, numero, tipo_aditivo, data_assinatura, nova_data_fim, valor_aditivo, justificativa) 
            VALUES 
            (:ajuste_id, :sequencia, :numero, :tipo_aditivo, :data_assinatura, :nova_data_fim, :valor_aditivo, :justificativa)");
        
        $stmt->execute([
            'ajuste_id'       => $data['ajuste_id'],
            'sequencia'       => $data['sequencia'],
            'numero'          => $data['numero'],
            'tipo_aditivo'    => $data['tipo_aditivo'],
            'data_assinatura' => $data['data_assinatura'],
            'nova_data_fim'   => !empty($data['nova_data_fim']) ? $data['nova_data_fim'] : null,
            'valor_aditivo'   => $valorAditivo,
            'justificativa'   => !empty($data['justificativa']) ? $data['justificativa'] : null
        ]);

        return true;        
    }

    public function listarAditivosPorAjuste(int $id): array
    {        
        $stmt = $this->pdo->prepare("SELECT * FROM ajustes_aditivos WHERE ajuste_id = :ajuste_id ORDER BY id ASC");            
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(['ajuste_id' => $id]);
        return $stmt->fetchAll() ?: [];
    }

    public function listarTodosAditivos(): array
    {        
        $stmt = $this->pdo->query("SELECT 
            ad.*, 
            a.numero_ajuste, 
            t.tipo_ajuste 
            FROM ajustes_aditivos ad
            INNER JOIN ajustes a ON ad.ajuste_id = a.id
            INNER JOIN tipo_ajuste t ON a.tipo_ajuste_id = t.id
            ORDER BY ad.id DESC");            
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll() ?: [];
    }

    public function excluirEmpenho(int $id): array {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM ajustes_pagamentos WHERE empenho_id = :id");
        $stmt->execute(['id' => $id]);        

        if ($stmt->fetchColumn() > 0) {
            return [
                'status' => false, 
                'message' => "ERRO: Não é possível excluir o Empenho pois existem Pagamentos associados a ele."
            ]; // Indica que o empenho não foi excluído por ter pagamentos vinculados
        }

        $stmt = $this->pdo->prepare("DELETE FROM ajustes_empenhos WHERE id = :id");
        $sucesso = $stmt->execute(['id' => $id]);
        
        return [
            'status' => $sucesso, 
            'message' => $sucesso ? "Empenho excluído com sucesso." : "Erro ao tentar excluir o Empenho."
        ]; // Indica que o empenho foi excluído com sucesso
    }

    public function excluirPagamento(int $id): array {
        $stmt = $this->pdo->prepare("DELETE FROM ajustes_pagamentos WHERE id = :id");
        $sucesso = $stmt->execute(['id' => $id]);
        return [
            'status' => $sucesso,
            'message' => $sucesso ? "Pagamento excluído com sucesso." : "Erro ao tentar excluir o Pagamento."
        ];
    }

    public function excluirAditivo(int $id): array {
        $stmt = $this->pdo->prepare("DELETE FROM ajustes_aditivos WHERE id = :id");
        $sucesso = $stmt->execute(['id' => $id]);
        return [
            'status' => $sucesso,
            'message' => $sucesso ? "Aditivo excluído com sucesso." : "Erro ao tentar excluir o Aditivo."
        ];
    }
}