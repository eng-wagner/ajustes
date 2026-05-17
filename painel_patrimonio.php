<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";
require_once __DIR__ . "/source/Helpers/Helpers.php";

use Source\Models\Patrimonio;
use Source\Models\Processo;
use Source\Models\Fornecedor;
use Source\Models\Instituicao;
use Source\Models\User;
use Source\Database\Connect;

date_default_timezone_set("America/Sao_Paulo");

// Proteção de sessão
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

$pdo = Connect::getInstance();
$patrimonioModel = new Patrimonio();
$processoModel = new Processo();
$fornecedorModel = new Fornecedor();
$instituicaoModel = new Instituicao();

// Verifica se a instituição foi selecionada
$idProcesso = filter_input(INPUT_GET, 'proc_id', FILTER_VALIDATE_INT);

// ==============================================================================
// CONTROLLER: AÇÕES DE SALVAR E EXCLUIR
// ==============================================================================
if ($idProcesso && isset($_GET['acao'])) {
    if ($_GET['acao'] === 'salvarBem' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = $patrimonioModel->save($_POST);
        if ($resultado['status']) {
            redirecionar("painel_patrimonio.php?proc_id=$idProcesso", 'sucesso', $resultado['message']);
        } else {
            redirecionar("painel_patrimonio.php?proc_id=$idProcesso", 'erro', $resultado['message']);
        }
        exit();
    }

    if ($_GET['acao'] === 'excluirBem' && isset($_GET['id'])) {
        $resultado = $patrimonioModel->delete($_GET['id']);
        redirecionar("painel_patrimonio.php?proc_id=$idProcesso", 'sucesso', 'Bem patrimonial excluído com sucesso!');
        exit();
    }

    if ($_GET['acao'] === 'atualizarStatus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $dadosTramitacao = [
            'processo_id' => $_POST['processo_id'],
            'status' => $_POST['status'],
            'observacoes' => $_POST['observacoes'],
            'usuario_id' => $_SESSION['user_id']
        ];
        
        // Aqui você chamará o método que vai inserir na nova tabela que criamos
        $resultado = $patrimonioModel->salvarTramitacao($dadosTramitacao);
        
        // Simulação do redirecionamento
        if ($resultado['status']) {
            redirecionar("painel_patrimonio.php?proc_id=$idProcesso", 'sucesso', $resultado['message']);
        } else {
            redirecionar("painel_patrimonio.php?proc_id=$idProcesso", 'erro', $resultado['message']);
        }
        exit();
    }

    // =========================================================
    // AÇÃO: SALVAR NOVA PENDÊNCIA
    // =========================================================
    if ($_GET['acao'] === 'salvarPendencia' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $dadosPendencia = [
            'processo_id'     => $idProcesso, 
            'data'            => date('Y-m-d'),
            'favorecido'      => $_POST['favorecido'],
            'num_documento'   => $_POST['num_documento'],
            'num_termo'       => $_POST['num_termo'],
            'descricao_item'  => $_POST['descricao_item'],
            'pendencia'       => $_POST['pendencia'],
            'providencias'    => $_POST['providencias'],
            'regularizado'    => 'False' // Por padrão, entra como Pendente
        ];
        
        // Chame o método do seu model para fazer o INSERT na tabela de pendências
        // $resultado = $pendenciaModel->inserirPendencia($dadosPendencia);
        
        redirecionar("painel_patrimonio.php?proc_id=$idProcesso", 'sucesso', 'Pendência registrada com sucesso!');
        exit();
    }

    // =========================================================
    // AÇÃO: MARCAR PENDÊNCIA COMO REGULARIZADA
    // =========================================================
    if ($_GET['acao'] === 'regularizarPendencia' && isset($_GET['id_pendencia'])) {
        $idPendencia = (int)$_GET['id_pendencia'];
        $dataHoje = date('Y-m-d');
        
        // Chame o método do seu model para dar o UPDATE no banco
        // Exemplo de SQL interna: 
        // UPDATE tblpendencias SET Regularizado = 'True', data_regularizacao = '$dataHoje' WHERE idPendencia = $idPendencia
        // $resultado = $pendenciaModel->marcarComoRegularizado($idPendencia, $dataHoje);
        
        redirecionar("painel_patrimonio.php?proc_id=$idProcesso", 'sucesso', 'A pendência foi marcada como regularizada!');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <title>Painel de Patrimônio</title>
    
    <style>
        /* VISUAL PREMIUM DASHBOARD (Copiado do painel_ajustes) */
        body {
            background-color: #f4f7fa; 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .page-title {
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #2b3035;
        }

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

        .icon-shape {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
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
            
            
            <div class="container-fluid px-4 py-2">  
                
                <?php if (!$idProcesso): 
                    // ==============================================================================
                    // TELA DE SELEÇÃO INICIAL
                    // ==============================================================================
                    $listaProcessos = $processoModel->findTDprocs();
                ?>
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <div>
                            <h2 class="page-title mb-1"><i class="lni lni-package text-primary me-2"></i>Gestão de Patrimônio e Inventário</h2>
                            <p class="text-muted mb-0">Selecione um processo abaixo para gerenciar seus bens patrimoniais</p>
                        </div>
                        <div>
                            <a href="hub.php" class="btn btn-light border shadow-sm rounded-3 px-4 py-2 text-primary fw-medium">
                                <i class="lni lni-home me-1"></i> Voltar ao Início
                            </a>
                        </div>
                    </div>

                    <div class="card dashboard-card mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive p-4">
                                <table id="tabelaInstituicoes" class="table table-hover align-middle mb-0 border-0 w-100">
                                    <thead class="table-light text-muted small text-uppercase">
                                        <tr>
                                            <th class="border-bottom-0 pb-3 ps-3" style="width: 80px;">PROCESSO</th>
                                            <th class="border-bottom-0 pb-3">Nome da Instituição</th>
                                            <th class="border-bottom-0 pb-3">CNPJ</th>
                                            <th class="text-center border-bottom-0 pb-3 pe-3" style="width: 150px;">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top">
                                        <?php foreach ($listaProcessos as $proc): ?>
                                            <tr>
                                                <td class="fw-bold text-muted ps-3 text-nowrap"><?= str_pad($processoModel->formatarProcesso($proc), 3, '0', STR_PAD_LEFT) ?></td>
                                                <td class="text-dark fw-bold"><?= htmlspecialchars($proc->instituicao) ?></td>
                                                <td class="text-secondary"><?= $instituicaoModel->formatarCnpj($proc) ?? 'Não informado' ?></td>
                                                <td class="text-center pe-3 text-nowrap">
                                                    <a href="painel_patrimonio.php?proc_id=<?= $proc->idProc ?>" class="btn btn-sm btn-outline-primary rounded-3 px-3 w-100 fw-medium" title="Acessar Inventário">
                                                        Acessar Painel <i class="lni lni-arrow-right ms-1"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
                    <script>
                        $(document).ready(function() {
                            $('#tabelaInstituicoes').DataTable({
                                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
                                pageLength: 10,
                                order: [[ 1, "asc" ]] // Ordena em ordem alfabética pelo nome
                            });
                        });
                    </script>

                <?php else: 
                    // ==============================================================================
                    // PAINEL DA INSTITUIÇÃO SELECIONADA
                    // ==============================================================================
                    $procData = $processoModel->findById($idProcesso);                    
                    $instData = $instituicaoModel->findById($procData->instituicao_id);                    

                    $listaBens = $patrimonioModel->listarPorProcesso($idProcesso);
                    $listaProcessos = $processoModel->findByInstId($idProcesso);
                    $fornecedores = $fornecedorModel->findAllFornecedores();
                    
                    $valorTotalPatrimonio = array_sum(array_column($listaBens, 'valor_total'));
                    $totalItens = count($listaBens);

                    // ==============================================================================
                    // LÓGICA INTELIGENTE DE STATUS
                    // Substitua 'status_atual' pelo nome real da coluna no seu banco de dados
                    // ==============================================================================
                    $tramitacaoAtual = $patrimonioModel->buscarUltimoStatus($idProcesso);

                    $statusProcesso = $tramitacaoAtual->status ?? 'Na SE-322.2 (Análise)'; 
                    $observacoes = $tramitacaoAtual->observacoes ?? 'Nenhuma observação registrada'; 
                    $dataAtualizacao = $tramitacaoAtual->data_atualizacao ?? date('Y-m-d H:i:s'); 
                    
                    // Padrão (Azul/Informação)
                    $badgeTheme = 'primary';
                    $iconTheme = 'lni-magnifier'; 

                    // Muda as cores com base no texto do status
                    if (stripos($statusProcesso, 'SA-222') !== false || stripos($statusProcesso, 'Aguardando') !== false) {
                        $badgeTheme = 'warning';
                        $iconTheme = 'lni-timer';
                    } elseif (stripos($statusProcesso, 'Pendente') !== false) {
                        $badgeTheme = 'danger';
                        $iconTheme = 'lni-warning';
                    } elseif (stripos($statusProcesso, 'Concluído') !== false || stripos($statusProcesso, 'Finalizado') !== false) {
                        $badgeTheme = 'success';
                        $iconTheme = 'lni-checkmark-circle';
                    }


                ?>

                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <div>
                            <div class="d-flex align-items-center">
                                <h2 class="mb-0 text-dark fw-bold"><i class="lni lni-apartment text-primary me-2"></i><?= htmlspecialchars($instData->instituicao) ?></h2>
                                <a href="painel_patrimonio.php" class="btn btn-sm btn-outline-secondary ms-3 rounded-pill">
                                    <i class="lni lni-reload"></i> Trocar
                                </a>
                            </div>
                            <h5 class="text-secondary mt-2 fw-normal">
                                <span class="badge bg-primary me-2">Inventário</span> 
                                Processo: <?= $processoModel->formatarProcesso($procData) ?? 'Não informado' ?>
                            </h5>
                        </div>
                        <div class="text-end min-w-200">
                            <span class="text-overline d-block mb-1 text-muted">Fase / Tramitação</span>
                            <div class="d-inline-flex align-items-center px-3 py-2 rounded-3 bg-<?= $badgeTheme ?> bg-opacity-10 border border-<?= $badgeTheme ?> border-opacity-25 shadow-sm">
                                <i class="lni <?= $iconTheme ?> text-<?= $badgeTheme ?> fs-5 me-2"></i>
                                <span class="fw-bold text-<?= $badgeTheme ?> text-uppercase small" style="letter-spacing: 0.5px;">
                                    <?= htmlspecialchars($statusProcesso) ?>
                                </span>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted fw-medium"><i class="lni lni-calendar me-1"></i>Atualizado em: <?= date('d/m/Y', strtotime($dataAtualizacao)) ?></small>
                            </div>
                            <button class="btn btn-sm btn-link text-decoration-none mt-1" data-bs-toggle="modal" data-bs-target="#modalAtualizarStatus">
                                Editar Status
                            </button>
                        </div>
                    </div>

                    <!-- <div class="row mb-4 g-4"> 
                        <div class="col-md-4">
                            <div class="card dashboard-card h-100 p-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-overline">Valor Total em Bens</span>
                                        <div class="icon-shape bg-primary bg-opacity-10 text-primary fs-4">
                                            <i class="lni lni-wallet"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold text-dark mb-1">R$ <?= number_format($valorTotalPatrimonio, 2, ',', '.') ?></h3>
                                    <small class="text-muted fw-medium">Acumulado na unidade</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card dashboard-card h-100 p-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-overline">Total de Itens</span>
                                        <div class="icon-shape bg-warning bg-opacity-10 text-warning fs-4">
                                            <i class="lni lni-layers"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold text-dark mb-2"><?= $totalItens ?></h3>
                                    <small class="text-muted fw-medium">Bens registrados no sistema</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card dashboard-card h-100 p-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-overline">Situação / Regularidade</span>
                                        <div class="icon-shape bg-success bg-opacity-10 text-success fs-4">
                                            <i class="lni lni-checkmark-circle"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold text-dark mb-2">Regular</h3>
                                    <small class="text-muted fw-medium">Sem pendências de patrimônio</small>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <div class="card dashboard-card mb-5">
                        <div class="card-header bg-white pt-3 pb-0 border-bottom-0">
                            <ul class="nav nav-tabs border-bottom-0" id="patrimonioTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link text-dark fw-bold" data-bs-toggle="tab" data-bs-target="#dadosGerais">
                                        <i class="lni lni-home"></i> Dados da Unidade
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link active text-dark fw-bold" data-bs-toggle="tab" data-bs-target="#bens">
                                        <i class="lni lni-list"></i> Relação de Bens
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link text-dark fw-bold" data-bs-toggle="tab" data-bs-target="#pendencias">
                                        <i class="lni lni-warning"></i> Pendências
                                    </button>
                                </li>
                            </ul>
                        </div>
            
                        <div class="card-body p-0">
                            <div class="tab-content p-4">                            
                                
                                <div class="tab-pane fade" id="dadosGerais">
                                    <h5 class="fw-bold text-dark mb-4">Informações da Instituição</h5>
                                    <div class="row g-4 mb-3">
                                        <div class="col-md-4">
                                            <label class="text-overline d-block mb-1">CNPJ</label>
                                            <span class="fw-semibold text-dark fs-5"><?= $instituicaoModel->formatarCnpj($instData) ?? 'Não informado' ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-overline d-block mb-1">Telefone</label>
                                            <span class="fw-semibold text-dark fs-5"><?= $instData->telefone ?? 'Não informado' ?></span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-overline d-block mb-1">E-mail de Contato</label>
                                            <span class="fw-semibold text-dark fs-5"><?= $instData->email ?? 'Não informado' ?></span>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="text-overline d-block mb-1">Endereço Completo</label>
                                            <span class="fw-semibold text-dark fs-5"><?= $instData->endereco ?? 'Endereço não cadastrado' ?></span>
                                        </div>
                                        
                                        <div class="col-md-12 mt-4">
                                            <div class="p-4 bg-white border border-light rounded-4 shadow-sm">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <label class="text-overline d-block mb-0 text-primary">
                                                        <i class="lni lni-clipboard me-1"></i> Observações e Andamento
                                                    </label>
                                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAtualizarStatus">
                                                        <i class="lni lni-pencil me-1"></i> Editar
                                                    </button>
                                                </div>
                                                <p class="text-dark mb-0" style="line-height: 1.6;">
                                                    <?= !empty($observacoes) && $observacoes !== 'Nenhuma observação registrada até o momento.' ? nl2br(htmlspecialchars($observacoes)) : '<span class="text-muted fst-italic">Nenhuma observação registrada até o momento.</span>' ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade show active" id="bens">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0 text-dark fw-bold">Inventário Consolidado</h5>
                                        <button class="btn btn-sm btn-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoBem">
                                            <i class="lni lni-plus me-1"></i> Adicionar Bem
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table id="tabelaPatrimonio" class="table table-hover align-middle border">
                                            <thead class="table-light text-muted small text-uppercase">
                                                <tr>
                                                    <th>Patrimônio / NIR</th>
                                                    <th>Descrição do Item</th>
                                                    <th>Categoria</th>
                                                    <th>Nota Fiscal</th>
                                                    <th class="text-end">Total (R$)</th>
                                                    <th class="text-center">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>                                            
                                                <?php foreach ($listaBens as $bem): ?>
                                                    <tr>
                                                        <td class="align-middle">
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($bem['numero_patrimonio'] ?? 'S/N') ?></div>
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary mt-1">NIR: <?= htmlspecialchars($bem['nir'] ?? '-') ?></span>
                                                        </td>
                                                        <td class="align-middle">
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($bem['descricao_item']) ?></div>
                                                            <small class="text-muted">Local: <?= htmlspecialchars($bem['local_guarda'] ?? 'Não definido') ?> | Qtd: <?= $bem['quantidade'] ?></small>
                                                        </td>
                                                        <td class="align-middle text-muted">
                                                            <?= htmlspecialchars($bem['categoria']) ?>
                                                        </td>
                                                        <td class="align-middle text-muted">
                                                            <span class="fw-semibold text-primary"><?= htmlspecialchars($bem['nota_fiscal'] ?? 'S/N') ?></span><br>
                                                            <small><?= !empty($bem['data_aquisicao']) ? date('d/m/Y', strtotime($bem['data_aquisicao'])) : '-' ?></small>
                                                        </td>
                                                        <td class="align-middle text-end fw-bold text-success">
                                                            R$ <?= number_format($bem['valor_total'], 2, ',', '.') ?>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button class="btn btn-outline-secondary" title="Editar"><i class="lni lni-pencil"></i></button>
                                                                <button class="btn btn-outline-danger btn-excluir" data-id="<?= $bem['id'] ?>" title="Excluir"><i class="lni lni-trash-can"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>                                                                                                        
                                            </tbody>
                                        </table>
                                    </div>
                                </div>  

                                <div class="tab-pane fade" id="pendencias">
                                    <?php 
                                    // Supondo que você faça a busca no banco de dados no topo da página
                                    // Exemplo: $listaPendencias = $pendenciaModel->listarPorProcesso($idProcesso);
                                    // Como ainda não criamos o Model, vou simular que a variável existe para o código funcionar.
                                    
                                    if (empty($listaPendencias)): ?>
                                        <div class="text-center py-5">
                                            <i class="lni lni-checkmark-circle text-success display-1 opacity-25 mb-3"></i>
                                            <h4 class="fw-bold text-dark">Tudo Regularizado</h4>
                                            <p class="text-muted">Não existem pendências de tombamento documentadas para este processo.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h5 class="mb-0 text-dark fw-bold">Histórico de Pendências</h5>
                                            <button class="btn btn-sm btn-danger px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovaPendencia">
                                                <i class="lni lni-plus me-1"></i> Registrar Pendência
                                            </button>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table id="tabelaPendencias" class="table table-hover align-middle border w-100">
                                                <thead class="table-light text-muted small text-uppercase">
                                                    <tr>
                                                        <th style="width: 100px;">Data</th>
                                                        <th>Favorecido / NF</th>
                                                        <th>Item / Descrição</th>
                                                        <th>Pendência e Providências</th>
                                                        <th class="text-center">Status</th>
                                                        <th class="text-center">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>                                            
                                                    <?php foreach ($listaPendencias as $pend): ?>
                                                        <tr>
                                                            <td class="align-middle text-muted fw-medium">
                                                                <?= !empty($pend['Data']) ? date('d/m/Y', strtotime($pend['Data'])) : '-' ?>
                                                            </td>
                                                            <td class="align-middle">
                                                                <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($pend['Favorecido'] ?? '') ?>">
                                                                    <?= htmlspecialchars($pend['Favorecido'] ?? 'Não informado') ?>
                                                                </div>
                                                                <small class="text-muted">Doc/NF: <?= htmlspecialchars($pend['Nº do Documento'] ?? '-') ?></small>
                                                            </td>
                                                            <td class="align-middle">
                                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($pend['Descrição do Item'] ?? 'Geral') ?></div>
                                                                <small class="text-muted">Termo: <?= htmlspecialchars($pend['Nº do Termo de Doação'] ?? '-') ?></small>
                                                            </td>
                                                            <td class="align-middle">
                                                                <span class="fw-bold text-danger d-block mb-1"><?= htmlspecialchars($pend['Pendência']) ?></span>
                                                                <small class="text-muted fst-italic"><i class="lni lni-arrow-right text-primary"></i> <?= htmlspecialchars($pend['Providências']) ?></small>
                                                            </td>
                                                            <td class="align-middle text-center">
                                                                <?php 
                                                                    // Verifica se o campo 'Regularizado' é True ou False (adaptar conforme seu banco)
                                                                    $isRegularizado = filter_var($pend['Regularizado'], FILTER_VALIDATE_BOOLEAN); 
                                                                ?>
                                                                <?php if ($isRegularizado): ?>
                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">Resolvido</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">Pendente</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <button class="btn btn-outline-secondary" title="Editar"><i class="lni lni-pencil"></i></button>
                                                                    
                                                                    <?php if (!$isRegularizado): ?>
                                                                        <a href="?acao=regularizarPendencia&id_pendencia=<?= $pend['idPendencia'] ?>&proc_id=<?= $idProcesso ?>" 
                                                                        class="btn btn-outline-success" 
                                                                        title="Marcar como Resolvido/Regularizado"
                                                                        onclick="return confirm('Tem certeza que deseja marcar esta pendência como REGULARIZADA?');">
                                                                            <i class="lni lni-checkmark"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>                                                                                                        
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($idProcesso): ?>
    <div class="modal fade" id="modalNovoBem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle me-2" style="width: 32px; height: 32px;">
                            <i class="lni lni-package fs-6 text-primary"></i>
                        </div>
                        Cadastrar Bem Patrimonial
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="?acao=salvarBem&processo_id=<?= $idProcesso ?>" method="POST">
                    <div class="modal-body p-4 bg-light">
                        <input type="hidden" name="processo_id" value="<?= $idProcesso ?>">
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Origem do Recurso</label>
                                <select class="form-select" name="origem_recurso" required>
                                    <option value="">Selecione a origem...</option>
                                    <option value="Termo de Colaboração">Termo de Colaboração</option>
                                    <option value="PDDE">PDDE</option>                                    
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Fornecedor</label>
                                <input type="text" class="form-control" name="fornecedor" style="width: 100%;" placeholder="Ex: Fornecedor LDTA" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4 align-items-end">
                            <div class="col-12 mb-0">
                                <label class="form-label fw-semibold small text-muted d-block">Como deseja inserir o(s) patrimônio(s)?</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_insercao" id="insercaoUnica" value="unica" checked onchange="togglePatrimonio()">
                                    <label class="form-check-label small fw-bold" for="insercaoUnica">Único</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_insercao" id="insercaoLote" value="lote" onchange="togglePatrimonio()">
                                    <label class="form-check-label small fw-bold text-primary" for="insercaoLote">Intervalo (Ex: 100 até 120)</label>
                                </div>
                            </div>

                            <div class="col-md-4" id="divPatrimonioUnico">
                                <label class="form-label fw-semibold small text-muted">Nº Patrimônio(s)</label>
                                <input type="text" class="form-control border-primary" name="numero_patrimonio" placeholder="Ex: 792363">
                            </div>

                            <div class="col-md-2 d-none" id="divPatrimonioInicio">
                                <label class="form-label fw-semibold small text-muted">Nº Inicial</label>
                                <input type="number" class="form-control border-primary" name="patrimonio_inicio" placeholder="Ex: 100">
                            </div>
                            <div class="col-md-2 d-none" id="divPatrimonioFim">
                                <label class="form-label fw-semibold small text-muted">Nº Final</label>
                                <input type="number" class="form-control border-primary" name="patrimonio_fim" placeholder="Ex: 120">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">NIR</label>
                                <input type="text" class="form-control" name="nir" placeholder="Ex: 707/2023">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Termo / Referência</label>
                                <input type="text" class="form-control" name="numero_termo_doacao" placeholder="Ex: 01/2023">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold small text-muted">Descrição do Item <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="descricao_item" required placeholder="Ex: PAINEL PSICOMOTOR">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-muted">Marca / Modelo</label>
                                <input type="text" class="form-control text-uppercase" name="marca_modelo" placeholder="Ex: TRAMONTINA / MASTER">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Categoria</label>
                                <select class="form-select" name="categoria">
                                    <option value="Mobiliário">Mobiliário</option>
                                    <option value="Informática">Informática</option>
                                    <option value="Eletrodoméstico">Eletrodoméstico</option>
                                    <option value="Brinquedo">Brinquedo</option>
                                    <option value="Segurança">Segurança</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Quantidade <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="quantidade" id="qtdBens" value="1" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Valor Unitário (R$) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control mascara-moeda" name="valor_unitario" placeholder="0,00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Nota Fiscal</label>
                                <input type="text" class="form-control" name="nota_fiscal">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Data Aquisição</label>
                                <input type="date" class="form-control" name="data_aquisicao">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small text-muted">Local de Guarda / Setor</label>
                            <input type="text" class="form-control text-uppercase" name="local_guarda" placeholder="Ex: SECRETARIA, SALA 01">
                        </div>
                    </div>
                    
                    <div class="modal-footer px-4 py-3 border-top">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4">Salvar Patrimônio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAtualizarStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom px-4 py-3 bg-light">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="lni lni-reload text-primary me-2"></i> Atualizar Tramitação
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="?acao=atualizarStatus&proc_id=<?= $idProcesso ?>" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="processo_id" value="<?= $idProcesso ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted">Fase / Status Atual</label>
                            <select class="form-select form-select-lg fs-6" name="status" required>
                                <option value="Na SE-322.2 (Análise)" <?= ($statusProcesso ?? '') == 'Na SE-322.2 (Análise)' ? 'selected' : '' ?>>Na SE-322.2 (Análise)</option>
                                <option value="Encaminhado para SA-222" <?= ($statusProcesso ?? '') == 'Encaminhado para SA-222' ? 'selected' : '' ?>>Encaminhado para SA-222</option>
                                <option value="Aguardando Retorno" <?= ($statusProcesso ?? '') == 'Aguardando Retorno' ? 'selected' : '' ?>>Aguardando Retorno</option>
                                <option value="Pendente de Documentação" <?= ($statusProcesso ?? '') == 'Pendente de Documentação' ? 'selected' : '' ?>>Pendente de Documentação</option>
                                <option value="Concluído / Finalizado" <?= ($statusProcesso ?? '') == 'Concluído / Finalizado' ? 'selected' : '' ?>>Concluído / Finalizado</option>
                            </select>
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label fw-semibold small text-muted">Observações do Processo</label>
                            <textarea class="form-control" name="observacoes" rows="5" placeholder="Digite detalhes sobre a pendência ou o andamento atual..."><?= htmlspecialchars(($observacoes ?? '') !== 'Nenhuma observação registrada até o momento.' ? ($observacoes ?? '') : '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer px-4 py-3 border-top">
                        <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm"><i class="lni lni-save me-1"></i> Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNovaPendencia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom px-4 py-3 bg-light">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="lni lni-plus text-danger me-2"></i> Registrar Nova Pendência
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="?acao=salvarPendencia&proc_id=<?= $idProcesso ?>" method="POST">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Favorecido / Fornecedor</label>
                                <input type="text" class="form-control" name="favorecido" placeholder="Ex: ATELIE QUERO QUERO" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Nº do Documento (NF)</label>
                                <input type="text" class="form-control" name="num_documento" placeholder="Ex: 4446">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Nº do Termo de Doação</label>
                                <input type="text" class="form-control" name="num_termo" placeholder="Ex: 01/2025">
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small text-muted">Descrição do Item / Equipamento</label>
                                <input type="text" class="form-control" name="descricao_item" placeholder="Ex: MINI SOFA COM ALMOFADAS" required>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small text-muted">Qual é a Pendência?</label>
                                <textarea class="form-control" name="pendencia" rows="3" placeholder="Ex: Termo de Doação não foi entregue..." required></textarea>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small text-muted">Providências Necessárias</label>
                                <textarea class="form-control" name="providencias" rows="3" placeholder="Ex: ENVIAR TERMO JUNTAMENTE COM NF E FOTOS NO LOCAL DE GUARDA" required></textarea>
                            </div>
                            
                        </div>
                    </div>
                    
                    <div class="modal-footer px-4 py-3 border-top">
                        <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold shadow-sm"><i class="lni lni-save me-1"></i> Registrar Pendência</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/script.js"></script>

    <script>
        function togglePatrimonio() {
            const isLote = document.getElementById('insercaoLote').checked;
            const divUnico = document.getElementById('divPatrimonioUnico');
            const divInicio = document.getElementById('divPatrimonioInicio');
            const divFim = document.getElementById('divPatrimonioFim');
            const campoQtd = document.getElementById('qtdBens');

            if (isLote) {
                divUnico.classList.add('d-none');
                divInicio.classList.remove('d-none');
                divFim.classList.remove('d-none');
                
                // Opcional: Avisa o usuário para focar no preenchimento de lotes
                divInicio.querySelector('input').setAttribute('required', 'true');
                divFim.querySelector('input').setAttribute('required', 'true');
            } else {
                divUnico.classList.remove('d-none');
                divInicio.classList.add('d-none');
                divFim.classList.add('d-none');
                
                divInicio.querySelector('input').removeAttribute('required');
                divFim.querySelector('input').removeAttribute('required');
            }
        }
        
        $(document).ready(function() {
            // Inicializa Select2 para a tela de busca inicial
            if ($('.select2-inst').length) {
                $('.select2-inst').select2({ theme: 'bootstrap-5' });
            }

            // Inicializações apenas se a tabela e modal existirem (Tela 2)
            if ($('#tabelaPatrimonio').length) {
                $('#tabelaPatrimonio').DataTable({
                    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
                    order: [[ 0, "desc" ]] 
                });

                $('.select2-modal').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#modalNovoBem'),
                    placeholder: 'Selecione ou digite para buscar...'
                });

                // Confirmação para excluir
                $('.btn-excluir').on('click', function(e) {
                    e.preventDefault();
                    const id = $(this).data('id');
                    
                    Swal.fire({
                        title: 'Excluir bem patrimonial?',
                        text: "Esta ação não pode ser desfeita!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sim, excluir!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "?acao=excluirBem&processo_id=<?= $idProcesso ?>&id=" + id;
                        }
                    });
                });
            }
            if ($('#tabelaPendencias').length) {
                $('#tabelaPendencias').DataTable({
                    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json' },
                    order: [[ 0, "desc" ]] // Ordena pelas pendências mais recentes
                });
            }   
        });
    </script>
    
    <?php include 'toasts.php'; ?>
</body>
</html>