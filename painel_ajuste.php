<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";
require_once __DIR__ . "/source/Helpers/Helpers.php";

date_default_timezone_set("America/Sao_Paulo");

use Source\Models\Ajuste;
use Source\Models\User;
use Source\Models\Logs;
use Source\Models\Processo;

$userModel = new User();

if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}

$loggedUser = $userModel->findById($_SESSION['user_id']);
if ($loggedUser) {
    $userName = $loggedUser->nome;
    $firstName = substr($userName, 0, strpos($userName, " "));
    $perfil = $loggedUser->perfil;
} else {    
    session_destroy();
    header("Location: index.php?status=sessao_invalida");
    exit();
}

$idAjuste = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idAjuste) {
    redirecionar('dashboard_ajustes.php','erro','ID Inválido');    
    exit;
}

$ajusteModel = new Ajuste();
$logModel = new Logs();
$processoModel = new Processo();

$ajuste = $ajusteModel->buscarPorId($idAjuste);

if (!$ajuste) {
    redirecionar('dashboard_ajustes.php','erro',"Ajuste não encontrado");    
    exit;
}

$contasBancarias = $ajusteModel->buscarContasPorAjuste($idAjuste);
$empenhos = $ajusteModel->listarEmpenhosPorAjuste($idAjuste);
$pagamentos = $ajusteModel->listarPagamentosPorAjuste($idAjuste);
$aditivos = $ajusteModel->listarAditivosPorAjuste($idAjuste);
$listaProcessos = $processoModel->findByInstId($ajuste['instituicao_id']);

if (isset($_REQUEST['salvarEmpenho']) && $_SERVER['REQUEST_METHOD'] === 'POST') 
{   
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                            
    $resultado = $ajusteModel->salvarEmpenho($_POST);
    
    if ($resultado['status']) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Registrou o empenho " . $postData['numero_empenho'] . " no ajuste ID " . $postData['ajuste_id']
        ]);        
        redirecionar("painel_ajuste.php?id=" . $postData['ajuste_id'], 'sucesso', $resultado['message']);        
    } else {
        redirecionar("painel_ajuste.php?id=" . $postData['ajuste_id'], 'erro', $resultado['message']);            
    }   
}

if (isset($_REQUEST['salvarPagamento']) && $_SERVER['REQUEST_METHOD'] === 'POST') 
{   
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                            
    $resultado = $ajusteModel->salvarPagamento($postData);

    if ($resultado['status']) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Registrou um pagamento de R$ " . $postData['valor'] . " no ajuste ID " . $postData['ajuste_id']
        ]);        
        redirecionar("painel_ajuste.php?id=" . $postData['ajuste_id'], 'sucesso', $resultado['message']);        
    } else {
        redirecionar("painel_ajuste.php?id=" . $postData['ajuste_id'], 'erro', $resultado['message']);            
    }   
}

if (isset($_REQUEST['salvarAditivo']) && $_SERVER['REQUEST_METHOD'] === 'POST') 
{   
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                            

    if ($ajusteModel->salvarAditivo($postData)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Registrou o aditivo " . $postData['numero'] . " no ajuste ID " . $postData['ajuste_id']
        ]);        
        redirecionar("painel_ajuste.php?id=" . $postData['ajuste_id'], 'sucesso', "Aditivo registrado com sucesso!");        
    } else {
        redirecionar("painel_ajuste.php?id=" . $postData['ajuste_id'], 'erro', "Erro ao registrar o aditivo!");            
    }   
}

if (isset($_REQUEST['editarAjuste']) && $_SERVER['REQUEST_METHOD'] === 'POST') 
{   
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                            

    if ($ajusteModel->atualizarAjuste($postData)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Editou os dados do ajuste ID " . $postData['id']
        ]);        
        redirecionar("painel_ajuste.php?id=" . $postData['id'], 'sucesso', "Ajuste atualizado com sucesso!");        
    } else {
        redirecionar("painel_ajuste.php?id=" . $postData['id'], 'erro', "Erro ao atualizar os dados!");            
    }   
}

