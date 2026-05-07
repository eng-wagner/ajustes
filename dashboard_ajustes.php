<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";
date_default_timezone_set("America/Sao_Paulo");
$timezone = new DateTimeZone("America/Sao_Paulo");

// Aqui no futuro vamos instanciar os Models (ex: Ajuste, Empenho, Pagamento)

use PhpOffice\PhpSpreadsheet\Shared\Date;
use Source\Models\User;
use Source\Models\Ajuste;
use Source\Models\Logs;
use Source\Models\Instituicao;

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

// ====================================================================
// 4. Carregamento dos outros Models
// ====================================================================

$ajusteModel = new Ajuste();
$logModel = new Logs();
$instituicaoModel = new Instituicao();
$listaAjustes = $ajusteModel->listarTodos();
$listaInstituicoes = $instituicaoModel->all();
$listaAditivos = $ajusteModel->listarTodosAditivos();

$ajusteModel = new Ajuste();
$logModel = new Logs();
$instituicaoModel = new Instituicao();
$listaAjustes = $ajusteModel->listarTodos();
$listaInstituicoes = $instituicaoModel->all();
$listaAditivos = $ajusteModel->listarTodosAditivos();

// ====================================================================
// FILTROS VIA GET
// ====================================================================
$buscaGet     = filter_input(INPUT_GET, 'busca', FILTER_DEFAULT) ?? '';
$statusGet    = filter_input(INPUT_GET, 'status', FILTER_DEFAULT) ?? 'todos';
$exercicioGet = filter_input(INPUT_GET, 'exercicio', FILTER_DEFAULT) ?? 'todos';

// Se algum filtro foi acionado, filtramos o array $listaAjustes
if (!empty($buscaGet) || $statusGet !== 'todos' || $exercicioGet !== 'todos') {
    
    $listaAjustes = array_filter($listaAjustes, function($ajuste) use ($buscaGet, $statusGet, $exercicioGet, $listaAditivos) {
        $passouBusca = true;
        $passouStatus = true;
        $passouExercicio = true;

        // 1. Filtro de Busca (Nome da Instituição ou Número do Ajuste)
        if (!empty($buscaGet)) {
            $termo = mb_strtolower($buscaGet, 'UTF-8');
            $nomeInst = mb_strtolower($ajuste['nome_instituicao'] ?? '', 'UTF-8');
            $numAjuste = mb_strtolower($ajuste['numero_ajuste'] ?? '', 'UTF-8');
            
            if (strpos($nomeInst, $termo) === false && strpos($numAjuste, $termo) === false) {
                $passouBusca = false;
            }
        }

        // 2. Filtro de Status
        if ($statusGet !== 'todos') {
            // Usando o operador de coalescência (??) para evitar erro se a chave 'status' não existir
            $statusAjuste = $ajuste['status'] ?? ''; 
            if ($statusAjuste !== $statusGet) {
                $passouStatus = false;
            }
        }

        // 3. Filtro de Exercício (Pega o ano da data_inicio)
        if ($exercicioGet !== 'todos') {

            // 3.1 Começamos assumindo que a data final é a data_fim original do Ajuste
            $dataFimEfetiva = $ajuste['data_fim'] ?? null;

            // 3.2 Varremos a lista de aditivos para ver se algum esticou o prazo
            if (!empty($listaAditivos)) {
                foreach ($listaAditivos as $aditivo) {
                    // Verifica se o aditivo pertence a este ajuste (adapte 'ajuste_id' se o nome da chave for outro no seu banco)
                    if (isset($aditivo['ajuste_id']) && $aditivo['ajuste_id'] == $ajuste['id']) {
                        
                        // Se a data do aditivo for maior que a nossa data referência, ela passa a ser a data efetiva
                        if (!empty($aditivo['nova_data_fim']) && strtotime($aditivo['nova_data_fim']) > strtotime($dataFimEfetiva)) {
                            $dataFimEfetiva = $aditivo['nova_data_fim'];
                        }
                    }
                }
            }

            // 3.3 Agora que temos a data fim final (seja da origem ou do aditivo), verificamos o ano
            if (!empty($dataFimEfetiva)) {
                $anoEfetivo = date('Y', strtotime($dataFimEfetiva));
                
                if ($anoEfetivo != $exercicioGet) {
                    $passouExercicio = false;
                }
            } else {
                // Se não tiver data_fim na origem e também não tiver em nenhum aditivo, reprova
                $passouExercicio = false;
            }
        }

        // O ajuste só aparece na tabela se passar nos 3 filtros
        return $passouBusca && $passouStatus && $passouExercicio;
    });
}

