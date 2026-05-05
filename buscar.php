<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\Logs;
use Source\Models\User;
use Source\Models\Instituicao;
use Source\Models\Processo;

// Criar instâncias do modelo.
$userModel = new User();
$logModel = new Logs();
$instituicaoModel = new Instituicao();
$processoModel = new Processo();

// Verifica se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}

$loggedUser = $userModel->findById($_SESSION['user_id']);
if ($loggedUser) {
    $userName = $loggedUser->nome;
    $perfil = $loggedUser->perfil;
    $firstName = substr($userName, 0, strpos($userName, " "));
} else {
    session_destroy();
    header("Location: index.php?status=sessao_invalida");
    exit();
}

// ==============================================================================
// 1. ROTAS E REDIRECIONAMENTOS (Sempre no topo antes do HTML)
// ==============================================================================
if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
    $_SESSION['flag'] = false;
    session_unset();
    header("Location:index.php?status=logoff");
    exit();
}

if(isset($_REQUEST['pc']) && $_REQUEST['pc'] == true ) {                
    $_SESSION['idProc'] = $_REQUEST['idProc'];
    header('Location:pddePC.php');
    exit();
}

if(isset($_REQUEST['af']) && $_REQUEST['af'] == true ) {                
    $_SESSION['idProc'] = $_REQUEST['idProc'];
    header('Location:pddeFinanc.php');
    exit();
}

if(isset($_REQUEST['tc']) && $_REQUEST['tc'] == true ) {                
    $_SESSION['idProc'] = $_REQUEST['idProc'];
    header('Location:termoPC.php');                       
    exit();
}

// Cadastrar Novo Processo
if(isset($_REQUEST['novoProcesso']) && $_REQUEST['novoProcesso'] == true) {
    if($perfil === 'adm' || $perfil === 'ges') {
        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);    
    
        if ($processoModel->save($postData)) {
            $logModel->save([
                'usuario' => $_SESSION['matricula'],
                'acao' => "Cadastrou novo processo"
            ]);
            redirecionar('buscar.php', 'sucesso', "Cadastrou novo processo com sucesso");        
        } else {    
            redirecionar('buscar.php', 'erro', "Erro ao cadastrar processo");        
        } 
    } else {
        redirecionar('buscar.php', 'erro', "ERRO: Usuário sem permissão para cadastro de processo. Contate o administrador.");
        exit();
    }
     
}

// ==============================================================================
// 2. BUSCA DE DADOS E LÓGICA DA PÁGINA
// ==============================================================================
$searchTerm = filter_input(INPUT_GET, 'search', FILTER_DEFAULT);                       

if(!empty($searchTerm)) {
    $listaProcessos = $processoModel->findProcByInstName($searchTerm);
} else {
    $listaProcessos = $processoModel->allProcs();        
}

$listaInstituicoes = $instituicaoModel->all();