// Intercepta a exclusão de registros
if (isset($_GET['excluir']) && isset($_GET['id_registro'])) {
    $tipoExclusao = filter_input(INPUT_GET, 'excluir', FILTER_DEFAULT);
    $idRegistro = filter_input(INPUT_GET, 'id_registro', FILTER_VALIDATE_INT);
    
    $resultado = ['status' => false, 'message' => "Erro ao tentar excluir o registro!"];
    

    if ($tipoExclusao === 'empenho') {
        $resultado = $ajusteModel->excluirEmpenho($idRegistro);       
    } elseif ($tipoExclusao === 'pagamento') {
        $resultado = $ajusteModel->excluirPagamento($idRegistro);
    } elseif ($tipoExclusao === 'aditivo') {
        $resultado = $ajusteModel->excluirAditivo($idRegistro);
    }

    if ($resultado['status']) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Excluiu um(a) {$tipoExclusao} (ID: {$idRegistro}) do ajuste ID {$idAjuste}"
        ]);
        redirecionar("painel_ajuste.php?id=" . $idAjuste, 'sucesso', $resultado['message']);
    } else {
        redirecionar("painel_ajuste.php?id=" . $idAjuste, 'erro', $resultado['message']);
    }
}

// =========================================================================================
// --- CÁLCULOS DINÂMICOS PARA OS CARDS VISUAIS ---
// =========================================================================================
$totalEmpenhado = is_array($empenhos) ? array_sum(array_column($empenhos, 'valor')) : 0;
$totalPago = is_array($pagamentos) ? array_sum(array_column($pagamentos, 'valor')) : 0;
$valorGlobal = (float)$ajuste['valor_global_inicial'];
if (is_array($aditivos)) {
    foreach ($aditivos as $aditivo) {
        $valorGlobal += (float)$aditivo['valor_aditivo'];
    }
}

$saldoAEmpenhar = $valorGlobal - $totalEmpenhado;
$saldoAPagar = $totalEmpenhado - $totalPago;

