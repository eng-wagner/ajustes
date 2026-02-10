<?php

namespace Source\Models;

use Source\Core\Model;
use PDO;

class RelatorioPDDE extends Model
{   
    /**
     * Busca dados com filtros opcionais de Programa e Status
     */

    public function buscarDadosGerais(?string $programa = null, ?int $statusId = null): array
    {
        $query = "
            SELECT 
                p.id as proc_id, p.orgao, p.numero, p.ano, p.digito, p.tipo,
                i.instituicao,
                a.status_id, a.data_ent, a.s_movimento, a.data_analise_ex, a.data_enc_af, a.data_analise_fin, a.data_sigpc,
                st.status_pc as status_nome,
                ue.nome as usuario_ex_nome,
                uf.nome as usuario_fin_nome
            FROM processos p
            JOIN instituicoes i ON p.instituicao_id = i.id
            LEFT JOIN analise_pdde_25 a ON p.id = a.proc_id
            LEFT JOIN status_processo st ON a.status_id = st.id
            LEFT JOIN usuarios ue ON a.usuario_ex_id = ue.id
            LEFT JOIN usuarios uf ON a.usuario_fin_id = uf.id
            WHERE 1=1
        ";

        $params = [];

        if(!empty($programa) && $programa != '0') {
            $query .= " AND p.tipo = :programa";
            $params['programa'] = $programa;
        } else {
            $query .= " AND p.tipo LIKE '%PDDE%'";
        }

        if($statusId == -1) {
            $query .= " AND (a.status_id IS NULL OR a.status_id = 0)";
        } elseif(!empty($statusId) && $statusId > 0) {
            $query .= " AND a.status_id = :statusId";
            $params['statusId'] = $statusId;
        }
        
        $query .= " ORDER BY i.instituicao ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }
        
    /**
     * Lista todos os status disponíveis
     */
    public function listarStatus(): array
    {
        $stmt = $this->pdo->query("SELECT id, status_pc FROM status_processo ORDER BY status_pc ASC");
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * Ajuda a formatar o número do processo visualmente
     */
    public function formatarProcesso($p): string
    {
        return "{$p->orgao}.{$p->numero}/{$p->ano}-{$p->digito}";
    }
}