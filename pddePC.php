<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";
require_once __DIR__ . "/source/Helpers/Helpers.php";

date_default_timezone_set("America/Sao_Paulo");
$timezone = new DateTimeZone("America/Sao_Paulo");

use Source\Models\Contabilidade;
use Source\Models\Logs;
use Source\Models\User;
use Source\Models\Instituicao;
use Source\Models\Processo;
use Source\Models\Banco;
use Source\Models\Repasse;
use Source\Models\Despesa;
use Source\Models\Documento;
use Source\Models\Pendencia;
use Source\Models\Programa;

// 1. Instancia Apenas o User inicialmente
$userModel = new User();

// 2. Verifica Segurança IMEDIATAMENTE
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}

// 3. Dados do Usuário Logado
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

if (!isset($_SESSION['idProc'])) {
    header('Location:buscar.php');
    exit();
}

// ====================================================================
// 4. Carregamento dos outros Models e Variáveis de Sessão
// ====================================================================
$logModel = new Logs();
$contModel = new Contabilidade();
$instituicaoModel = new Instituicao();
$processoModel = new Processo();
$bancoModel = new Banco();
$repasseModel = new Repasse();
$despesaModel = new Despesa();
$programaModel = new Programa();
$pendenciaModel = new Pendencia();
$documentoModel = new Documento();

$currentUser = $_SESSION['user_id'];
$idProc = (int) $_SESSION['idProc'];

// ====================================================================
// Variáveis Globais da Página (Garante que sempre estarão inicializadas)
// ====================================================================
$modalToOpen = '';
$despesasPendentes = 0;

// Buscas Iniciais e Dados do Processo
$processo = $processoModel->findById($idProc);
$statusProcesso = $processoModel->procStatus($idProc);
$despesas = $despesaModel->findByProcId($idProc);
$numPendencias = $pendenciaModel->contarPendencias($idProc);

if($processo->tipo === "Termo de Colaboração"){
    redirecionar('buscar.php', 'erro', 'Selecione um processo do tipo PDDE.');    
}

if($processo){
    $instituicao = $instituicaoModel->findById($processo->instituicao_id);
    $numProcesso = $processoModel->formatarProcesso($processo);
    $tipoPrograma = $processo->tipo;
    $tipoProcesso = $processo->assunto . ' - ' . $tipoPrograma;
    $idInst = $instituicao->id;
    $cnpj = $instituicaoModel->formatarCnpj($instituicao);    
    $iNome = $instituicao->instituicao;
    $iEmail = $instituicao->email;
    $iEndereco = $instituicao->endereco;
    $inep = $instituicao->inep;
    $iTelefone = $instituicao->telefone;
    
    $contabilidade = $contModel->findById($instituicao->cont_id);    
    $cNome = $contabilidade->c_nome;
    $cTelefone = $contabilidade->c_telefone;
    $cEmail = $contabilidade->c_email;
}

$progs = $programaModel->findByProgName($tipoPrograma);

$idStatus = empty($statusProcesso) ? Processo::STATUS_AGUARDANDO_ENTREGA : $statusProcesso->status_id;
$statusPC = empty($statusProcesso) ? "Aguardando Entrega" : $statusProcesso->status_pc;

if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
    session_unset();
    header("Location:index.php");
    exit();
}

// ====================================================================
// Coleta de Dados da Aba "Análise da Execução" (Limpeza das datas)
// ====================================================================
$entrega = "";
$usuarioExId = "";
$nomeUsuarioEx = "";
$dataAnaliseEx = "";
$dataEncAf = "";
$obsAnaliseEx = "";
$movimento = "";
$savedFlag = "";
$pendente = "";

$proc = $processoModel->abrirTramitacao($idProc);                        
if ($proc) {
    $entrega = $proc->data_ent;
    $usuarioExId = $proc->usuario_ex_id;
    $dataAnaliseEx = $proc->data_analise_ex;
    $dataEncAf = $proc->data_enc_af;
    $obsAnaliseEx = $proc->obs_analise_ex;
    $movimento = $proc->s_movimento == "1" ? "checked" : "";
    $savedFlag = $proc->saved_flag;
    $pendente = $proc->pendente;                    

    if (!empty($entrega)) {
        $dtEntrega = new DateTime($entrega, $timezone);
        $entrega = $dtEntrega->format('d/m/Y');
    }
    if (!empty($usuarioExId)) {
        $nomeUsuarioEx = $userModel->findById($usuarioExId)->nome;
    }
    if (!empty($dataAnaliseEx)) {
        $dtAnalise = new DateTime($dataAnaliseEx, $timezone);
        $dataAnaliseEx = $dtAnalise->format('d/m/Y');
    }
    if (!empty($dataEncAf)) {
        $dtEnc = new DateTime($dataEncAf, $timezone);
        $dataEncAf = $dtEnc->format('d/m/Y');
    }
}

// ====================================================================
// Ações de Formulário e Processamento (Rotas de Botões e Tabelas)
// ====================================================================

if(isset($_REQUEST['entrega']) && $_REQUEST['entrega'] == true) {
    $_SESSION['aba_ativa'] = 'analiseExecucao';

    if(!empty($entrega)){
        $_SESSION['toast_erro'] = "A entrega já foi registrada!";        
    } elseif($_SESSION['perfil'] == 'ofc') {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Tentou receber o processo de id " . $idProc
        ]);
        $_SESSION['toast_erro'] = "Usuário não autorizado!";
    } else {                                            
        if($processoModel->receberProcesso($idProc)) {
            $logModel->save([
                'usuario' => $_SESSION['matricula'],
                'acao' => "Recebeu o processo de id " . $idProc
            ]);
            redirecionar('pddePC.php', 'sucesso', "Processo recebido com sucesso");
        }
    }    
}

// --- 2. SALVAR EXECUÇÃO ---                 
if(isset($_REQUEST['saveExec']) && $_REQUEST['saveExec'] == true) {  
    $_SESSION['aba_ativa'] = 'analiseExecucao';
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                                
    $despesasPendentes += $numPendencias;

    $idSts = ($despesasPendentes > 0) ? Processo::STATUS_PENDENCIA_AE : Processo::STATUS_ANALISE_EXECUCAO;
    $pendente = ($despesasPendentes > 0) ? 1 : 0;

    $idUserEx = $_SESSION['user_id'];
    if ($proc && $proc->saved_flag == 1) {
        $idUserEx = $proc->usuario_ex_id;
    }   
    
    $processoModel->saveExecucao($postData, $idSts, $idUserEx, $pendente, $idProc);
    redirecionar('pddePC.php', 'sucesso', "Execução salva com sucesso");    
}

// --- 3. ENCAMINHAR PARA ANÁLISE FINANCEIRA ---
if (isset($_REQUEST['encFin']) && $_REQUEST['encFin'] == true) {                                
    $despesasPendentes += $numPendencias;

    if ($despesasPendentes > 0) {
        $modalToOpen = 'avancarPend'; // Chama o modal usando a variável mágica que temos no final do código!
    } else {
        $pcStatus = $proc ? $proc->status_id : 0;
        if ($pcStatus >= Processo::STATUS_ANALISE_FINANCEIRA) {
            $_SESSION['toast_erro'] = "O processo já foi encaminhado para análise financeira!!!";
        } else {
            if ($processoModel->encaminharFin($idProc)) {
                $_SESSION['aba_ativa'] = 'analiseFinanceira';
                redirecionar('pddePC.php', 'sucesso', "Processo encaminhado para análise financeira");
            }  
        }
    }                                
}

// --- 4. FORÇAR AVANÇO PARA FINANCEIRA ---
if (isset($_REQUEST['forceFin']) && $_REQUEST['forceFin'] == true) {
    if ($idStatus == Processo::STATUS_PENDENCIA_AE) {
        if ($processoModel->encaminharFin($idProc)) {
            $_SESSION['aba_ativa'] = 'analiseFinanceira';
            redirecionar('pddePC.php', 'sucesso', "Processo encaminhado para análise financeira");
        }                                    
    } else {                                    
       redirecionar('pddePC.php', 'erro', "O Processo não está pendente");
    }
}

// --- 5. DELETAR DESPESA ---
if (isset($_GET['delDesp']) && $_GET['delDesp'] == true) {
    $_SESSION['aba_ativa'] = 'analiseExecucao';
    if ($idStatus >= Processo::STATUS_ANALISE_FINANCEIRA) {
        $_SESSION['toast_erro'] = "Não foi possível excluir a despesa. O processo já foi encaminhado para análise financeira.";
    } else {
        $idDesp = $_GET['idDesp'];
        if ($despesaModel->delete($idDesp)) {
            $logModel->save([
                'usuario' => $_SESSION['matricula'],
                'acao' => "Deletou a despesa de id " . $idDesp
            ]);
        }
        redirecionar('pddePC.php', 'sucesso', "Despesa apagada com sucesso");
        
    }
}