$percEmpenhado = ($valorGlobal > 0) ? ($totalEmpenhado / $valorGlobal) * 100 : 0;
$percPago = ($totalEmpenhado > 0) ? ($totalPago / $totalEmpenhado) * 100 : 0;
// Garantir que não passe de 100% visualmente nas barras
$percEmpenhado = min($percEmpenhado, 100);
$percPago = min($percPago, 100);
// =========================================================================================

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <title>Painel de Ajustes</title>
    
    <style>
        /* VISUAL PREMIUM DASHBOARD */
        body {
            background-color: #f4f7fa; 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .page-title {
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #2b3035;
        }

        /* Cards flutuantes e modernos */
        .dashboard-card {
            border: none !important;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(149, 157, 165, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(149, 157, 165, 0.15);
        }

        /* Ícones com fundo circular suave (Avatar style) */
        .icon-shape {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        /* Barras de progresso mais finas e elegantes */
        .progress-thin {
            height: 6px;
            border-radius: 10px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .progress-thin .progress-bar {
            border-radius: 10px;
        }

        .text-overline {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #8392a5;
        }
        
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.015); }
    </style>
</head>

<body>    
    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1 class="page-title mb-4">
                    Painel de Ajustes
                </h1>
            </div>
            <div class="container-fluid px-4 py-4">    
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <div>
                        <div class="d-flex align-items-center">
                            <h2 class="mb-0 text-dark fw-bold"><i class="lni lni-document text-primary me-2"></i>Ajuste nº <?= htmlspecialchars($ajuste['numero_ajuste']) ?></h2>
                            <button class="btn btn-sm btn-outline-secondary ms-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalEditarAjuste">
                                <i class="lni lni-pencil"></i> Editar
                            </button>
                        </div>
                        <h5 class="text-secondary mt-2 fw-normal">
                            <span class="badge bg-primary me-2"><?= htmlspecialchars($ajuste['tipo_ajuste']) ?></span> 
                            <?= htmlspecialchars($ajuste['nome_instituicao']) ?>
                        </h5>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success fs-6 mb-2"><?= htmlspecialchars($ajuste['status']) ?></span><br>
                        <small class="text-muted fw-medium">Vigência: <?= date('d/m/Y', strtotime($ajuste['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($ajuste['data_fim'])) ?></small>
                    </div>
                </div>

                <div class="row mb-4 g-4"> 
                    <div class="col-md-4">
                        <div class="card dashboard-card h-100 p-2">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-overline">Valor Global Atualizado</span>
                                    <div class="icon-shape bg-primary bg-opacity-10 text-primary fs-4">
                                        <i class="lni lni-wallet"></i>
                                    </div>
                                </div>
                                <h3 class="fw-bold text-dark mb-1">R$ <?= number_format($valorGlobal, 2, ',', '.') ?></h3>
                                <small class="text-muted fw-medium">Inicial: R$ <?= number_format($ajuste['valor_global_inicial'], 2, ',', '.') ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card dashboard-card h-100 p-2">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-overline">Total Empenhado</span>
                                    <div class="icon-shape bg-warning bg-opacity-10 text-warning fs-4">
                                        <i class="lni lni-layers"></i>
                                    </div>
                                </div>
                                <h3 class="fw-bold text-dark mb-2">R$ <?= number_format($totalEmpenhado, 2, ',', '.') ?></h3>
                                <div class="progress progress-thin mb-2">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= round($percEmpenhado) ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted fw-medium">Saldo a empenhar:</small>
                                    <small class="text-dark fw-bold">R$ <?= number_format($saldoAEmpenhar, 2, ',', '.') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card dashboard-card h-100 p-2">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-overline">Total Pago</span>
                                    <div class="icon-shape bg-success bg-opacity-10 text-success fs-4">
                                        <i class="lni lni-checkmark-circle"></i>
                                    </div>
                                </div>
                                <h3 class="fw-bold text-dark mb-2">R$ <?= number_format($totalPago, 2, ',', '.') ?></h3>
                                <div class="progress progress-thin mb-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= round($percPago) ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted fw-medium">A pagar (empenhos):</small>
                                    <small class="text-dark fw-bold">R$ <?= number_format($saldoAPagar, 2, ',', '.') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <?php if (empty($contasBancarias)): ?>
                        <div class="col-12">
                            <div class="alert alert-light border shadow-sm text-muted">
                                <i class="lni lni-warning text-warning me-2"></i>Nenhuma conta bancária vinculada a este ajuste ainda.
                            </div>
                        </div>
                    <?php else: ?>                        
                        <?php foreach ($contasBancarias as $conta): ?>
                            <div class="col-md-6 mb-3"> 
                                <div class="card dashboard-card">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="icon-shape bg-secondary bg-opacity-10 text-secondary fs-4 me-3">
                                            <i class="lni lni-building"></i>
                                        </div>
                                        <div>
                                            <h6 class="card-title text-dark fw-bold mb-1">
                                                <?= htmlspecialchars($conta['banco']) ?>
                                            </h6>
                                            <div class="text-muted small">
                                                <span class="me-3"><strong>Ag:</strong> <?= htmlspecialchars($conta['agencia']) ?></span>
                                                <span class="me-3"><strong>Cc:</strong> <?= htmlspecialchars($conta['conta_corrente']) ?></span>
                                                <span><strong>Fonte:</strong> <?= htmlspecialchars($conta['fonte_recursos']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card dashboard-card mb-5">
                    <div class="card-header bg-white pt-3 pb-0 border-bottom-0">
                        <ul class="nav nav-tabs border-bottom-0" id="meusTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active text-dark fw-bold" data-bs-toggle="tab" data-bs-target="#empenhos">
                                    <i class="lni lni-bookmark"></i> Empenhos
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link text-dark fw-bold" data-bs-toggle="tab" data-bs-target="#pagamentos">
                                    <i class="lni lni-coin"></i> Pagamentos
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link text-dark fw-bold" data-bs-toggle="tab" data-bs-target="#aditivos">
                                    <i class="lni lni-circle-plus"></i> Aditamentos
                                </button>
                            </li>
                        </ul>
                    </div>
        
                    <div class="card-body p-0">
                        <div class="tab-content p-4">                            
                            
                            <div class="tab-pane fade show active" id="empenhos">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0 text-dark fw-bold">Relação de Notas de Empenho (NE)</h5>
                                    <button class="btn btn-sm btn-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoEmpenho">
                                        <i class="lni lni-plus me-1"></i> Novo Empenho
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle border">
                                        <thead class="table-light text-muted small text-uppercase">
                                            <tr>
                                                <th>Data</th>
                                                <th>Nº do Empenho</th>
                                                <th>Descrição/Referência</th>
                                                <th class="text-end">Valor (R$)</th>
                                                <th class="text-end">Saldo (R$)</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>                                            
                                            <?php foreach ($empenhos as $empenho): ?>
                                                <?php 
                                                    // Lógica de saldo: Valor do Empenho - Soma dos pagamentos atrelados a ele
                                                    $pagamentosDesteEmpenho = array_filter((array)$pagamentos, function($p) use ($empenho) {
                                                        return $p['empenho_id'] == $empenho['id'];
                                                    });
                                                    $totalPagoDesteEmpenho = array_sum(array_column($pagamentosDesteEmpenho, 'valor'));
                                                    $saldoDoEmpenho = $empenho['valor'] - $totalPagoDesteEmpenho;
                                                ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <?= date('d/m/Y', strtotime($empenho['data_empenho'])) ?>
                                                    </td>
                                                    <td class="align-middle fw-bold text-dark">
                                                        <?= htmlspecialchars($empenho['numero_empenho']) ?>
                                                    </td>
                                                    <td class="align-middle text-muted">
                                                        <?= htmlspecialchars($empenho['descricao']) ?>
                                                    </td>
                                                    <td class="align-middle text-end fw-semibold text-dark">
                                                        R$ <?= number_format($empenho['valor'], 2, ',', '.') ?>
                                                    </td>
                                                    <td class="align-middle text-end">
                                                        <?php if ($saldoDoEmpenho <= 0): ?>
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">Quitado</span>
                                                        <?php else: ?>
                                                            <span class="text-danger fw-semibold">R$ <?= number_format($saldoDoEmpenho, 2, ',', '.') ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Editar"><i class="lni lni-pencil"></i></button>
                                                            <button class="btn btn-outline-danger btn-excluir" data-id="<?= $empenho['id'] ?>" data-tipo="empenho" title="Excluir"><i class="lni lni-trash-can"></i></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>                                                                                     
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="pagamentos">
                                <form action="cotaPagamento.php" method="POST" id="formGerarCota">
                                    <input type="hidden" name="ajuste_id" value="<?= htmlspecialchars($ajuste['id'] ?? '') ?>">

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0 text-dark fw-bold">Pagamentos Registrados</h5>
                                        <button class="btn btn-sm btn-success px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoPagamento">
                                            <i class="lni lni-plus me-1"></i> Novo Pagamento
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle border">
                                            <thead class="table-light text-muted small text-uppercase">
                                                <tr>
                                                    <th>Data</th>
                                                    <th>Empenho Vinculado</th>
                                                    <th>Descrição</th>
                                                    <th class="text-end">Valor Pago (R$)</th>
                                                    <th class="text-center">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>                                                              
                                                <?php foreach ($pagamentos as $pagamento): ?>
                                                    <tr>
                                                        <td class="align-middle">
                                                            <?= date('d/m/Y', strtotime($pagamento['data_pagamento'])) ?>
                                                        </td>
                                                        <td class="align-middle fw-bold text-dark">
                                                            NE <?= htmlspecialchars($pagamento['numero_empenho'] ?? 'N/D') ?>
                                                        </td>
                                                        <td class="align-middle text-muted">
                                                            <?= htmlspecialchars($pagamento['descricao']) ?>
                                                        </td>
                                                        <td class="align-middle text-end fw-semibold text-success">
                                                            + R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <div class="btn-group btn-group-sm">
                                                                <button class="btn btn-outline-secondary" title="Editar"><i class="lni lni-pencil"></i></button>
                                                                <button class="btn btn-outline-danger btn-excluir" data-id="<?= $pagamento['id'] ?>" data-tipo="pagamento" title="Excluir"><i class="lni lni-trash-can"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>                                                                                                                                                                                  
                                            </tbody>
                                        </table>
                                    </div>

                                </form>
                                
                            </div>  

                            <div class="tab-pane fade" id="aditivos" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="fw-bold text-dark mb-0">Termos Aditivos e Apostilamentos</h5>
                                    <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalNovoAditivo">
                                        <i class="lni lni-plus me-1"></i> Novo Aditivo
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle" style="width:100%">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 small fw-bold text-muted text-center">NÚMERO</th>
                                                <th class="border-0 small fw-bold text-muted text-center">SEQUÊNCIA</th>
                                                <th class="border-0 small fw-bold text-muted">DATA ASSIN.</th>
                                                <th class="border-0 small fw-bold text-muted">TIPO</th>
                                                <th class="border-0 small fw-bold text-muted">JUSTIFICATIVA</th>
                                                <th class="border-0 small fw-bold text-muted text-center">NOVA VIGÊNCIA</th>
                                                <th class="border-0 small fw-bold text-muted text-end">VALOR (+/-)</th>
                                                <th class="border-0 small fw-bold text-muted text-center">AÇÕES</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($aditivos)): ?>
                                                <?php foreach ($aditivos as $aditivo): ?>
                                                <tr>
                                                    <td class="small fw-medium text-dark text-nowrap">
                                                        <?= htmlspecialchars($aditivo['numero']) ?>
                                                    </td>

                                                    <td class="small fw-medium text-dark text-nowrap">
                                                        <?= htmlspecialchars($aditivo['sequencia']) ?>
                                                    </td>
                                                    
                                                    <td class="small"><?= date('d/m/Y', strtotime($aditivo['data_assinatura'])) ?></td>
                                                    
                                                    <td>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                                            <?= $aditivo['tipo_aditivo'] ?>
                                                        </span>
                                                    </td>

                                                    <td class="small text-muted">
                                                        <?= !empty($aditivo['justificativa']) ? htmlspecialchars($aditivo['justificativa']) : '<span class="text-muted">-</span>' ?>
                                                    </td>
                                                    
                                                    <td class="text-center fw-medium">
                                                        <?= !empty($aditivo['nova_data_fim']) ? date('d/m/Y', strtotime($aditivo['nova_data_fim'])) : '<span class="text-muted">-</span>' ?>
                                                    </td>
                                                    
                                                    <td class="text-end fw-bold">
                                                        <?php 
                                                            if ($aditivo['valor_aditivo'] > 0) echo '<span class="text-success">R$ '.number_format($aditivo['valor_aditivo'], 2, ',', '.').'</span>';
                                                            elseif ($aditivo['valor_aditivo'] < 0) echo '<span class="text-danger">R$ '.number_format(abs($aditivo['valor_aditivo']), 2, ',', '.').'</span>';
                                                            else echo '<span class="text-muted">R$ 0,00</span>';
                                                        ?>
                                                    </td>
                                                    
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-secondary" title="Editar"><i class="lni lni-pencil"></i></button>
                                                        <button class="btn btn-sm btn-outline-danger btn-excluir" data-id="<?= $aditivo['id'] ?>" data-tipo="aditivo" title="Excluir">
                                                            <i class="lni lni-trash-can"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="modalEditarAjuste" tabindex="-1" aria-labelledby="modalEditarAjusteLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <form action="?id=<?= $idAjuste ?>&editarAjuste=true" method="POST" class="modal-content border-0 shadow-lg rounded-4">
                        
                        <input type="hidden" name="id" value="<?= $idAjuste ?>">
                        <input type="hidden" name="instituicao_id" value="<?= htmlspecialchars($ajuste['instituicao_id'] ?? '') ?>"> <div class="modal-header border-bottom px-4 py-3">
                            <h5 class="modal-title fw-bold text-dark" id="modalEditarAjusteLabel">
                                <div class="d-inline-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-circle me-2" style="width: 32px; height: 32px;">
                                    <i class="lni lni-pencil fs-6"></i>
                                </div>
                                Editar Dados do Ajuste
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="modal-body p-4 bg-light">
                            
                            <div class="card dashboard-card mb-4">
                                <div class="card-body p-4">
                                    <h6 class="card-title fw-bold text-dark mb-4">Dados do Instrumento</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label text-muted fw-semibold small">Nº do Ajuste / Ano</label>
                                            <input type="text" class="form-control" name="numero_ajuste" value="<?= htmlspecialchars($ajuste['numero_ajuste'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label text-muted fw-semibold small">Tipo de Ajuste</label>
                                            <select class="form-select" name="tipo_ajuste_id" required>
                                                <option value="1" <?= ($ajuste['tipo_ajuste_id'] == 1) ? 'selected' : '' ?>>Termo de Colaboração</option>
                                                <option value="2" <?= ($ajuste['tipo_ajuste_id'] == 2) ? 'selected' : '' ?>>Termo de Fomento</option>
                                                <option value="3" <?= ($ajuste['tipo_ajuste_id'] == 3) ? 'selected' : '' ?>>Acordo de Cooperação</option>
                                                <option value="4" <?= ($ajuste['tipo_ajuste_id'] == 4) ? 'selected' : '' ?>>Termo de Doação</option>
                                                <option value="5" <?= ($ajuste['tipo_ajuste_id'] == 5) ? 'selected' : '' ?>>Termo de Cooperação Técnica</option>                                            
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label text-muted fw-semibold small">Valor Global Inicial</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white text-muted">R$</span>
                                                <input type="text" class="form-control mascara-moeda border-start-0 ps-0" name="valor_global_inicial" value="<?= number_format($ajuste['valor_global_inicial'] ?? 0, 2, ',', '.') ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">                                    
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label text-muted fw-semibold small">Data da Assinatura</label>
                                            <input type="date" class="form-control" name="data_assinatura" value="<?= htmlspecialchars($ajuste['data_assinatura'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label text-muted fw-semibold small">Data de Início</label>
                                            <input type="date" class="form-control" name="data_inicio" value="<?= htmlspecialchars($ajuste['data_inicio'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label text-muted fw-semibold small">Data de Fim (Vigência)</label>
                                            <input type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars($ajuste['data_fim'] ?? '') ?>" required>
                                        </div>                                    
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <label class="form-label text-muted fw-semibold small">Objeto do Ajuste (Resumo)</label>
                                            <textarea class="form-control" name="objeto" rows="2" required><?= htmlspecialchars($ajuste['objeto'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card dashboard-card mb-2">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="icon-shape bg-secondary bg-opacity-10 text-secondary me-3">
                                            <i class="lni lni-building fs-5"></i>
                                        </div>
                                        <h6 class="card-title fw-bold text-dark mb-0">Processos e Contas Vinculadas</h6>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-semibold small">Processo de Parceria (Mãe)</label>
                                            <select class="form-select" name="processo_parceria_id">
                                                <option value="" <?= (empty($ajuste['processo_parceria_id'])) ? 'selected' : '' ?> disabled>Selecione...</option>
                                                <?php foreach ($listaProcessos as $processo): ?>
                                                    <option value="<?= $processo->id ?>" <?= ($ajuste['processo_parceria_id'] == $processo->id) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($processoModel->formatarProcesso($processo)) ?> - <?= htmlspecialchars($processo->assunto) ?> - <?= $processo->tipo ?>
                                                    </option>
                                                <?php endforeach; ?>                                                  
                                            </select>                                            
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted fw-semibold small">Processo de Pagamento</label>
                                            <select class="form-select" name="processo_pagamento_id">
                                                <option value="" <?= (empty($ajuste['processo_pagamento_id'])) ? 'selected' : '' ?> disabled>Selecione...</option>
                                                <?php foreach ($listaProcessos as $processo): ?>
                                                    <option value="<?= $processo->id ?>" <?= ($ajuste['processo_pagamento_id'] == $processo->id) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($processoModel->formatarProcesso($processo)) ?> - <?= htmlspecialchars($processo->assunto) ?> - <?= $processo->tipo ?>
                                                    </option>
                                                <?php endforeach; ?>                                                  
                                            </select> 
                                        </div>
                                    </div>

                                    <hr class="text-muted opacity-25">
                                    <div class="alert alert-info py-2 small">
                                        <i class="lni lni-information me-1"></i> As contas abaixo serão <b>adicionadas</b> ao ajuste (elas não apagam as existentes).
                                    </div>
                                    
                                    <div id="container-contas-edit">
                                        <div class="row mb-3 linha-conta-edit align-items-end">
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fw-semibold small">Banco</label>
                                                <input type="text" class="form-control" name="banco[]" placeholder="Ex: Banco do Brasil">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label text-muted fw-semibold small">Agência</label>
                                                <input type="text" class="form-control" name="agencia[]" placeholder="Ex: 0000-0">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fw-semibold small">Conta Corrente</label>
                                                <input type="text" class="form-control" name="conta_corrente[]" placeholder="Ex: 12345-6">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fw-semibold small">Fonte</label>
                                                <select class="form-select" name="fonte_recursos[]">
                                                    <option value="">Selecione...</option>
                                                    <option value="Municipal">Municipal</option>
                                                    <option value="Estadual">Estadual</option>
                                                    <option value="Federal">Federal</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-light text-danger border shadow-sm btn-remover-conta-edit d-none w-100"><i class="lni lni-trash-can"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-light border shadow-sm text-primary mt-2 fw-medium px-3" id="btn-add-conta-edit">
                                        <i class="lni lni-plus"></i> Adicionar Nova Conta
                                    </button>

                                </div>
                            </div>

                        </div>
                        
                        <div class="modal-footer border-top px-4 py-3 bg-white">
                            <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary px-4"><i class="lni lni-save me-1"></i> Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalNovoEmpenho" tabindex="-1" aria-labelledby="modalNovoEmpenhoLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom px-4 py-3">
                            <h5 class="modal-title fw-bold text-dark" id="modalNovoEmpenhoLabel">
                                <div class="d-inline-flex align-items-center justify-content-center bg-soft-primary rounded-circle me-2" style="width: 32px; height: 32px;">
                                    <i class="lni lni-folder-plus fs-6"></i>
                                </div>
                                Registrar Novo Empenho
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="?id=<?= $idAjuste ?? '' ?>&salvarEmpenho=true" method="POST">
                            <div class="modal-body p-4 bg-light">
                                <input type="hidden" name="ajuste_id" value="<?= $idAjuste ?? '' ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">Data do Empenho</label>
                                        <input type="date" class="form-control" name="data_empenho" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">Nº do Empenho (NE)</label>
                                        <input type="text" class="form-control" name="numero_empenho" placeholder="Ex: 00001/2026" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Descrição / Referência</label>
                                    <input type="text" class="form-control" name="descricao" placeholder="Ex: Repasse referente ao 1º Quadrimestre" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Valor do Empenho (R$)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">R$</span>
                                        <input type="text" class="form-control mascara-moeda border-start-0 ps-0" name="valor" placeholder="0,00" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer px-4 py-3 border-top">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary px-4">Salvar Empenho</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalNovoPagamento" tabindex="-1" aria-labelledby="modalNovoPagamentoLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom px-4 py-3">
                            <h5 class="modal-title fw-bold text-dark" id="modalNovoPagamentoLabel">
                                <div class="d-inline-flex align-items-center justify-content-center bg-soft-success rounded-circle me-2" style="width: 32px; height: 32px;">
                                    <i class="lni lni-wallet fs-6 text-success"></i>
                                </div>
                                Registrar Pagamento
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="?id=<?= $idAjuste ?? '' ?>&salvarPagamento=true" method="POST">
                            <div class="modal-body p-4 bg-light">
                                <input type="hidden" name="ajuste_id" value="<?= $idAjuste ?? '' ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Vincular a qual Empenho?</label>
                                    <select class="form-select" name="empenho_id" required>
                                        <option value="">Selecione um empenho...</option>
                                        <?php 
                                        $empenhosDisponiveis = $ajusteModel->getEmpenhosComSaldo($idAjuste);
                                        foreach ($empenhosDisponiveis as $emp): 
                                        ?>
                                            <option value="<?= $emp['id'] ?>">
                                                NE <?= htmlspecialchars($emp['numero_empenho']) ?>
                                                (Saldo: R$ <?= number_format($emp['saldo_disponivel'], 2, ',', '.') ?>)
                                            </option>
                                        <?php endforeach;  ?>
                                    </select>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">Data do Pagamento</label>
                                        <input type="date" class="form-control" name="data_pagamento" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">Valor Pago (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">R$</span>
                                            <input type="text" class="form-control mascara-moeda border-start-0 ps-0" name="valor" placeholder="0,00" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Descrição</label>
                                    <input type="text" class="form-control" name="descricao" placeholder="Ex: Pagamento 1ª Parcela" required>
                                </div>                                
                            </div>
                            <div class="modal-footer px-4 py-3 border-top">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success px-4">Salvar Pagamento</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalNovoAditivo" tabindex="-1" aria-labelledby="modalNovoAditivoLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        
                        <div class="modal-header border-bottom px-4 py-3">
                            <h5 class="modal-title fw-bold text-dark" id="modalNovoAditivoLabel">
                                <div class="d-inline-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-circle me-2" style="width: 32px; height: 32px;">
                                    <i class="lni lni-add-files fs-6"></i>
                                </div>
                                Registrar Termo Aditivo / Apostilamento
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <form action="?id=<?= $idAjuste ?? '' ?>&salvarAditivo=true" method="POST">
                            <div class="modal-body p-4 bg-light">
                                <input type="hidden" name="ajuste_id" value="<?= $idAjuste ?? '' ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small text-muted">Número do Aditamento</label>
                                        <input type="text" class="form-control" name="numero" placeholder="Ex: 3/2026" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold small text-muted">Sequência</label>
                                        <input type="text" class="form-control" name="sequencia" placeholder="Ex: Primeiro, Segundo, Terceiro..." required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small text-muted">Tipo de Aditamento</label>
                                        <select class="form-select" name="tipo_aditivo" required>
                                            <option value="">Selecione...</option>
                                            <option value="Vigencia">Vigência (Prorrogação)</option>
                                            <option value="Valor">Valor (Acréscimo/Supressão)</option>
                                            <option value="Vigencia e Valor">Vigência e Valor</option>
                                            <option value="Outros">Outros / Apostilamento</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small text-muted">Data da Assinatura</label>
                                        <input type="date" class="form-control" name="data_assinatura" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small text-muted">Nova Vigência (Opcional)</label>
                                        <input type="date" class="form-control" name="nova_data_fim">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold small text-muted">Impacto Financeiro</label>
                                        <div class="input-group shadow-sm">
                                            <select class="form-select border-end-0 text-dark fw-medium" name="operacao_valor" style="max-width: 140px;" id="operacaoValor">
                                                <option value="+">Acréscimo</option>
                                                <option value="-">Supressão</option>
                                            </select>
                                            <span class="input-group-text bg-white border-start-0 border-end-0 px-2 text-muted">R$</span>
                                            <input type="text" class="form-control mascara-moeda border-start-0 ps-0 fw-bold" name="valor_aditivo" placeholder="0,00">
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label fw-semibold small text-muted">Justificativa / Objeto (Opcional)</label>
                                        <textarea class="form-control shadow-sm" name="justificativa" rows="1" placeholder="Descreva brevemente o motivo..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-footer px-4 py-3 border-top">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-dark px-4"><i class="lni lni-save me-1"></i> Salvar Aditivo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include 'modalSair.php'; ?>
            <?php include 'toasts.php'; ?>
            <?php include 'footer.php'; ?>
        </div>
    </div> <script src="./js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Inicializa Tooltips do Bootstrap
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Máscara de moeda
        $('.mascara-moeda').mask('#.##0,00', {reverse: true});
        
        // DataTables para deixar a tabela dinâmica
        $('.table').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json' },
            order: [], 
            pageLength: 10, 
            responsive: true,
            columnDefs: [ { orderable: false, targets: -1 } ]
        });

        // =========================================================================
        // LÓGICA DO MODAL DE EDIÇÃO (Adicionar/Remover Contas Bancárias)
        // =========================================================================
        $('#btn-add-conta-edit').on('click', function() {
            // Clona a primeira linha oculta
            let novaLinha = $('.linha-conta-edit').first().clone();
            
            // Limpa os valores dos inputs copiados
            novaLinha.find('input').val('');
            novaLinha.find('select').val('');
                        
            // Remove a classe d-none APENAS do botão de lixeira da nova linha
            novaLinha.find('.btn-remover-conta-edit').removeClass('d-none'); 
            
            // Adiciona a nova linha ao final do container
            $('#container-contas-edit').append(novaLinha);
        });

        // Remove a linha clonada quando o usuário clica na lixeira da conta
        $(document).on('click', '.btn-remover-conta-edit', function() {
            $(this).closest('.linha-conta-edit').remove();
        });
        // =========================================================================

        // SweetAlert de Exclusão (Para os botões das tabelas de empenhos/pagamentos)
        $('.btn-excluir').on('click', function(e) {
            e.preventDefault(); 
            let registroId = $(this).data('id'); 
            let tipo = $(this).data('tipo'); // Pega se é empenho, pagamento ou aditivo
            
            // Dica: Se quiser saber se está excluindo um empenho ou pagamento, 
            // você pode colocar um data-tipo="empenho" no HTML do botão e capturar aqui.

            Swal.fire({
                title: 'Tem certeza?',
                text: "Você está prestes a excluir este registro. Esta ação não pode ser desfeita!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redireciona para a mesma página, avisando o PHP para apagar
                    window.location.href = `?id=<?= $idAjuste ?>&excluir=${tipo}&id_registro=${registroId}`;
                    
                    // Swal.fire('Atente-se!', 'A função de exclusão precisa ser conectada ao backend.', 'info')
                }
            })
        });
    });
</script>

</body>
</html>