// Define qual aba deve iniciar aberta. (Se pesquisou algo, abre a Lista. Se não, abre o Dash).
// $abaAtiva = !empty($searchTerm) ? 'lista' : 'dashboard';
$abaAtiva = 'lista';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    
    <script src="https://www.gstatic.com/charts/loader.js"></script> 
    <?php include 'dash.php'; ?>
    
    <title>Localizar Processos</title>
    <style>
        h1 {
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu.php'; ?>
        
        <div class="main p-3">
            <div class="text-center mb-4">
                <h1>Painel de Processos</h1>
            </div>

            <div class="container-fluid">
                
                <ul class="nav nav-tabs mb-4" id="buscarTabs" role="tablist">                    
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $abaAtiva == 'lista' ? 'active' : '' ?>" id="lista-tab" data-bs-toggle="tab" data-bs-target="#lista" type="button" role="tab" aria-controls="lista" aria-selected="<?= $abaAtiva == 'lista' ? 'true' : 'false' ?>">
                            <i class="lni lni-list"></i> Lista de Processos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $abaAtiva == 'dashboard' ? 'active' : '' ?>" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="<?= $abaAtiva == 'dashboard' ? 'true' : 'false' ?>">
                            <i class="lni lni-bar-chart"></i> Dashboard
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="buscarTabsContent">
                    
                    <div class="tab-pane fade <?= $abaAtiva == 'dashboard' ? 'show active' : '' ?>" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                        <div class="row g-4 mt-2">
                            <div class="col-12 col-xl-6">
                                <div class="card shadow-sm h-100 border-0 rounded-4">
                                    <div class="card-body d-flex justify-content-center align-items-center p-1">
                                        <div id="statusPrestacao" style="width: 100%; height: 400px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-6">
                                <div class="card shadow-sm h-100 border-0 rounded-4">
                                    <div class="card-body d-flex justify-content-center align-items-center p-1">
                                        <div id="columnchart_values" style="width: 100%; height: 400px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade <?= $abaAtiva == 'lista' ? 'show active' : '' ?>" id="lista" role="tabpanel" aria-labelledby="lista-tab">
                        
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 bg-light p-3 rounded shadow-sm">
                            <form method="get" action="buscar.php" class="d-flex w-100 me-md-3 mb-3 mb-md-0">
                                <div class="input-group">
                                    <span class="input-group-text bg-white" id="search-icon"><i class="lni lni-search-alt"></i></span>
                                    <input type="text" name="search" value="<?= htmlspecialchars($searchTerm ?? '') ?>" class="form-control" placeholder="Buscar pelo Nome da Instituição..." aria-label="Buscar" aria-describedby="search-icon">
                                    <button class="btn btn-primary px-4" type="submit">Buscar</button>
                                </div>
                            </form>
                            
                            <button type="button" class="btn btn-success text-nowrap shadow-sm" data-bs-toggle="modal" data-bs-target="#processoModal">
                                <i class="lni lni-plus"></i> Novo Processo
                            </button>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-0 table-responsive">
                                <?php if (!empty($processos)): ?>
                                    <table class="table table-striped table-hover align-middle mb-0">
                                        <thead class="table-dark">
                                            <tr class="text-center align-middle">
                                                <th class="fw-semibold py-1 ps-3">Instituição</th>
                                                <th class="fw-semibold py-1">CNPJ</th>                        
                                                <th class="fw-semibold py-1">Nº Processo Digital</th>
                                                <th class="fw-semibold py-1">Assunto</th>
                                                <th class="fw-semibold py-1">Tipo</th>                        
                                                <th class="fw-semibold py-1">Análise</th>                                        
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($listaProcessos as $proc): 
                                                $instId = $proc->instituicao_id;
                                                $instNome = htmlspecialchars($proc->instituicao);
                                                $instCnpj = preg_replace("/[^0-9]/", "", $proc->cnpj);
                                                
                                                // Máscara do CNPJ em PHP
                                                $cnpjMasked = strlen($instCnpj) == 14 ? substr($instCnpj,0,2) . "." . substr($instCnpj,2,3) . "." . substr($instCnpj,5,3) . "/" . substr($instCnpj,8,4) . "-" . substr($instCnpj,12,2) : $instCnpj;
                                                
                                                $numeroFormatado = htmlspecialchars($proc->orgao) . '.' . htmlspecialchars($proc->numero) . '/' . htmlspecialchars($proc->ano) . '-' . htmlspecialchars($proc->digito);
                                                $assunto = htmlspecialchars($proc->assunto);
                                                $tipo = htmlspecialchars($proc->tipo);
                                                $detalhamento = htmlspecialchars($proc->detalhamento);
                                                $idProc = $proc->idProc;                                                    
                                            ?>
                                                <tr class="text-center">
                                                    <td class="text-start ps-3 fw-medium"><?= $instNome ?></td>                                            
                                                    <td class="text-nowrap"><?= $cnpjMasked ?></td>
                                                    <td><span class="badge bg-secondary"><?= $numeroFormatado ?></span></td>
                                                    <td class="text-nowrap"><?= $assunto ?></td>
                                                    <td ><?= $tipo ?> <?= $detalhamento ?></td>
                                                    <td class="text-nowrap">
                                                        <div class="d-flex justify-content-center gap-2">
                                                            <?php if($tipo == "Termo de Colaboração"): ?>
                                                                <a href="?tc=true&idProc=<?= $idProc ?>" class="btn btn-sm btn-outline-primary" title="Análise da Execução">
                                                                    <i class="lni lni-write"></i> Execução
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="?pc=true&idProc=<?= $idProc ?>" class="btn btn-sm btn-outline-primary" title="Análise da Execução">
                                                                    <i class="lni lni-write"></i> Execução
                                                                </a>
                                                                <a href="?af=true&idProc=<?= $idProc ?>" class="btn btn-sm btn-outline-success" title="Análise Financeira">
                                                                    <i class="lni lni-investment"></i> Financeiro
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="p-5 text-center">
                                        <i class="lni lni-empty-file fs-1 text-muted mb-3"></i>
                                        <h5 class="text-muted">Nenhum processo encontrado.</h5>
                                        <?php if(!empty($searchTerm)): ?>
                                            <p class="text-muted mb-0">Não localizamos a instituição "<strong><?= htmlspecialchars($searchTerm) ?></strong>".</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="processoModal" tabindex="-1" aria-labelledby="processoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="?novoProcesso=true" method="post" name="pendencia">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="processoModalLabel"><i class="lni lni-folder text-success me-2"></i> Novo Processo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">

                        <div class="row g-3 mb-3">                                        
                            <div class="col-12">
                                <label class="form-label text-muted small mb-1" for="instProc">Instituição</label>
                                <select name="instProc" class="form-select select2-instituicao" id="instProc" required style="width: 100%;">                                
                                    <option value="" disabled selected>Selecione a instituição...</option>
                                    <?php foreach($listaInstituicoes as $inst): 
                                        $idInst = $inst->id;
                                        $instNome = htmlspecialchars($inst->instituicao);
                                        $instCnpj = $inst->formatarCnpj($inst);
                                    ?>
                                    <option value="<?= $idInst ?>"><?= $instNome ?> - CNPJ: <?= $instCnpj ?></option>
                                    <?php endforeach; ?>
                                </select>                                                    
                            </div>
                        </div>          
                        
                        <div class="row g-3 mb-3">                                        
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1" for="assuntoProc">Assunto</label>
                                <select name="assuntoProc" class="form-select" id="assuntoProc" required>
                                    <option value="" disabled selected>Selecione...</option>
                                    <option value="Prestação de Contas">Prestação de Contas</option>
                                    <option value="Parceria">Parceria</option>
                                    <option value="Pagamento">Pagamento</option>
                                </select>                                                    
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1" for="tipoProc">Tipo</label>
                                <select name="tipoProc" class="form-select" id="tipoProc" required>
                                    <option value="" disabled selected>Selecione...</option>
                                    <option value="PDDE Básico">PDDE Básico</option>
                                    <option value="PDDE Qualidade">PDDE Qualidade</option>
                                    <option value="PDDE Equidade">PDDE Equidade</option>
                                    <option value="Termo de Colaboração">Termo de Colaboração</option>
                                </select>                                                    
                            </div>
                        </div>
                        <div class="row g-3 mb-3">                                        
                            <div class="col-12">
                                <label class="form-label text-muted small mb-1" for="detalhamentoProc">Detalhamento</label>
                                <input type="text" name="detalhamentoProc" class="form-control" id="detalhamentoProc" required placeholder="Detalhamento do processo" />                                              
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label text-muted small mb-1" for="orgaoProc">Órgão</label>
                                <input type="text" name="orgaoProc" class="form-control bg-light" value="SB" id="orgaoProc" readonly/>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-muted small mb-1" for="numProc">Número</label>
                                <input type="text" name="numProc" class="form-control" id="numProc" minlength="6" maxlength="6" required placeholder="000000" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted small mb-1" for="anoProc">Ano</label>
                                <input type="text" name="anoProc" class="form-control" id="anoProc" minlength="4" maxlength="4" required placeholder="2026" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted small mb-1" for="digProc">Dígito</label>
                                <input type="text" name="digProc" class="form-control" id="digProc" minlength="2" maxlength="2" required placeholder="00" />
                            </div>                                        
                        </div>   
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <input type="submit" class="btn btn-success px-4" value="Cadastrar Processo"/>                                
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializa o Select2 com o tema do Bootstrap 5
            $('.select2-instituicao').select2({
                theme: 'bootstrap-5',
                placeholder: 'Digite para buscar a instituição...',
                dropdownParent: $('#processoModal') // Importante para funcionar dentro do Modal do Bootstrap!
            });
        });

        document.querySelectorAll('button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]').forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function (event) {
                drawAllCharts();
            });
        });
    </script>

</body>
</html>