// --- 6. INCLUIR/ATUALIZAR DESPESA ---
if (isset($_REQUEST['include']) && $_REQUEST['include'] == true && $_SERVER['REQUEST_METHOD'] === 'POST') {          
    $_SESSION['aba_ativa'] = 'analiseExecucao';          
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    
    if ($despesaModel->save($postData, $idProc, $currentUser)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Inseriu nova despesa no processo de id " . $idProc
        ]);
        redirecionar('pddePC.php', 'sucesso', "Despesa incluída com sucesso");
    } else {
        redirecionar('pddePC.php', 'erro', "Erro ao incluir despesa!");       
    }
}

if (isset($_REQUEST['update']) && $_REQUEST['update'] == true && $_SERVER['REQUEST_METHOD'] === 'POST') {   
    $_SESSION['aba_ativa'] = 'analiseExecucao';          
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    
    if ($despesaModel->save($postData, $idProc, $currentUser)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Atualizou a despesa no processo de id " . $postData['idDespM']
        ]);
        redirecionar('pddePC.php', 'sucesso', "Atualizou a despesa com sucesso");
    } else {
        redirecionar('pddePC.php', 'erro', "Erro ao atualizar despesa!");        
    }
}

if (isset($_POST['acaoAjax']) && $_POST['acaoAjax'] === 'salvar_fornecedor') {    
    ob_clean();
    header('Content-Type: application/json');

    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);    

    if (empty($postData['cnpj']) || empty($postData['razao_social'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os campos.']);
        exit; // <-- CRUCIAL: Para o script aqui!
    }

    // Tenta salvar usando o seu DespesaModel
    $novoId = $despesaModel->novoFornecedor($postData);

    if ($novoId) {
        echo json_encode([
            'sucesso' => true, 
            'id' => $novoId, 
            'razao_social' => $postData['razao_social'],
            'cnpj' => $postData['cnpj']
        ]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar no banco de dados.']);
    }
    
    exit; // <-- CRUCIAL: Para o script aqui para não carregar o resto do HTML!
}

// Controle do Modal de Despesa (Edição vs Inclusão)
if (isset($_GET['editDesp']) && $_GET['editDesp'] == true) {
    $_SESSION['aba_ativa'] = 'analiseExecucao';
    $idDesp = $_GET['idDesp'];
    $desp = $despesaModel->findById($idDesp);                        
    
    if ($desp) {
        $idDespM = $desp->id;
        $idAcaoM = $desp->acao_id;
        $categoriaM = $desp->categoria;        
        $fornecedorIdM = $desp->fornecedor_id;
        $descricaoM = $desp->descricao;
        $numDocM = $desp->documento;
        $numPgtoM = $desp->pagamento;
        $dataDespM = $desp->data_desp;
        $valorM = $desp->valor;
                                
        $checkProgM = $desp->check_prog == 1 ? "checked" : "";
        $checkAtaM = $desp->check_ata == 1 ? "checked" : "";
        $checkEnqM = $desp->check_enq == 1 ? "checked" : "";
        $checkConsM = $desp->check_cons == 1 ? "checked" : "";

        // Removemos o 'R$' porque o plugin de máscara que colocamos já cuida disso dinamicamente!
        $valorReal = number_format($valorM, 2, ",", ""); 

        $fornM = $despesaModel->findFornecedorById($desp->fornecedor_id);
        $fornecedorM = $fornM->razao_social;
        $cnpjFornM = $fornM->cnpj;
    }
    
    $modalToOpen = 'despesaModal'; // Chama o modal silenciosamente!
    $action = "?update=true";
    $titulo = "Atualizar Despesa";
    $botao = '<input type="submit" class="btn btn-warning" value="Atualizar"/>';                
} else {
    $action = "?include=true";
    $titulo = "Nova Despesa";
    $botao = '<input type="submit" class="btn btn-success" value="Incluir"/>';                
}

// --- 7. ANÁLISE FINANCEIRA E SIGPC ---
if (isset($_REQUEST['saveFin']) && $_REQUEST['saveFin'] == true) {                        
    if ($idStatus < Processo::STATUS_ANALISE_FINANCEIRA) {                            
        redirecionar('pddePC.php', 'erro', "ERRO! O status do processo não está disponível para Análise Financeira!");        
    } else {
        $_SESSION['aba_ativa'] = 'analiseFinanceira';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
            if ($processoModel->atualizarFinan($postData, $idProc)) {
                $logModel->save([
                    'usuario' => $_SESSION['matricula'],
                    'acao' => "Atualizou o status da análise financeira do processo de " . $idProc
                ]);
                redirecionar('pddePC.php', 'sucesso', "Atualizou o status da análise financeira com sucesso");                
            } else {                
                redirecionar('pddePC.php', 'erro', "Erro ao atualizar Análise Financeira!");                 
            }
        }                              
    }              
}

if (isset($_REQUEST['registrarSIGPC']) && $_REQUEST['registrarSIGPC'] == true) {                        
    if ($idStatus < Processo::STATUS_AF_CONCLUIDO) {        
        redirecionar('pddePC.php', 'erro', "A Análise Financeira da Prestação de Contas ainda não foi concluída.");
    } else if ($idStatus == Processo::STATUS_CONCLUIDO) {        
        redirecionar('pddePC.php', 'erro', "A Prestação de Contas já foi concluída.");        
    } else {                               
        if ($processoModel->registrarSIGPC($idProc)) {
            $_SESSION['aba_ativa'] = 'analiseFinanceira';
            redirecionar('pddePC.php', 'sucesso', "Registrou a Prestação de contas no SIGPC");            
        }
    }
}

// --- 8. GERENCIAMENTO DE PENDÊNCIAS ---
if (isset($_GET['reg']) && $_GET['reg'] == true) {
    $_SESSION['aba_ativa'] = 'pendencias';
    $idPend = $_GET['idPend'];
    
    if ($pendenciaModel->regularizarPendencia($idPend)) {    
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Regularizou a pendência de id " . $idPend
        ]);
        redirecionar('pddePC.php', 'sucesso', "Pendência regularizada com sucesso");        
    } else {        
        redirecionar('pddePC.php', 'erro', "Erro ao regularizar pendência");        
    }
}

if (isset($_GET['delPend']) && $_GET['delPend'] == true) {
    $_SESSION['aba_ativa'] = 'pendencias';
    $idPend = $_GET['idPend'];
    
    if ($pendenciaModel->deletarPendencia($idPend, $currentUser)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Deletou a pendência de id " . $idPend
        ]);          
        redirecionar('pddePC.php', 'sucesso', "Apagou pendência com sucesso");        
    } else {        
        redirecionar('pddePC.php', 'erro', "Erro ao apagar a pendência");        
    }                    
}

if (isset($_REQUEST['newPend']) && $_REQUEST['newPend'] == true && $_SERVER['REQUEST_METHOD'] === 'POST') {                        
    $_SESSION['aba_ativa'] = 'pendencias';        
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    
    if ($pendenciaModel->save($postData, $idProc, $currentUser)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Inseriu nova pendência no processo de id " . $idProc
        ]);
        redirecionar('pddePC.php', 'sucesso', "Incluiu nova pendência com sucesso");        
    } else {        
        redirecionar('pddePC.php', 'erro', "Erro ao incluir pendência");        
    }                       
}

if (isset($_REQUEST['updatePend']) && $_REQUEST['updatePend'] == true && $_SERVER['REQUEST_METHOD'] === 'POST') {   
    $_SESSION['aba_ativa'] = 'pendencias';          
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    
    if ($pendenciaModel->save($postData, $idProc, $currentUser)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Atualizou a pendência de " . $postData['idPendM']
        ]);
        redirecionar('pddePC.php', 'sucesso', "Atualizou a pendência com sucesso");        
    } else {    
        redirecionar('pddePC.php', 'erro', "Erro ao atualizar pendência");        
    }
}