// ====================================================================
// CÁLCULOS DINÂMICOS PARA OS CARDS DO DASHBOARD
// ====================================================================

$totalAjustesAtivos = 0;
$valorGlobalAtivo = 0;
$ajustesAVencer = 0;
$exercicioAtual = date('Y');
$dataHoje = new DateTime('now', $timezone);

if (!empty($listaAjustes)) {
    foreach ($listaAjustes as $ajusteCard) {

        // Verifica se o status é "Vigente"
        if ($ajusteCard['status'] === 'Vigente') {
            $totalAjustesAtivos++;
            $valorGlobalAtivo += (float) $ajusteCard['valor_global_inicial'];
            
            if(is_array($listaAditivos)) {
                foreach ($listaAditivos as $aditivo) {
                    if ($aditivo['ajuste_id'] == $ajusteCard['id']) {
                        $valorGlobalAtivo += (float) $aditivo['valor_aditivo'];
                    }
                }
            }

            // Lógica para calcular quantos ajustes estão a vencer nos próximos 90 dias
            if(!empty($ajusteCard['data_fim'])) {
                $dataFim = new DateTime($ajusteCard['data_fim'], $timezone);                
                $diferenca = $dataHoje->diff($dataFim);               
                
                // Se o ajuste vencer nos próximos 90 dias e ainda não tiver vencido
                if ($diferenca->invert === 0 && $diferenca->days <= 90) {
                    $ajustesAVencer++;
                }
            }            
        }
    }
}

// ====================================================================
// Ações de Formulário
// ====================================================================

if (isset($_REQUEST['novoAjuste']) && $_SERVER['REQUEST_METHOD'] === 'POST') 
{   
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                            
    if($ajusteModel->salvarNovoAjuste($postData)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Criou um novo ajuste (" . $postData['tipo_ajuste'] . " nº " . $postData['numero_ajuste'] . ")"
        ]);        
        redirecionar('dashboard_ajustes.php', 'sucesso', "Ajuste criado com sucesso!");        
    } else {
        redirecionar('dashboard_ajustes.php', 'erro', "Erro na criação do ajuste!");            
    }   
}

