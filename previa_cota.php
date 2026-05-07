<?php
ob_start();
session_start();
require_once __DIR__ . "/source/autoload.php";
use Source\Database\Connect;
use Source\Models\Processo;

// Trava de segurança: Se não vier nada do formulário anterior, volta pro início
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['pagamentos_selecionados'])) {
    header("Location: painel_ajuste.php?id=" . $_POST['ajuste_id'] . "&erro=Nenhum pagamento selecionado");
    exit();
}

$pagamentosIds = $_POST['pagamentos_selecionados'];
$ajusteId = $_POST['ajuste_id'] ?? 0;

$pdo = Connect::getInstance();
$processoModel = new Processo();

// 1. Busca os dados do Ajuste e da Instituição (Adapte os nomes das colunas conforme seu banco)
$stmtAjuste = $pdo->prepare("
    SELECT a.*, i.instituicao, c.banco, c.agencia, c.conta_corrente, c.fonte_recursos
    FROM ajustes a 
    JOIN instituicoes i ON a.instituicao_id = i.id 
    JOIN ajustes_contas c ON a.id = c.ajuste_id     
    WHERE a.id = ?
");
$stmtAjuste->execute([$ajusteId]);
$dadosAjuste = $stmtAjuste->fetch(PDO::FETCH_ASSOC);
$processoSb = $processoModel->formatarProcesso($processoModel->findById($dadosAjuste['processo_pagamento_id']));

// 2. Busca os Pagamentos selecionados
$placeholders = str_repeat('?,', count($pagamentosIds) - 1) . '?';
$stmtPag = $pdo->prepare("SELECT e.numero_empenho, p.data_pagamento, p.valor, p.descricao FROM ajustes_pagamentos p JOIN ajustes_empenhos e ON p.empenho_id = e.id WHERE p.id IN ($placeholders)");
$stmtPag->execute($pagamentosIds);
$listaPagamentos = $stmtPag->fetchAll(PDO::FETCH_ASSOC);

// Calcula o total geral
$valorTotal = 0;
foreach ($listaPagamentos as $pag) {
    $valorTotal += $pag['valor'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Prévia da Cota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
</head>
<body class="bg-light">
    <div class="container mt-5 max-w-75">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="lni lni-pencil-alt me-2"></i>Revisão e Dados Variáveis da Cota</h5>
                <a href="javascript:history.back()" class="btn btn-sm btn-outline-light">Voltar</a>
            </div>
            <div class="card-body">
                
                <div class="alert alert-info">
                    <strong>Instituição:</strong> <?= htmlspecialchars($dadosAjuste['instituicao'] ?? '') ?><br>
                    <strong>Processo:</strong> <?= htmlspecialchars($processoSb) ?><br>
                    <strong>Banco:</strong> <?= htmlspecialchars($dadosAjuste['banco'] ?? '') ?><br>
                    <strong>Agência:</strong> <?= htmlspecialchars($dadosAjuste['agencia'] ?? '') ?><br>
                    <strong>Conta:</strong> <?= htmlspecialchars($dadosAjuste['conta_corrente'] ?? '') ?><br>
                    <strong>Fonte de Recursos:</strong> <?= htmlspecialchars($dadosAjuste['fonte_recursos'] ?? '') ?><br>
                    <strong>Total a repassar:</strong> R$ <?= number_format($valorTotal, 2, ',', '.') ?> (<?= count($listaPagamentos) ?> empenhos selecionados)
                </div>

                <form action="cotaPagamento.php" method="POST">
                    
                    <input type="hidden" name="ajuste_id" value="<?= $ajusteId ?>">
                    <input type="hidden" name="processo_id" value="<?= $dadosAjuste['processo_pagamento_id'] ?? '' ?>">
                    <?php foreach ($pagamentosIds as $id): ?>
                        <input type="hidden" name="pagamentos_selecionados[]" value="<?= $id ?>">
                    <?php endforeach; ?>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Documentos Anexados</label>
                        <div class="card p-3 border">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="documentos[]" value="Nota(s) de Empenho" id="doc1">
                                <label class="form-check-label" for="doc1">Nota(s) de Empenho</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="documentos[]" value="CNDT - Certidão Negativa de Débitos Trabalhistas" id="doc2" checked>
                                <label class="form-check-label" for="doc2">CNDT - Certidão Negativa de Débitos Trabalhistas</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="documentos[]" value="CND - Certidão Negativa de Débitos Relativos aos Tributos Federais e à Dívida Ativa da União" id="doc3" checked>
                                <label class="form-check-label" for="doc3">CND - Certidão Negativa de Débitos Relativos aos Tributos Federais...</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="documentos[]" value="CRF - Certificado de Regularidade do FGTS" id="doc4" checked>
                                <label class="form-check-label" for="doc4">CRF - Certificado de Regularidade do FGTS</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="documentos[]" value="Certidão de Tributos Mobiliários" id="doc5">
                                <label class="form-check-label" for="doc5">Certidão de Tributos Mobiliários</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="documentos[]" value="Termo de Ciência e de Notificação" id="doc6">
                                <label class="form-check-label" for="doc6">Termo de Ciência e de Notificação</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="documentos[]" value="Termo de Colaboração nº <?= $dadosAjuste['numero_ajuste'] ?>" id="doc7">
                                <label class="form-check-label" for="doc7">Termo de Colaboração nº <?= $dadosAjuste['numero_ajuste'] ?></label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="documentos[]" value="Termo de Aditamento (Sétimo) nº 13/2026-SE" id="doc8">
                                <label class="form-check-label" for="doc8">Termo de Aditamento (Sétimo) nº 13/2026-SE</label>
                            </div>                            
                        </div>
                        <div class="form-text">Desmarque os documentos que não farão parte deste repasse.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Texto base do Aditamento/Colaboração</label>
                        <textarea name="texto_termo" class="form-control" rows="2" required><?= htmlspecialchars("Termo de Aditamento (Sétimo) nº 13/2026-SE ao Termo de Colaboração nº 52/2022-SE") ?></textarea>
                        <div class="form-text">Revise se o número do Termo está correto para esta cota.</div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="lni lni-printer me-2"></i> Gerar Documento Final
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</body>
</html>