// Controle do Modal de Pendências (Edição vs Inclusão)
if (isset($_GET['editPend']) && $_GET['editPend'] == true) {
    $_SESSION['aba_ativa'] = 'pendencias';
    $idPend = $_GET['idPend'];
    $pend = $pendenciaModel->findById($idPend);
    
    if ($pend) {
        $idPendM = $pend->id;
        $iDRDM = $pend->itemDRD;
        $docPendIdM = $pend->docPend_id;
        $favorecidoM = $pend->favorecido;
        $dataDocPendM = $pend->dataDocPend;
        $numDocPendM = $pend->numDocPend;
        $pendIdM = $pend->pend_id;
        $providenciasM = $pend->providencias;
        $etapaIdM = $pend->etapa_id;
    }
    
    $modalToOpen = 'pendenciaModal'; // Abertura silenciosa e nativa do modal
    $actionP = "?updatePend=true";
    $tituloP = "Atualizar Pendência";
    $botaoP = '<input type="submit" class="btn btn-warning" value="Atualizar"/>';                
} else {
    $actionP = "?newPend=true";
    $tituloP = "Nova Pendência";
    $botaoP = '<input type="submit" class="btn btn-success" value="Incluir"/>';                
} 

// ====================================================================
// Lê qual aba deve estar ativa no carregamento da página
// ====================================================================
$abaAtiva = $_SESSION['aba_ativa'] ?? 'dadosGerais';
unset($_SESSION['aba_ativa']);

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
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    <title>Prestação de Contas - PDDE</title>
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Prestação de Contas 2025 - PDDE
                </h1>
            </div>
            <!-- Início do Conteúdo  -->
            <div class="container-fluid mb-4">
                <div class="row g-3"> 
                    <div class="col-12 col-md-6">
                        <div class="input-group input-group-sm mb-2">
                            <label class="input-group-text col-4 col-xl-3" for="nomeEnt">Entidade</label>
                            <input type="text" id="nomeEnt" name="nomeEnt" value="<?= htmlspecialchars($iNome ?? '') ?>" class="form-control" readonly/>
                        </div>        
                        <div class="input-group input-group-sm">
                            <label class="input-group-text col-4 col-xl-3" for="processo">Processo</label>
                            <input type="text" id="processo" name="campo3" value="<?= htmlspecialchars($numProcesso ?? '') ?>" class="form-control" readonly/>
                        </div>
                    </div>    
                    
                    <div class="col-12 col-md-6">
                        <div class="input-group input-group-sm mb-2">
                            <label class="input-group-text col-4 col-xl-3" for="assuntoProc">Assunto</label>
                            <input type="text" id="assuntoProc" name="assuntoProc" value="<?= htmlspecialchars($tipoProcesso ?? '') ?>" class="form-control" readonly/>
                        </div>
                        <div class="input-group input-group-sm">
                            <label class="input-group-text col-4 col-xl-3" for="statusProc">Status</label>
                            <input type="text" id="statusProc" name="statusProc" value="<?= htmlspecialchars($statusPC ?? '') ?>" class="form-control" readonly/>
                        </div>                          
                    </div>
                    
                </div>
            </div>
            <div class="container-fluid">        
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <button class="nav-link <?= $abaAtiva == 'dadosGerais' ? 'active' : '' ?>" id="nav-dados-tab" data-bs-toggle="tab" data-bs-target="#nav-dados" type="button" role="tab" aria-controls="nav-dados" aria-selected="<?= $abaAtiva == 'dadosGerais' ? 'true' : 'false' ?>">Dados Gerais</button>
                        <button class="nav-link <?= $abaAtiva == 'dadosFinanceiros' ? 'active' : '' ?>" id="nav-dadosfin-tab" data-bs-toggle="tab" data-bs-target="#nav-dadosfin" type="button" role="tab" aria-controls="nav-dadosfin" aria-selected="<?= $abaAtiva == 'dadosFinanceiros' ? 'true' : 'false' ?>">Dados Financeiros</button>
                        <button class="nav-link <?= $abaAtiva == 'analiseExecucao' ? 'active' : '' ?>" id="nav-quali-tab" data-bs-toggle="tab" data-bs-target="#nav-quali" type="button" role="tab" aria-controls="nav-quali" aria-selected="<?= $abaAtiva == 'analiseExecucao' ? 'true' : 'false' ?>">Análise da Execução</button>
                        <button class="nav-link <?= $abaAtiva == 'analiseFinanceira' ? 'active' : '' ?>" id="nav-finan-tab" data-bs-toggle="tab" data-bs-target="#nav-finan" type="button" role="tab" aria-controls="nav-finan" aria-selected="<?= $abaAtiva == 'analiseFinanceira' ? 'true' : 'false' ?>">Análise Financeira</button>
                        <button class="nav-link <?= $abaAtiva == 'pendencias' ? 'active' : '' ?>" id="nav-pendencia-tab" data-bs-toggle="tab" data-bs-target="#nav-pendencia" type="button" role="tab" aria-controls="nav-pendencia" aria-selected="<?= $abaAtiva == 'pendencias' ? 'true' : 'false' ?>">Histórico de Pendências</button>              
                    </div>
                </nav>   
            
                <div class="tab-content" id="nav-tabContent">

                    <!-- DADOS GERAIS -->
                    <div class="tab-pane fade <?= $abaAtiva == 'dadosGerais' ? 'show active' : '' ?>" id="nav-dados" role="tabpanel" aria-labelledby="nav-dados-tab" tabindex="0">
                        <div class="container-fluid mt-3"> 
                            <div class="row g-4"> 
                                <div class="col-12 col-md-6">
                                    <h6 class="mb-3 text-secondary border-bottom pb-2">Entidade</h6>
                                    
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inep">INEP</label>
                                        <input type="text" id="inep" name="inep" value="<?= htmlspecialchars($inep ?? '') ?>" class="form-control" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="cnpj">CNPJ</label>
                                        <input type="text" id="cnpj" name="cnpj" value="<?= htmlspecialchars($cnpj ?? '') ?>" class="form-control" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="email">E-mail</label>
                                        <input type="text" id="email" name="email" value="<?= htmlspecialchars($iEmail ?? '') ?>" class="form-control" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="endereco">Endereço</label>
                                        <input type="text" id="endereco" name="campo2" value="<?= htmlspecialchars($iEndereco ?? 'Rua Tiradentes, 3180 - Montanhão') ?>" class="form-control" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="telefone">Telefone</label>
                                        <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($iTelefone ?? '') ?>" class="form-control" readonly/>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">                                    
                                    <h6 class="mb-3 text-secondary border-bottom pb-2">Contabilidade</h6>
                                    
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="nomeC">Nome</label>
                                        <input type="text" id="nomeC" name="nomeC" value="<?= htmlspecialchars($cNome ?? '') ?>" class="form-control" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="telefoneC">Telefone</label>
                                        <input type="text" id="telefoneC" name="telefoneC" value="<?= htmlspecialchars($cTelefone ?? '') ?>" class="form-control" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="emailC">E-mail</label>
                                        <input type="text" id="emailC" name="emailC" value="<?= htmlspecialchars($cEmail ?? '') ?>" class="form-control" readonly/>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <!-- DADOS FINANCEIROS -->
                    <div class="tab-pane fade <?= $abaAtiva == 'dadosFinanceiros' ? 'show active' : '' ?>" id="nav-dadosfin" role="tabpanel" aria-labelledby="nav-dadosfin-tab" tabindex="0">
                        <div class="container-fluid mt-3">                            
                            <div class="row g-4">                                
                                <div class="col-12 col-xl-6">                                    
                                    <div class="mb-4"> 
                                        <h6 class="mb-3 text-secondary border-bottom pb-2">Saldo Bancário em 31/12/2024</h6>
                                        <div class="table-responsive">
                                            <table class="table table-light table-hover table-sm align-middle">
                                                <thead>                                    
                                                    <tr class="text-center">
                                                        <th scope="col">Agência</th>
                                                        <th scope="col">Conta</th>
                                                        <th scope="col">Saldo Conta</th>
                                                        <th scope="col">Saldo Poupança</th>
                                                        <th scope="col">Saldo Fundos</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $somaCorrenteLY = 0;
                                                    $somaPoupancaLY = 0;
                                                    $somaFundosLY = 0;

                                                    $contas = $bancoModel->findByProcId($idProc);                                            
                                                    if ($contas):
                                                        foreach($contas as $ly):                                            
                                                            $agencia = htmlspecialchars($ly->agencia ?? '');
                                                            $conta = htmlspecialchars($ly->conta ?? '');
                                                            
                                                            $sCorrenteLY = (float)($ly->cc_LY ?? 0);
                                                            $sPoupancaLY = (float)($ly->pp_01_LY ?? 0) + (float)($ly->pp_51_LY ?? 0);
                                                            $sFundosLY = (float)($ly->spubl_LY ?? 0) + (float)($ly->bb_rf_cp_LY ?? 0);

                                                            $somaCorrenteLY += $sCorrenteLY;
                                                            $somaPoupancaLY += $sPoupancaLY;
                                                            $somaFundosLY += $sFundosLY;
                                                    ?>
                                                        <tr class="text-center">
                                                            <td><?= $agencia ?></td>
                                                            <td><?= $conta ?></td>
                                                            <td>R$ <?= number_format($sCorrenteLY, 2, ',', '.') ?></td>
                                                            <td>R$ <?= number_format($sPoupancaLY, 2, ',', '.') ?></td>
                                                            <td>R$ <?= number_format($sFundosLY, 2, ',', '.') ?></td>
                                                        </tr>
                                                    <?php 
                                                        endforeach; 
                                                    endif; 
                                                    ?>                                    
                                                </tbody>
                                                <tfoot class="table-group-divider text-center">
                                                    <tr>
                                                        <th scope="row" colspan="2">Total</th>
                                                        <th>R$ <?= number_format($somaCorrenteLY, 2, ',', '.') ?></th>
                                                        <th>R$ <?= number_format($somaPoupancaLY, 2, ',', '.') ?></th>
                                                        <th>R$ <?= number_format($somaFundosLY, 2, ',', '.') ?></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                    <div>
                                        <h6 class="mb-3 text-secondary border-bottom pb-2">Saldo Bancário em 31/12/2025</h6>
                                        <div class="table-responsive">
                                            <table class="table table-light table-hover table-sm align-middle">
                                                <thead>                                    
                                                    <tr class="text-center">
                                                        <th scope="col">Agência</th>
                                                        <th scope="col">Conta</th>
                                                        <th scope="col">Saldo Conta</th>
                                                        <th scope="col">Saldo Poupança</th>
                                                        <th scope="col">Saldo Fundos</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $somaCorrenteCY = 0;
                                                    $somaPoupancaCY = 0;
                                                    $somaFundosCY = 0;
                                                    
                                                    if (!empty($cys)):
                                                        foreach($cys as $cy):                                            
                                                            $agencia = htmlspecialchars($cy->agencia ?? '');
                                                            $conta = htmlspecialchars($cy->conta ?? '');
                                                            
                                                            $sCorrenteCY = (float)($cy->cc_CY ?? 0);
                                                            $sPoupancaCY = (float)($cy->pp_01_CY ?? 0) + (float)($cy->pp_51_CY ?? 0);
                                                            $sFundosCY = (float)($cy->spubl_CY ?? 0) + (float)($cy->bb_rf_cp_CY ?? 0);

                                                            $somaCorrenteCY += $sCorrenteCY;
                                                            $somaPoupancaCY += $sPoupancaCY;
                                                            $somaFundosCY += $sFundosCY;
                                                    ?>
                                                        <tr class="text-center">
                                                            <td><?= $agencia ?></td>
                                                            <td><?= $conta ?></td>                                                
                                                            <td>R$ <?= number_format($sCorrenteCY, 2, ',', '.') ?></td>
                                                            <td>R$ <?= number_format($sPoupancaCY, 2, ',', '.') ?></td>
                                                            <td>R$ <?= number_format($sFundosCY, 2, ',', '.') ?></td>
                                                        </tr>
                                                    <?php 
                                                        endforeach; 
                                                    endif; 
                                                    ?>                                    
                                                </tbody>
                                                <tfoot class="table-group-divider text-center">
                                                    <tr>
                                                        <th scope="row" colspan="2">Total</th>
                                                        <th>R$ <?= number_format($somaCorrenteCY, 2, ',', '.') ?></th>
                                                        <th>R$ <?= number_format($somaPoupancaCY, 2, ',', '.') ?></th>
                                                        <th>R$ <?= number_format($somaFundosCY, 2, ',', '.') ?></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    
                                </div> 
                                <div class="col-12 col-xl-6">
                                    <h6 class="mb-3 text-secondary border-bottom pb-2">Repasse 2025</h6>
                                    <div class="table-responsive">
                                        <table class="table table-light table-hover table-sm align-middle">
                                            <thead>
                                                <tr class="text-center">
                                                    <th scope="col">Destinação</th>
                                                    <th scope="col">Custeio</th>
                                                    <th scope="col">Capital</th>
                                                    <th scope="col">Total</th>
                                                    <th scope="col">Ord. Pgto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                                $somaCusteio = 0;
                                                $somaCapital = 0;
                                                $somaRepasse = 0;

                                                $repasses = $repasseModel->findById($idProc);                                            
                                                if($repasses):
                                                    foreach($repasses as $repasse):
                                                        $destinacao = htmlspecialchars($repasse->destinacao ?? '');
                                                        $rCusteio = (float)($repasse->custeio ?? 0);
                                                        $rCapital = (float)($repasse->capital ?? 0);
                                                        $rTotal = $rCusteio + $rCapital;
                                                        
                                                        $rData = $repasse->data;
                                                        $dataRepasse = $rData ? (new DateTime($rData, $timezone))->format('d/m/Y') : '-';

                                                        $somaCusteio += $rCusteio;
                                                        $somaCapital += $rCapital;
                                                        $somaRepasse += $rTotal;
                                            ?>
                                                    <tr class="text-center">
                                                        <td><?= $destinacao ?></td>
                                                        <td>R$ <?= number_format($rCusteio, 2, ',', '.') ?></td>
                                                        <td>R$ <?= number_format($rCapital, 2, ',', '.') ?></td>                                        
                                                        <td>R$ <?= number_format($rTotal, 2, ',', '.') ?></td>
                                                        <td><?= $dataRepasse ?></td>
                                                    </tr>
                                            <?php 
                                                    endforeach;
                                                endif;                                           
                                            ?>
                                            </tbody>
                                            <tfoot class="table-group-divider text-center">
                                                <tr>
                                                    <th scope="row">Total</th>
                                                    <th>R$ <?= number_format($somaCusteio, 2, ',', '.') ?></th>
                                                    <th>R$ <?= number_format($somaCapital, 2, ',', '.') ?></th>
                                                    <th>R$ <?= number_format($somaRepasse, 2, ',', '.') ?></th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ANÁLISE DA EXECUÇÃO -->
                    <div class="tab-pane fade <?= $abaAtiva == 'analiseExecucao' ? 'show active' : '' ?>" id="nav-quali" role="tabpanel" aria-labelledby="nav-quali-tab" tabindex="0">                
                        <form method="POST" action="?saveExec=true">
                            <div class="container-fluid mt-4">
                                <div class="row g-4 mb-4">                                    
                                    <div class="col-12 col-md-6">
                                        <h6 class="mb-3 text-secondary border-bottom pb-2">Análise da Execução</h6>

                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-4" for="dataEntrega">Data da Entrega</label>
                                            <input type="text" id="dataEntrega" name="dataEntrega" value="<?= htmlspecialchars($entrega ?? '') ?>" class="form-control" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-4" for="usuarioQ">Responsável</label>
                                            <input type="text" id="usuarioQ" name="usuario" value="<?= htmlspecialchars($nomeUsuarioEx ?? '') ?>" class="form-control" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-4" for="dataAnalQ">Data da Análise</label>
                                            <input type="text" id="dataAnalQ" name="dataAnal" value="<?= htmlspecialchars($dataAnaliseEx ?? '') ?>" class="form-control" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-3">
                                            <label class="input-group-text col-4" for="dataEncFin">Enc. Anál. Financeira</label>
                                            <input type="text" id="dataEncFin" name="dataEncFin" value="<?= htmlspecialchars($dataEncAf ?? '') ?>" class="form-control" readonly/>
                                        </div>
                                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                                            <div>
                                                <?php if($idStatus == Processo::STATUS_AGUARDANDO_ENTREGA): ?>
                                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#entregaModal">Registrar Entrega</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#despesaModal">Incluir Nova Despesa</button>
                                                <?php endif; ?>                                            
                                            </div>
                                            <div class="form-check form-switch">
                                                <input type="checkbox" name="checkMov" class="form-check-input" value="1" role="switch" id="checkMovimento" <?= $movimento ?? '' ?>>
                                                <label class="form-check-label text-muted" for="checkMovimento">Sem Movimento</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 col-md-6">
                                        <h6 class="mb-3 text-secondary border-bottom pb-2">Observações</h6>
                                        <div class="form-floating h-100 pb-4"> 
                                            <textarea name="analObs" class="form-control h-100" placeholder="Detalhes" id="analObs"><?= htmlspecialchars($obsAnaliseEx ?? '') ?></textarea>
                                            <label for="analObs">Descreva as observações aqui...</label>
                                        </div>
                                    </div>                                                               
                                </div>                        

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr class="text-center align-middle">
                                                        <th scope="col" class="fw-semibold">Item</th>
                                                        <th scope="col" class="fw-semibold">Categoria</th>
                                                        <th scope="col" class="fw-semibold">Ação</th>
                                                        <th scope="col" class="fw-semibold text-start">Fornecedor</th>
                                                        <th scope="col" class="fw-semibold">CNPJ</th>
                                                        <th scope="col" class="fw-semibold text-start">Bens/Serviços</th>
                                                        <th scope="col" class="fw-semibold">Nº Doc</th>
                                                        <th scope="col" class="fw-semibold">Dt Emissão</th>
                                                        <th scope="col" class="fw-semibold">Ident. Pgto</th>
                                                        <th scope="col" class="fw-semibold">Valor</th>
                                                        <th scope="col" class="fw-semibold">Ações</th>                                        
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $nItem = 0;
                                                    $total = 0;                                            
                                                    $despesasPendentes = 0;                                                
                                                    if(!empty($despesas)):
                                                        foreach($despesas as $despesa):                                                                                                            
                                                            $idDesp = htmlspecialchars($despesa->id ?? '');
                                                            $idAcao = $despesa->acao_id;
                                                            $categoria = $despesa->categoria == 'C' ? 'Custeio' : ($despesa->categoria == 'K' ? 'Capital' : htmlspecialchars($despesa->categoria));
                                                            $fornecedorId = $despesa->fornecedor_id ?? '';                                                            ;
                                                            $descricao = htmlspecialchars($despesa->descricao ?? '');
                                                            $numDoc = htmlspecialchars($despesa->documento ?? '');
                                                            $numPgto = htmlspecialchars($despesa->pagamento ?? '');
                                                            $valor = (float)($despesa->valor ?? 0);

                                                            // Validações
                                                            $checkProg = $despesa->check_prog;
                                                            $checkAta = $despesa->check_ata;
                                                            $checkEnq = $despesa->check_enq;
                                                            $checkCons = $despesa->check_cons;
                                                            
                                                            $fornecedor = htmlspecialchars($despesaModel->findFornecedorById($fornecedorId)->razao_social ?? '');
                                                            $cnpjForn = htmlspecialchars($despesaModel->findFornecedorById($fornecedorId)->cnpj ?? '');

                                                            $acaoDesp = '';
                                                            $programa = $programaModel->findById($idAcao);                                                      
                                                            if($programa) {                                                                                                                                                                                    
                                                                $acaoDesp = htmlspecialchars($programa->acao);                                                              
                                                            }                                                                                                                  

                                                            // Formatação do CNPJ e Data
                                                            if (strlen($cnpjForn) >= 14) {
                                                                $cnpjForn = substr($cnpjForn,0,2) . "." . substr($cnpjForn,2,3) . "." . substr($cnpjForn,5,3) . "/" . substr($cnpjForn,8,4) . "-" . substr($cnpjForn,12,2); 
                                                            }
                                                            $data = new DateTime($despesa->data_desp, $timezone);
                                                            $dataDesp = $data->format('d/m/Y');
                                                            
                                                            // Definição da classe da linha
                                                            if($checkProg == false || $checkCons == false || $checkEnq == false || $checkAta == false){
                                                                $backPendente = "table-danger";
                                                                $despesasPendentes++;
                                                            } else {
                                                                $backPendente = "table-success";
                                                            }
                                                            
                                                            $nItem++;
                                                            $total += $valor;
                                                    ?>
                                                        <tr class="<?= $backPendente ?>">
                                                            <td class="text-center"><?= $nItem ?></td>
                                                            <td><?= $categoria ?></td>
                                                            <td><?= $acaoDesp ?></td>
                                                            <td class="text-start"><?= $fornecedor ?></td>
                                                            <td class="text-center text-nowrap"><?= $cnpjForn ?></td>
                                                            <td class="text-start"><?= $descricao ?></td>
                                                            <td class="text-center"><?= $numDoc ?></td>
                                                            <td class="text-center"><?= $dataDesp ?></td>
                                                            <td class="text-center"><?= $numPgto ?></td>
                                                            <td class="text-end text-nowrap">R$ <?= number_format($valor, 2, ",", ".") ?></td>
                                                            <td class="text-center">
                                                                <a href="?editDesp=true&idDesp=<?= $idDesp ?>" class="text-decoration-none me-2">
                                                                    <img src="img/pencil-alt.svg" alt="Editar" title="Editar" />
                                                                </a>
                                                                <a href="?delDesp=true&idDesp=<?= $idDesp ?>" class="text-decoration-none">
                                                                    <img src="img/na.svg" alt="Deletar" title="Deletar"/>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php                                                    
                                                        endforeach;
                                                    endif;
                                                    ?>                                   
                                                </tbody>
                                                <tfoot class="table-group-divider">
                                                    <tr>
                                                        <th scope="row" colspan="9" class="text-end pe-3">Total</th>
                                                        <th class="text-end text-nowrap">R$ <?= number_format($total, 2, ",", ".") ?></th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                                        
                                <div class="row">                            
                                    <div class="col-12 text-end">                            
                                        <?php if($idStatus == Processo::STATUS_RECEBIDO): ?>
                                            <input type="submit" class="btn btn-success" value="Gravar Status" />
                                        <?php elseif ($idStatus > Processo::STATUS_RECEBIDO && $savedFlag == 1): ?>
                                            <input type="submit" class="btn btn-warning me-2" value="Atualizar Status" />
                                            <button type="button" class="btn btn-primary" onclick="location.href='?encFin=true'">Encaminhar Análise Financeira</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>                        
                    </div>

                    <!-- ANÁLISE FINANCEIRA -->
                    <div class="tab-pane fade <?= $abaAtiva == 'analiseFinanceira' ? 'show active' : '' ?>" id="nav-finan" role="tabpanel" aria-labelledby="nav-finan-tab" tabindex="0">
                        <div class="container-fluid mt-4">                    
                            <?php
                            // Lógica de busca e tratamento de dados
                            $proc = $processoModel->abrirTramitacao($idProc);
                            
                            $userFin = '';
                            $dataAnaliseFin = '';
                            $dataSigpc = '';
                            $finObs = '';
                            $checkEmailFin = '';

                            if(!empty($proc)) {
                                $userFinId = $proc->usuario_fin_id;
                                $finObs = $proc->obs_analise_fin;
                                $emailAf = $proc->email_af;
                                
                                // Busca o nome do usuário responsável
                                if(!empty($userFinId)) {
                                    $user = $userModel->findById($userFinId);
                                    if($user) {
                                        $userFin = $user->nome;
                                    }
                                }

                                // Formata a data de análise, se existir
                                if(!empty($proc->data_analise_fin)) {
                                    $dtAnalise = new DateTime($proc->data_analise_fin, $timezone);
                                    $dataAnaliseFin = $dtAnalise->format("d/m/Y");
                                }

                                // Formata a data SIGPC, se existir
                                if(!empty($proc->data_sigpc)) {
                                    $dtSigpc = new DateTime($proc->data_sigpc, $timezone);
                                    $dataSigpc = $dtSigpc->format("d/m/Y");
                                }

                                // Verifica o checkbox de e-mail
                                if(isset($emailAf) && $emailAf == "1"){
                                    $checkEmailFin = "checked";                    
                                }
                            }
                            ?>
                            <form action="?saveFin=true" method="post">
                                <div class="row g-4 mb-4">
                                    <div class="col-12 col-md-6">
                                        <h6 class="mb-3 text-secondary border-bottom pb-2">Análise Financeira</h6>

                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-4" for="userFin">Responsável</label>
                                            <input type="text" id="userFin" name="userFin" class="form-control" value="<?= htmlspecialchars($userFin) ?>" readonly/>
                                        </div>
                                        
                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-4" for="dataAnalFin">Data da Análise</label>
                                            <input type="text" id="dataAnalFin" name="dataAnalFin" class="form-control" value="<?= htmlspecialchars($dataAnaliseFin) ?>" />
                                        </div>
                                        
                                        <div class="input-group input-group-sm mb-3">
                                            <label class="input-group-text col-4" for="dataSigpc">Data SIGPC</label>
                                            <input type="text" id="dataSigpc" name="dataSigpc" class="form-control" value="<?= htmlspecialchars($dataSigpc) ?>" />
                                        </div>
                                        
                                        <div class="form-check form-switch mt-4">
                                            <input type="checkbox" name="checkEmailFin" class="form-check-input" value="1" role="switch" id="checkEmailFin" <?= $checkEmailFin ?>>
                                            <label class="form-check-label text-muted" for="checkEmailFin">Encaminhado E-mail com Análise Financeira</label>
                                        </div>                         
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <h6 class="mb-3 text-secondary border-bottom pb-2">Observações</h6>
                                        <div class="form-floating h-100 pb-4">
                                            <textarea name="finObs" class="form-control h-100" placeholder="Detalhes" id="finObs"><?= htmlspecialchars($finObs) ?></textarea>
                                            <label for="finObs">Descreva as observações financeiras aqui...</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 text-end">                                
                                        <input type="submit" class="btn btn-warning me-2" value="Atualizar Status" />
                                        <button type="button" class="btn btn-primary" onclick="location.href='?registrarSIGPC=true'">Registrar lançamento no SIGPC</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>                    

                    <!-- PENDÊNCIAS -->
                    <div class="tab-pane fade <?= $abaAtiva == 'pendencias' ? 'show active' : '' ?>" id="nav-pendencia" role="tabpanel" aria-labelledby="nav-pendencia-tab" tabindex="0">
                        <div class="container-fluid mt-4">
                            
                            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-3 gap-3">                       
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pendenciaModal">
                                        + Nova Pendência
                                    </button>
                                    <a href="emailPendencias.php?idProc=<?= $idProc ?>" target="_blank" class="btn btn-success">
                                        Escrever E-mail
                                    </a>
                                </div>
                                <h6 class="m-0 fw-semibold text-secondary">Histórico de Pendências</h6>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr class="text-center align-middle">
                                                    <th scope="col" class="fw-semibold text-nowrap">Data</th>
                                                    <th scope="col" class="fw-semibold">Item DRD</th>
                                                    <th scope="col" class="fw-semibold text-start">Documento</th>
                                                    <th scope="col" class="fw-semibold text-start">Favorecido</th>
                                                    <th scope="col" class="fw-semibold text-nowrap">Nº Doc</th>
                                                    <th scope="col" class="fw-semibold text-nowrap">Dt Emissão</th>
                                                    <th scope="col" class="fw-semibold text-start">Pendência</th>
                                                    <th scope="col" class="fw-semibold text-start">Providências</th>
                                                    <th scope="col" class="fw-semibold">Etapa</th>                                        
                                                    <th scope="col" class="fw-semibold text-nowrap">Dt Regularização</th>
                                                    <th scope="col" class="fw-semibold">Regularizado?</th>
                                                    <th scope="col" class="fw-semibold">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $pends = $pendenciaModel->findByProcId($idProc);
                                                if($pends):
                                                    foreach($pends as $pend):
                                                        if($pend->ativado != 1) continue; // Pula pendências inativas

                                                        $idPend = $pend->id;

                                                        // Formatação de Datas
                                                        $dataPend = (new DateTime($pend->dataPend, $timezone))->format('d/m/Y');
                                                        $dataDocPend = (new DateTime($pend->dataDocPend, $timezone))->format('d/m/Y');
                                                        $dataResolved = !empty($pend->dataResolvido) ? (new DateTime($pend->dataResolvido, $timezone))->format('d/m/Y') : '-';

                                                        // Dados do BD (Seguros contra XSS)
                                                        $itemDRD = htmlspecialchars($pend->itemDRD ?? '');
                                                        $favorecido = htmlspecialchars($pend->favorecido ?? '');
                                                        $numDocPend = htmlspecialchars($pend->numDocPend ?? '');
                                                        $providencias = htmlspecialchars($pend->providencias ?? '');
                                                        
                                                        $checkResolved = $pend->resolvido;
                                                        
                                                        // Buscas relacionadas
                                                        $documentoDesc = $documentoModel->findById($pend->docPend_id);
                                                        $documento = $documentoDesc ? htmlspecialchars($documentoDesc->documento) : '-';
                                                        
                                                        $pendenciaDesc = $pendenciaModel->findTipoById($pend->pend_id);
                                                        $pendencia = $pendenciaDesc ? htmlspecialchars($pendenciaDesc->pendencia) : '-';
                                                        
                                                        // Etapa
                                                        $etapa = "Desconhecida";
                                                        switch($pend->etapa_id) {
                                                            case 1: $etapa = "Juntada"; break;
                                                            case 2: $etapa = "Execução"; break;
                                                            case 3: $etapa = "Financeira"; break;
                                                        }

                                                    ?>
                                                        
                                                        <tr>
                                                            <td class="text-center text-nowrap"><?= $dataPend ?></td>
                                                            <td class="text-center"><?= $itemDRD ?></td>
                                                            <td class="text-start"><?= $documento ?></td>
                                                            <td class="text-start"><?= $favorecido ?></td>
                                                            <td class="text-nowrap"><?= $numDocPend ?></td>
                                                            <td class="text-center text-nowrap"><?= $dataDocPend ?></td>
                                                            <td class="text-start"><?= $pendencia ?></td>
                                                            <td class="text-start"><?= $providencias ?></td>
                                                            <td class="text-center"><?= $etapa ?></td>
                                                            <td class="text-center text-nowrap"><?= $dataResolved ?></td>
                                                            <?php if($checkResolved == 0): ?>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-success btn-sm" onclick="location.href='?reg=true&idPend=<?= $idPend ?>'">Marcar</button>
                                                                </td>
                                                                <td class="text-center text-nowrap">
                                                                    <a href="?editPend=true&idPend=<?= $idPend ?>" class="text-decoration-none me-2">
                                                                        <img src="img/pencil-alt.svg" alt="Editar" title="Editar"/>
                                                                    </a>
                                                                    <a href="?delPend=true&idPend=<?= $idPend ?>" class="text-decoration-none">
                                                                        <img src="img/na.svg" alt="Excluir" title="Excluir"/>
                                                                    </a>
                                                                </td>
                                                            <?php else: ?>
                                                                <td class="text-center fw-bold text-success">SIM</td>
                                                                <td class="text-center">-</td>
                                                            <?php endif; ?>
                                                        </tr> 
                                                    <?php 
                                                    endforeach;
                                                endif; 
                                                ?>                                                                     
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Fim Modal Pendências -->            
                </div>        
            </div>
            
            <!-- Fim do Conteúdo  -->
        </div>
    </div>

    <!-- Modal Entrega -->
    <div class="modal fade" id="entregaModal" tabindex="-1" aria-labelledby="entregaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="entregaModalLabel">Registrar Entrega</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 fs-6">Deseja registrar a entrega da Prestação de Contas?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
                    <button type="button" class="btn btn-success" onclick="location.href='?entrega=true'">Sim, registrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Despesa -->
    <div class="modal fade modal-trigger" id="despesaModal" tabindex="-1" aria-labelledby="despesaModalLabel" aria-hidden="true">                
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= htmlspecialchars($action ?? '') ?>" method="post" name="despesa">
                    
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="despesaModalLabel"><?= htmlspecialchars($titulo ?? '') ?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <input type="hidden" value="<?= htmlspecialchars($idDespM ?? '') ?>" name="idDespM" />
                    </div>
                    
                    <div class="modal-body">
                        <div class="container-fluid">                                    
                            
                            <div class="row mb-2">
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <label class="form-label text-muted small mb-1" for="acaoId">Ação</label>
                                    <select name="acaoId" id="acaoId" class="form-select form-select-sm" required>
                                        <option <?= isset($idAcaoM) && $idAcaoM != null ? '' : 'selected' ?> disabled value="">Selecione...</option>
                                        <?php
                                        $progs = $programaModel->findByProgName($tipoPrograma);
                                        if($progs) {
                                            foreach($progs as $prog):                                                        
                                                $idAcao = $prog->id;                                                      
                                                $acao = htmlspecialchars($prog->acao); 
                                                $selected = (isset($idAcaoM) && $idAcaoM == $idAcao) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($idAcao) . '" ' . $selected . '>' . $acao . '</option>';
                                            endforeach;
                                        }
                                        ?>                                            
                                    </select>                                                    
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small mb-1" for="categoria">Categoria</label>
                                    <select name="categoria" id="categoria" class="form-select form-select-sm" required>
                                        <option <?= isset($categoriaM) && $categoriaM != null ? '' : 'selected' ?> disabled value="">Selecione...</option>                                                    
                                        <option value="C" <?= isset($categoriaM) && $categoriaM == 'C' ? 'selected' : '' ?>>Custeio</option>
                                        <option value="K" <?= isset($categoriaM) && $categoriaM == 'K' ? 'selected' : '' ?>>Capital</option>                                                        
                                    </select>                                                    
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-12">
                                    <label class="form-label text-muted small mb-1" for="fornecedorId">Fornecedor (Razão Social ou CNPJ)</label>
                                    <div class="input-group input-group-sm">
                                        <select name="fornecedor_id" id="fornecedorId" class="form-select select2-fornecedor" required style="width: 80%;">
                                            <?php
                                                // Opção padrão
                                                echo '<option value="" disabled ' . (empty($fornecedorIdM) ? 'selected' : '') . '>Digite para buscar...</option>';

                                                // Busca a lista completa de fornecedores
                                                $todosFornecedores = $despesaModel->findAllFornecedores();

                                                if ($todosFornecedores) {
                                                    foreach ($todosFornecedores as $forn) {
                                                        // Máscara de CNPJ no PHP
                                                        $cnpjLimpo = preg_replace("/[^0-9]/", "", $forn->cnpj);
                                                        $cnpjMask = strlen($cnpjLimpo) == 14 ? substr($cnpjLimpo,0,2).".".substr($cnpjLimpo,2,3).".".substr($cnpjLimpo,5,3)."/".substr($cnpjLimpo,8,4)."-".substr($cnpjLimpo,12,2) : $forn->cnpj;
                                                        
                                                        // Verifica se este é o fornecedor que já estava salvo na despesa
                                                        $selected = (isset($fornecedorIdM) && $fornecedorIdM == $forn->id) ? 'selected' : '';
                                                        
                                                        // Imprime a opção
                                                        echo '<option value="' . htmlspecialchars($forn->id) . '" ' . $selected . '>' . htmlspecialchars($forn->razao_social) . ' - ' . $cnpjMask . '</option>';
                                                    }
                                                }
                                            ?>
                                        </select>
                                        <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#novoFornecedorModal" title="Cadastrar Novo Fornecedor">
                                            <i class="lni lni-plus"></i> Novo
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-12">
                                    <label class="form-label text-muted small mb-1" for="descDesp">Aquisição</label>
                                    <input type="text" id="descDesp" name="descDesp" class="form-control form-control-sm" value="<?= htmlspecialchars($descricaoM ?? '') ?>" required />
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <label class="form-label text-muted small mb-1" for="numDoc">Nº Documento</label>
                                    <input type="text" id="numDoc" name="numDoc" class="form-control form-control-sm" value="<?= htmlspecialchars($numDocM ?? '') ?>" required />
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small mb-1" for="numPgto">Ident. Pagamento</label>
                                    <input type="text" id="numPgto" name="numPgto" class="form-control form-control-sm" value="<?= htmlspecialchars($numPgtoM ?? '') ?>" required />
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <label class="form-label text-muted small mb-1" for="dataDoc">Data</label>
                                    <input type="date" id="dataDoc" name="dataDoc" class="form-control form-control-sm" value="<?= htmlspecialchars($dataDespM ?? '') ?>" required />
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small mb-1" for="valDesp">Valor</label>
                                    <input type="text" id="valDesp" name="valDesp" class="form-control form-control-sm mascara-moeda" value="<?= htmlspecialchars($valorReal ?? '') ?>" required />
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12 col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input type="checkbox" name="checkProg" class="form-check-input" value="1" role="switch" id="checkProg" <?= $checkProgM ?? '' ?>>
                                        <label class="form-check-label text-muted" for="checkProg">De Acordo com o Programa?</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input type="checkbox" name="checkEnquad" class="form-check-input" value="1" role="switch" id="checkEnquad" <?= $checkEnqM ?? '' ?>>
                                        <label class="form-check-label text-muted" for="checkEnquad">Enquadramento Correto?</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">                                                        
                                    <div class="form-check form-switch mb-2">
                                        <input type="checkbox" name="checkAta" class="form-check-input" value="1" role="switch" id="checkAta" <?= $checkAtaM ?? '' ?>>
                                        <label class="form-check-label text-muted" for="checkAta">Possui Ata de deliberação?</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input type="checkbox" name="checkConso" class="form-check-input" value="1" role="switch" id="checkConso" <?= $checkConsM ?? '' ?>>
                                        <label class="form-check-label text-muted" for="checkConso">Possui Consolidação de Preços?</label>
                                    </div>
                                </div>
                            </div>

                        </div>                                                    
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <?= $botao; ?>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para Cadastrar Novo Fornecedor -->
    <div class="modal fade" id="novoFornecedorModal" aria-hidden="true" aria-labelledby="novoFornecedorLabel" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-success">
                
                <form id="formNovoFornecedor" action="" method="post">
                    <input type="hidden" name="acaoAjax" value="salvar_fornecedor">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="novoFornecedorLabel"><i class="lni lni-delivery"></i> Cadastrar Fornecedor</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        
                        <div class="mb-3">
                            <label for="cnpjForn" class="form-label text-muted small mb-1">CNPJ</label>
                            <input type="text" class="form-control form-control-sm mascara-cnpj" id="cnpjForn" name="cnpj" placeholder="00.000.000/0000-00" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="razaoSocialForn" class="form-label text-muted small mb-1">Razão Social / Nome Fantasia</label>
                            <input type="text" class="form-control form-control-sm" id="razaoSocialForn" name="razao_social" required>
                        </div>

                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-target="#despesaModal" data-bs-toggle="modal">Voltar</button>
                        <button type="submit" class="btn btn-success" id="btnSalvarFornecedor">Salvar Fornecedor</button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
    
    <div class="modal fade modal-trigger" id="avancarPend" tabindex="-1" aria-labelledby="avancarPendLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5 text-warning" id="avancarPendLabel">Atenção!</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <p class="mb-1">Existe(m) <strong><?= htmlspecialchars($despesasPendentes ?? 0) ?></strong> pendência(s).</p>
                        <p class="mb-0">Deseja realmente avançar para a análise financeira mesmo assim?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
                    <button type="button" class="btn btn-warning" onclick="location.href='?forceFin=true'">Sim, avançar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Pendências -->
    <div class="modal fade" id="pendenciaModal" tabindex="-1" aria-labelledby="pendenciaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= htmlspecialchars($actionP ?? '') ?>" method="post" name="pendencia">
                    
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="pendenciaModalLabel"><?= htmlspecialchars($tituloP ?? '') ?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <input type="hidden" value="<?= htmlspecialchars($idPendM ?? '') ?>" name="idPendM" />
                    </div>
                    
                    <div class="modal-body">
                        <div class="container-fluid">
                            
                            <div class="row mb-2">                                        
                                <div class="col-12 col-md-4 mb-2 mb-md-0">
                                    <div class="input-group input-group-sm">
                                        <label class="input-group-text col-4" for="itemDRD">Item DRD</label>
                                        <input type="text" id="itemDRD" name="itemDRD" class="form-control" value="<?= htmlspecialchars($iDRDM ?? '') ?>" />
                                    </div>
                                </div>
                                <div class="col-12 col-md-8">
                                    <div class="input-group input-group-sm">
                                        <label class="input-group-text col-4 col-md-3" for="docPend">Documento</label>
                                        <select name="docPend" id="docPend" class="form-select" required>                                                    
                                            <option disabled <?= isset($docPendIdM) && $docPendIdM != null ? '' : 'selected' ?> value="">Selecione...</option>
                                            <?php
                                            $docs = $documentoModel->all();
                                            if($docs) {
                                                foreach($docs as $doc):
                                                    if($doc->pdde == 1) { // Só exibe se for pdde == 1
                                                        $idDoc = $doc->id;                                                              
                                                        $docPend = htmlspecialchars($doc->documento);
                                                        $selected = (isset($docPendIdM) && $docPendIdM == $idDoc) ? 'selected' : '';
                                                        echo '<option value="' . htmlspecialchars($idDoc) . '" ' . $selected . '>' . $docPend . '</option>';
                                                    }                                                        
                                                endforeach;
                                            }
                                            ?>                                                   
                                        </select>                                                    
                                    </div>
                                </div>
                            </div>                                    
                            
                            <div class="row mb-2">                                        
                                <div class="col-12">
                                    <div class="input-group input-group-sm">
                                        <label class="input-group-text col-4 col-md-2" for="favorecido">Favorecido</label>
                                        <input type="text" id="favorecido" name="favorecido" class="form-control" value="<?= htmlspecialchars($favorecidoM ?? '') ?>" />
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                    <div class="input-group input-group-sm">
                                        <label class="input-group-text col-4" for="dataDocP">Data Doc.</label>
                                        <input type="date" id="dataDocP" name="dataDocP" class="form-control" value="<?= htmlspecialchars($dataDocPendM ?? '') ?>" />
                                    </div>                                                  
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="input-group input-group-sm">
                                        <label class="input-group-text col-4" for="numDocP">Nº Documento</label>
                                        <input type="text" id="numDocP" name="numDocP" class="form-control" value="<?= htmlspecialchars($numDocPendM ?? '') ?>" />
                                    </div>
                                </div>                                        
                            </div>
                            
                            <div class="row mb-2">                                        
                                <div class="col-12">
                                    <div class="input-group input-group-sm">
                                        <label class="input-group-text col-4 col-md-2" for="pendencia">Pendência</label>
                                        <select name="pendencia" id="pendencia" class="form-select">
                                            <option disabled <?= isset($pendIdM) && $pendIdM != null ? '' : 'selected' ?> value="">Selecione...</option>
                                            <?php
                                            $tipos = $pendenciaModel->allTipos();
                                            if($tipos) {
                                                foreach($tipos as $tipo) {
                                                    $idTipoPend = $tipo->id;
                                                    $tipoPend = htmlspecialchars($tipo->pendencia);
                                                    $selected = (isset($pendIdM) && $pendIdM == $idTipoPend) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($idTipoPend) . '" ' . $selected . '>' . $tipoPend . '</option>';                                                        
                                                }
                                            }
                                            ?>                                                                                                            
                                        </select>                                                    
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">                                        
                                <div class="col-12">
                                    <div class="input-group input-group-sm">
                                        <label class="input-group-text col-4 col-md-2" for="providencias">Providências</label>
                                        <textarea id="providencias" name="providencias" class="form-control" rows="3" maxlength="1024"><?= htmlspecialchars($providenciasM ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>                                
                            
                            <div class="row">
                                <div class="col-12">
                                    <span class="fw-semibold text-secondary me-3 d-block d-md-inline mb-2">Etapa da Pendência:</span>
                                    
                                    <div class="form-check form-check-inline">
                                        <input type="radio" name="etapaPend" class="form-check-input" value="1" id="rJuntada" <?= isset($etapaIdM) && $etapaIdM == 1 ? "checked" : "" ?> />
                                        <label class="form-check-label" for="rJuntada">Juntada</label>
                                    </div>                                    
                                    <div class="form-check form-check-inline">
                                        <input type="radio" name="etapaPend" class="form-check-input" value="2" id="rExecucao" <?= isset($etapaIdM) && $etapaIdM == 2 ? "checked" : "" ?> />
                                        <label class="form-check-label" for="rExecucao">Execução</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" name="etapaPend" class="form-check-input" value="3" id="rFinanceira" <?= isset($etapaIdM) && $etapaIdM == 3 ? "checked" : "" ?> />
                                        <label class="form-check-label" for="rFinanceira">Financeira</label>
                                    </div>  
                                </div>
                            </div>
                                                              
                        </div>                                                    
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <?= $botaoP ?>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>

    <?php if (!empty($modalToOpen)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var myModal = new bootstrap.Modal(document.getElementById('<?= $modalToOpen ?>'));
            myModal.show();
        });
    </script>
    <?php endif; ?>
    
    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // APLICAR MÁSCARAS AOS INPUTS

            // Aplica a máscara de dinheiro da direita para a esquerda
            // 1. A formatação inteligente (substitui o .mask do plugin)
            $('.mascara-moeda').on('input', function() {
                let valor = $(this).val();
                
                // Verifica e guarda se o usuário já colocou o sinal de negativo
                let isNegative = valor.includes('-');
                
                // Extrai apenas os números, ignorando pontos, vírgulas e letras
                let numeros = valor.replace(/\D/g, '');
                
                if (numeros === '') {
                    $(this).val('');
                    return;
                }
                
                // Divide por 100 para criar os centavos automaticamente (ex: "1500" vira 15.00)
                let decimais = parseFloat(numeros) / 100;
                
                // Usa o formatador nativo do navegador para o padrão brasileiro (R$ 1.500,00)
                let formatado = decimais.toLocaleString('pt-BR', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2 
                });
                
                // Devolve o valor formatado para o input, recolocando o '-' se for negativo
                $(this).val(isNegative ? '-' + formatado : formatado);
            });

            // 2. O interruptor do sinal de menos (Mantemos este!)
            $('.mascara-moeda').on('keypress', function(e) {
                if (e.key === '-') {
                    e.preventDefault();
                    let valorAtual = $(this).val();
                    
                    // Se já tem o menos, a gente tira. Se não tem, a gente coloca.
                    if (valorAtual.includes('-')) {
                        $(this).val(valorAtual.replace('-', '')).trigger('input');
                    } else {
                        $(this).val('-' + valorAtual).trigger('input');
                    }
                }
            });
            
            // Se no futuro quiser mascarar CNPJ ou CPF, basta usar:
            $('.mascara-cnpj').mask('00.000.000/0000-00');


            // 2. TRANSFORMAR AS TABELAS EM DATATABLES
            // Pega todas as tabelas com a classe "table" e aplica o plugin
            // $('.table').DataTable({
            //     language: {
            //         // Tradução oficial para o Português do Brasil
            //         url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json',
            //     },
            //     order: [], // Não força ordenação automática ao carregar a página
            //     pageLength: 10, // Mostra 10 linhas por vez
            //     responsive: true,
            //     columnDefs: [
            //         { orderable: false, targets: -1 } // Desabilita a setinha de ordenar na última coluna (Ações/Botões)
            //     ]
            // });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Seleciona todos os botões de aba e todos os painéis de conteúdo
            const tabButtons = document.querySelectorAll('#nav-tab button');
            const tabPanes = document.querySelectorAll('.tab-content .tab-pane');

            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // 1. Remove a cor azul ('active') de todos os botões
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active');
                        btn.setAttribute('aria-selected', 'false');
                    });
                    
                    // 2. Esconde todos os painéis de conteúdo
                    tabPanes.forEach(pane => {
                        pane.classList.remove('show', 'active');
                    });
                    
                    // 3. Pinta de azul o botão que acabou de ser clicado
                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');
                    
                    // 4. Mostra o painel correto na tela
                    const targetId = this.getAttribute('data-bs-target');
                    const targetPane = document.querySelector(targetId);
                    if (targetPane) {
                        targetPane.classList.add('show', 'active');
                    }
                });
            });
        });
    </script>
    
    <!-- Modal para Cadastrar Novo Fornecedor -->
    <script>
    $(document).ready(function() {
        // Inicializa o Select2 dentro do Modal de Despesa
        $('.select2-fornecedor').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#despesaModal .modal-content')
        });

        // Intercepta o envio do formulário do NOVO FORNECEDOR
        $(document).on('submit', '#formNovoFornecedor', function(e) {
            
            // PREVINE que a página recarregue
            e.preventDefault(); 
        
            var form = $(this);
            var botaoSalvar = form.find('button[type="submit"]');
        
            // Muda o texto do botão para dar feedback ao usuário
            botaoSalvar.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...').prop('disabled', true);

            $.ajax({
                url: window.location.href, // Manda para a própria página (onde está o Controller)
                type: 'POST',
                data: form.serialize(), // Pega todos os inputs do form, incluindo o acaoAjax escondido
                dataType: 'json',
                success: function(response) {
                    if(response.sucesso) {

                        // --- CÓDIGO QUE CRIA O TEXTO DO OPTION ---
                        var cnpjLimpo = response.cnpj.replace(/\D/g, ''); 
                        var cnpjFormatado = response.cnpj; 
                        if(cnpjLimpo.length === 14) {
                            cnpjFormatado = cnpjLimpo.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
                        }
                        var textoOpcao = response.razao_social + ' - ' + cnpjFormatado;
                        
                        // 1. Adiciona o novo fornecedor no Select2 dinamicamente
                        var novaOpcao = new Option(textoOpcao, response.id, true, true);
                        $('#fornecedorId').append(novaOpcao).trigger('change');
                        
                        // 2. Fecha o modal de Novo Fornecedor
                        var modalForn = bootstrap.Modal.getInstance(document.getElementById('novoFornecedorModal'));
                        if(modalForn) {
                            modalForn.hide();
                        }
                        
                        // 3. Limpa os campos do formulário para o próximo cadastro
                        form[0].reset();
                            
                        // Opcional: Mostrar um toast de sucesso
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Fornecedor salvo com sucesso!',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                    } else {
                        Swal.fire({
                        icon: 'error',
                        title: 'Ops...',
                        text: response.mensagem,
                        confirmButtonColor: '#198754' // Cor verde do Bootstrap para combinar com seu sistema
                    });
                    }
                },
                error: function() {
                    alert('Ocorreu um erro de comunicação com o servidor.');
                },
                complete: function() {
                    botaoSalvar.html('Salvar Fornecedor').prop('disabled', false);
                }
            });
        });
    });
</script>
</body>
</html>