// ====================================================================
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <title>Dashboard de Ajustes</title>
    
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
        
        <div class="main p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="page-title mb-1"><i class="lni lni-layers text-primary me-2"></i>Gestão de Ajustes e Parcerias</h2>
                    <p class="text-muted mb-0">Visão geral e acompanhamento de instrumentos</p>
                </div>
                <div>
                    <button class="btn btn-primary shadow-sm rounded-3 px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalNovoAjuste">
                        <i class="lni lni-plus fw-bold me-1"></i> Novo Ajuste
                    </button>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card dashboard-card h-100 p-2">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <span class="text-overline">Ajustes Ativos</span>
                                <h3 class="mb-0 fw-bold text-dark"><?= $totalAjustesAtivos ?></h3>
                            </div>
                            <div class="icon-shape bg-primary bg-opacity-10 text-primary fs-4">
                                <i class="lni lni-layers"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card h-100 p-2">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <span class="text-overline">Valor Global Ativo</span>
                                <h3 class="mb-0 fw-bold text-dark fs-4">R$ <?= number_format($valorGlobalAtivo, 2, ',', '.') ?></h3>
                            </div>
                            <div class="icon-shape bg-success bg-opacity-10 text-success fs-4">
                                <i class="lni lni-coin"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card h-100 p-2">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <span class="text-overline">A Vencer (90 dias)</span>
                                <h3 class="mb-0 fw-bold text-dark"><?= $ajustesAVencer ?></h3>
                            </div>
                            <div class="icon-shape bg-warning bg-opacity-10 text-warning fs-4">
                                <i class="lni lni-timer"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card h-100 p-2">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <span class="text-overline">Exercício Atual</span>
                                <h3 class="mb-0 fw-bold text-dark"><?= $exercicioAtual ?></h3>
                            </div>
                            <div class="icon-shape bg-secondary bg-opacity-10 text-secondary fs-4">
                                <i class="lni lni-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card dashboard-card mb-4">
                <div class="card-body p-4">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label text-muted fw-semibold small">Buscar Instituição / Nº Ajuste</label>
                            <input type="text" class="form-control" name="busca" value="<?= htmlspecialchars($buscaGet) ?>" placeholder="Digite o nome ou número do ajuste...">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label text-muted fw-semibold small">Status</label>
                            <select class="form-select" name="status">
                                <option value="todos" <?= $statusGet === 'todos' ? 'selected' : '' ?>>Todos</option>
                                <option value="Vigente" <?= $statusGet === 'Vigente' ? 'selected' : '' ?>>Apenas Ativos (Vigentes)</option>
                                <option value="Concluído" <?= $statusGet === 'Concluído' ? 'selected' : '' ?>>Concluídos</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label text-muted fw-semibold small">Exercício</label>
                            <select class="form-select" name="exercicio">
                                <option value="todos" <?= $exercicioGet === 'todos' ? 'selected' : '' ?>>Todos os Anos</option>
                                <option value="2026" <?= $exercicioGet === '2026' ? 'selected' : '' ?>>2026</option>
                                <option value="2025" <?= $exercicioGet === '2025' ? 'selected' : '' ?>>2025</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-light w-100 border shadow-sm text-primary fw-medium">
                                <i class="lni lni-search-alt me-1"></i> Filtrar
                            </button>
                            <?php if (!empty($_GET)): ?>
                                <a href="dashboard_ajustes.php" class="btn btn-light border shadow-sm text-danger" title="Limpar Filtros">
                                    <i class="lni lni-close me-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card dashboard-card">
                <div class="card-body p-0">
                    <div class="table-responsive p-3">
                        <table class="table table-hover align-middle mb-0 border-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="border-bottom-0 pb-3 ps-3">Nº Ajuste</th>
                                    <th class="border-bottom-0 pb-3">Tipo</th>
                                    <th class="border-bottom-0 pb-3">Instituição</th>
                                    <th class="border-bottom-0 pb-3">Vigência (Fim)</th>
                                    <th class="border-bottom-0 pb-3">Valor Global</th>
                                    <th class="border-bottom-0 pb-3">Status</th>
                                    <th class="text-center border-bottom-0 pb-3 pe-3">Painel</th>
                                </tr>
                            </thead>
                            <tbody class="border-top">
                                <?php if (!empty($listaAjustes)): ?>                                    
                                    <?php foreach($listaAjustes as $ajuste):
                                        $data_fim = new DateTime($ajuste['data_fim'], $timezone);
                                        $valorGlobalTotal = (float)$ajuste['valor_global_inicial'];
                                        if (is_array($listaAditivos)) {
                                            foreach ($listaAditivos as $aditivo) {
                                                if ($aditivo['ajuste_id'] == $ajuste['id']) {
                                                    $valorGlobalTotal += (float)$aditivo['valor_aditivo'];                                                    
                                                    /*$data_fim_aditivo = new DateTime($aditivo['nova_data_fim'], $timezone);
                                                    if ($data_fim_aditivo > $data_fim) {
                                                        $data_fim = $data_fim_aditivo; // Atualiza a data de fim para a do aditivo
                                                    }*/
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-dark ps-3 text-nowrap"><?= htmlspecialchars($ajuste['numero_ajuste']) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ajuste['tipo_ajuste']) ?></span></td>                                                  
                                            <td class="text-secondary fw-medium"><?= htmlspecialchars($ajuste['nome_instituicao']) ?></td>
                                            <td class="text-secondary"><?= $data_fim->format('d/m/Y'); ?></td>                                            
                                            <td class="fw-bold text-dark">R$ <?= number_format($valorGlobalTotal, 2, ',', '.') ?></td>                                            
                                            <td>
                                                <?php 
                                                    // Badge moderna translúcida
                                                    if($ajuste['status'] === 'Vigente') {
                                                        $corBadge = 'bg-success bg-opacity-10 text-success border border-success';
                                                    } else {
                                                        $corBadge = 'bg-secondary bg-opacity-10 text-secondary border border-secondary';
                                                    }
                                                ?>
                                                <span class="badge <?= $corBadge ?> px-3 py-2"><?= htmlspecialchars($ajuste['status']) ?></span>
                                            </td>                                            
                                            <td class="text-center pe-3 text-nowrap">
                                                <a href="painel_ajuste.php?id=<?= $ajuste['id'] ?>" class="btn btn-sm btn-outline-primary rounded-3 px-3" title="Acessar Painel">
                                                    Acessar <i class="lni lni-arrow-right ms-1"></i>
                                                </a>
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

        <div class="modal fade" id="modalNovoAjuste" tabindex="-1" aria-labelledby="modalNovoAjusteLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <form action="?novoAjuste=true" method="POST" name="novoAjuste" class="modal-content border-0 shadow-lg rounded-4">
                    
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold text-dark" id="modalNovoAjusteLabel">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle me-2" style="width: 32px; height: 32px;">
                                <i class="lni lni-add-files fs-6"></i>
                            </div>
                            Cadastrar Novo Ajuste / Parceria
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4 bg-light">
                        
                        <div class="card dashboard-card mb-4">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold text-dark mb-4">Identificação das Partes</h6>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label text-muted fw-semibold small">Instituição / Organização da Sociedade Civil (OSC)</label>
                                        <select class="form-select select2-instituicao" name="instituicao_id" required style="width: 100%;">
                                            <option value="" disabled selected>Selecione a Instituição já cadastrada...</option>
                                            <?php foreach($listaInstituicoes as $inst): ?>
                                                <option value="<?= $inst->id ?>"><?= htmlspecialchars($inst->instituicao) ?> - CNPJ: <?= $instituicaoModel->formatarCnpj($inst) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label text-muted fw-semibold small">Tipo de Ajuste</label>
                                        <select class="form-select" name="tipo_ajuste_id" required>
                                            <option value="">Selecione a natureza...</option>
                                            <option value="1">Termo de Colaboração</option>
                                            <option value="2">Termo de Fomento</option>
                                            <option value="3">Acordo de Cooperação</option>
                                            <option value="4">Termo de Doação</option>
                                            <option value="5">Termo de Cooperação Técnica</option>                                            
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card dashboard-card mb-4">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold text-dark mb-4">Dados do Instrumento</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted fw-semibold small">Nº do Ajuste / Ano</label>
                                        <input type="text" class="form-control" name="numero_ajuste" placeholder="Ex: 001/2026" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted fw-semibold small">Valor Global Inicial</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-muted">R$</span>
                                            <input type="text" class="form-control mascara-moeda border-start-0 ps-0" name="valor_global_inicial" placeholder="0,00" required>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.70rem;">Aditivos serão lançados no painel</small>
                                    </div>
                                </div>
                                <div class="row">                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label text-muted fw-semibold small">Data da Assinatura</label>
                                        <input type="date" class="form-control" name="data_assinatura" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label text-muted fw-semibold small">Data de Início</label>
                                        <input type="date" class="form-control" name="data_inicio" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label text-muted fw-semibold small">Data de Fim (Vigência)</label>
                                        <input type="date" class="form-control" name="data_fim" required>
                                    </div>                                    
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <label class="form-label text-muted fw-semibold small">Objeto do Ajuste (Resumo)</label>
                                        <textarea class="form-control" name="objeto" rows="2" placeholder="Descreva sucintamente o objetivo desta parceria..." required></textarea>
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
                                    <h6 class="card-title fw-bold text-dark mb-0">Processos e Contas Vinculadas (Exclusivas)</h6>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fw-semibold small">Processo de Parceria (Mãe)</label>
                                        <select class="form-select select2-processo" name="processo_parceria_id">
                                            <option value="">Selecione...</option>
                                            <option value="10">Proc. 4587/2025</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fw-semibold small">Processo de Pagamento</label>
                                        <select class="form-select select2-processo" name="processo_pagamento_id">
                                            <option value="">Selecione...</option>
                                            <option value="15">Proc. 5002/2026</option>
                                        </select>
                                    </div>
                                </div>

                                <hr class="text-muted opacity-25">
                                
                                <div id="container-contas">
                                    <div class="row mb-3 linha-conta align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label text-muted fw-semibold small">Banco</label>
                                            <input type="text" class="form-control" name="banco[]" placeholder="Ex: Banco do Brasil" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-muted fw-semibold small">Agência</label>
                                            <input type="text" class="form-control" name="agencia[]" placeholder="Ex: 0000-0" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-muted fw-semibold small">Conta Corrente</label>
                                            <input type="text" class="form-control" name="conta_corrente[]" placeholder="Ex: 12345-6" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-muted fw-semibold small">Fonte de Recursos</label>
                                            <select class="form-select" name="fonte_recursos[]" required>
                                                <option value="">Selecione...</option>
                                                <option value="1">Municipal</option>
                                                <option value="2">Estadual</option>
                                                <option value="3">Federal</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-light text-danger border shadow-sm btn-remover-conta d-none w-100" title="Remover Conta"><i class="lni lni-trash-can"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-sm btn-light border shadow-sm text-primary mt-2 fw-medium px-3" id="btn-add-conta">
                                    <i class="lni lni-plus"></i> Adicionar outra conta bancária
                                </button>

                            </div>
                        </div>

                    </div>
                    
                    <div class="modal-footer border-top px-4 py-3 bg-white">
                        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4"><i class="lni lni-save me-1"></i> Salvar e Abrir Painel</button>
                    </div>
                </form>
                
            </div>
        </div>

        <?php include 'modalSair.php'; ?>
        <?php include 'toasts.php'; ?>
        <?php include 'footer.php'; ?>
    </div>

<script src="./js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // 1. APLICAR MÁSCARAS AOS INPUTS
        $('.mascara-moeda').mask('#.##0,00', {reverse: true});
        
        // 2. TRANSFORMAR AS TABELAS EM DATATABLES
        $('.table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json',
            },
            order: [], 
            pageLength: 10, 
            responsive: true,
            columnDefs: [
                { orderable: false, targets: -1 } 
            ],
            dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

        // 3. INICIALIZAR SELECT2
        $('.select2-instituicao, .select2-processo').select2({
            theme: 'bootstrap-5',
            placeholder: 'Digite para buscar...',
            dropdownParent: $('#modalNovoAjuste') 
        });

        // 4. LÓGICA DE CONTAS BANCÁRIAS
        $('#btn-add-conta').on('click', function() {
            let novaLinha = $('.linha-conta').first().clone();
            novaLinha.find('input').val('');
            novaLinha.find('select').val('');
            novaLinha.find('.btn-remover-conta').removeClass('d-none');
            $('#container-contas').append(novaLinha);
        });

        $(document).on('click', '.btn-remover-conta', function() {
            $(this).closest('.linha-conta').remove();
        });
    });
</script>

</body>
</html>