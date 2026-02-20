<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

date_default_timezone_set("America/Sao_Paulo");
$timezone = new DateTimeZone("America/Sao_Paulo");
$hoje = date('d/m/Y');

use Source\Models\Despesa;
use Source\Models\Instituicao;
use Source\Models\Processo;
use Source\Models\Programa;
use Source\Models\Repasse;
use Source\Models\Saldo;
use Source\Models\User;
use Source\Models\Banco;
use Source\Models\Logs;
use Source\Models\Analise;
use Source\Models\Rentabilidade;
use Source\Models\Conciliacao;

// ====================================================================
// FUNÇÃO HELPER: Limpar formato de moeda (R$ 1.500,00 -> 1500.00)
// ====================================================================
function limparMoedaSQL($valor) {
    if (empty($valor)) return 0.00;
    $limpo = preg_replace('/[^0-9,-]/', '', $valor); // Remove R$ e pontos
    return (float) str_replace(',', '.', $limpo); // Troca vírgula por ponto
}

function redirecionar(string $url, string $tipo, string $mensagem) {
    $_SESSION[$tipo == 'sucesso' ? 'toast_sucesso' : 'toast_erro'] = $mensagem;
    header("Location: $url");
    exit();
}

// 1. Instancia Apenas o User inicialmente
$userModel = new User();

// 2. Verifica Segurança IMEDIATAMENTE
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}

// 3. Agora que temos o usuário logado, podemos buscar seus dados para usar na aplicação
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
// 4. Carregamento dos outros Models (Agora é seguro)
// ====================================================================
$analiseModel = new Analise();
$processoModel = new Processo();
$instituicaoModel = new Instituicao();
$saldoModel = new Saldo();
$programaModel = new Programa();
$repasseModel = new Repasse();
$despesaModel = new Despesa();
$bancoModel = new Banco();
$rentabilidadeModel = new Rentabilidade();
$logModel = new Logs();
$conciliacaoModel = new Conciliacao();

$idProc = (int) $_SESSION['idProc'];
$currentUser = $_SESSION['user_id'];

// Dados do Processo
$processo = $processoModel->findById($idProc);
$statusProcesso = $processoModel->procStatus($idProc);
$dadosAnalise = $analiseModel->findByProcessoId($idProc);
$dadosConciliacao = $conciliacaoModel->getOcorrencias($idProc);

if($processo){
    $instituicao = $instituicaoModel->findById($processo->instituicao_id);
    $idInst = $processo->instituicao_id;
    $instNome = $instituicao->instituicao;
    $cnpj = $instituicaoModel->formatarCnpj($instituicao);        
    $numProcesso = $processoModel->formatarProcesso($processo);
    $tipoProcesso = $processo->assunto . ' - ' . $processo->tipo;       
}

$idStatus = empty($statusProcesso) ? Processo::STATUS_AGUARDANDO_ENTREGA : $statusProcesso->status_id;
$statusPC = empty($statusProcesso) ? "Aguardando Entrega" : $statusProcesso->status_pc;

if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
    session_unset();
    header("Location:index.php");
    exit();
}

// ====================================================================
// Ações de Formulário (Criar/Atualizar Saldo, Criar/Atualizar Banco, etc.)
// Cada ação verifica um parâmetro específico (ex: 'novoSaldo', 'updateSaldo', 'createBanco', etc.) para saber qual ação executar.
// ====================================================================

if (isset($_REQUEST['novoSaldo']) && $_SERVER['REQUEST_METHOD'] == 'POST') 
{   
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    if ($saldoModel->findSaldoByProcCatAcao($idProc, $postData)) 
    {
        //echo '<script>alert("ERRO! Não foi possível adicionar novo saldo. Saldo já existente!")</script>';
        $_SESSION['toast_erro'] = "Saldo já existente!";
    } 
    else 
    {
        $_SESSION['aba_ativa'] = 'resumo';
        if ($saldoModel->setSaldoInicial($idInst, $idProc, $postData)) {           
            $log = $logModel->save([
                'usuario' => $_SESSION['matricula'],
                'acao' => "Inserção de Saldo Inicial - " . $idProc
                ]);                
            redirecionar('pddeFinanc.php', 'sucesso', "Saldo criado com sucesso!");
        } else {                        
            redirecionar('pddeFinanc.php', 'erro', "Erro ao gravar saldo!");
        }
    }
}

// Se receber $_POST['updateSaldo'], executa a lógica para atualizar um saldo existente.
if (isset($_REQUEST['updateSaldo']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['aba_ativa'] = 'resumo';                           
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                            
    if($saldoModel->updateSaldo($currentUser, $idProc, $postData)) {       
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Atualização do Saldo do PDDE de Id " . $postData['idSaldoM']
        ]);
        redirecionar('pddeFinanc.php', 'sucesso', "Saldo atualizado com sucesso!");        
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao atualizar saldo!");
    }    
}

// Se receber $_POST['createBanco'], executa a lógica para criar um novo banco.
if (isset($_REQUEST['createBanco']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['aba_ativa'] = 'saldoBancario';  
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                            
    if($bancoModel->saveBanco($idProc, $idInst, $postData)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Criou nova conta no processo de Id " . $idProc
        ]);        
        redirecionar('pddeFinanc.php', 'sucesso', "Conta criada com sucesso!");        
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao criar conta!");
    }            
}                   

// Se receber $_POST['updateBanco'], executa a lógica para atualizar um banco existente.
if (isset($_REQUEST['updateBanco']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['aba_ativa'] = 'saldoBancario';
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                            
    if($bancoModel->saveBanco($idProc, $idInst, $postData)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Atualização do Saldo Bancário da Conta de id " . $postData['idContaM']
        ]);        
        redirecionar('pddeFinanc.php', 'sucesso', "Conta atualizada com sucesso!");        
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao atualizar saldo bancário!");            
    }                   
}

// Se receber $_POST['includeRent'], executa a lógica para criar uma nova rentabilidade.
if (isset($_REQUEST['includeRent']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['aba_ativa'] = 'rentabilidade';    
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    if($rentabilidadeModel->saveRentabilidade($idProc, $currentUser, $postData)) {        
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Inclusão de Rentabilidade no Processo de Id " . $_SESSION['idProc']
        ]);
        redirecionar('pddeFinanc.php', 'sucesso', "Rentabilidade criada com sucesso!");
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao criar rentabilidade!");        
    }    
}

// Se receber $_POST['updateRent'], executa a lógica para atualizar uma rentabilidade existente.
if (isset($_REQUEST['updateRent']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['aba_ativa'] = 'rentabilidade';  
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    if($rentabilidadeModel->updateRentabilidade($currentUser, $postData)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Atualização da Rentabilidade id " . $postData['idRentM']
        ]);
        redirecionar('pddeFinanc.php', 'sucesso', "Rentabilidade atualizada com sucesso!");
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao atualizar rentabilidade!");
    }
}

// Se receber $_POST['update'], executa a lógica para criar ou atualizar uma despesa.
if (isset($_REQUEST['update']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['aba_ativa'] = 'despesas';    
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    $pagamento = $postData['pagamento'];
    if($liquidarDespesa = $despesaModel->liquidarDespesa($postData, $pagamento, $idProc)) {
        $log = $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Atualizou a despesa no processo de id " . $idProc
        ]);
        redirecionar('pddeFinanc.php', 'sucesso', "Despesa atualizada com sucesso!");
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao atualizar despesa!");        
    }
}
 
// Se receber $_POST['glosar'], executa a lógica para glosar uma despesa existente.
if (isset($_REQUEST['glosa']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['aba_ativa'] = 'despesas';    
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    $idDespesa = $postData['idDespM'];
    if($glosarDespesa = $despesaModel->glosarDespesa($postData, $idDespesa)) {       
        $log = $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Glosou a despesa no processo de id " . $idDespesa
        ]);
        redirecionar('pddeFinanc.php', 'sucesso', "Despesa glosada com sucesso!");
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao glosar despesa!");        
    }
}

// Se receber $_POST['novaOcorrencia'], executa a lógica para criar ou atualizar uma ocorrência.
if (isset($_REQUEST['novaOcorrencia']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['aba_ativa'] = 'ocorrencias';   
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    if($conciliacaoModel->saveOcorrencia($idProc, $currentUser, $postData)) {        
        $log = $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Adicionou nova ocorrência no processo de id " . $idProc
        ]);
        redirecionar('pddeFinanc.php', 'sucesso', "Ocorrência salva com sucesso!");
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao salvar ocorrência!");        
    }            
}

// Se receber $_GET['delOcc'] == true, executa a lógica para deletar uma ocorrência existente.
if (isset($_GET['delOcc']) && $_GET['delOcc'] == true) {
    $_SESSION['aba_ativa'] = 'ocorrencias';
    if($conciliacaoModel->deleteOcc($_GET['idOcc'])) {
        $log = $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Deletou a ocorrência de id " . $_GET['idOcc'] . " no processo de id " . $idProc
        ]);
        redirecionar('pddeFinanc.php', 'sucesso', "Ocorrência deletada com sucesso!");
    } else {
        redirecionar('pddeFinanc.php', 'erro', "Erro ao deletar ocorrência!");
    }              
}
// ====================================================================
// Fim das Ações de Formulário
// ====================================================================

// ====================================================================
// AÇÃO: CONCLUIR ANÁLISE FINANCEIRA
// ====================================================================
if (isset($_REQUEST['concluirAf']) && $_REQUEST['concluirAf'] == 'true') {                        
    
    // Validação 1: O status do processo permite análise financeira?
    if ($idStatus < Processo::STATUS_ANALISE_FINANCEIRA) { 
        redirecionar('pddeFinanc.php', 'erro', "ERRO! O status do processo não está para análise financeira.");        
    }

    // ==================================================================================
    // CÁLCULO MATEMÁTICO DOS SALDOS (A "CONTA" QUE PEDIU)
    // ==================================================================================
    $arrSaldoAF = [];      // Vai guardar os dados para atualizar o banco no final
    $saldoFinalResumo = 0; // Soma total para comparar com o Banco
    $rentResumo = 0;       // Soma total da rentabilidade para validar
    
    // A. Busca linhas de saldo (Custeio, Capital, etc) e repasse vinculadas ao processo
    // Nota: Estou a usar SQL direto para garantir que temos todos os campos necessários.    
    $listaSaldos = $saldoModel->somaByProcId($idProc);
    $repasse = $repasseModel->somaRepasseByProc($idProc);
    // var_dump($listaSaldos, $listaSaldos->saldo_anterior, $repasse);
    // die();
    if (!empty($listaSaldos)) {
        // B. Calcula as RECEITAS (Saldo Anterior + Repasse + Rentabilidade + Recursos Próprios)
        $totalReceitas = ($listaSaldos->saldo_anterior + $repasse + $listaSaldos->rp + $listaSaldos->rent - $listaSaldos->devl);
        $totalDespesas = $despesaModel->getResumoDespesas($idProc)['despesa'];
        $totalGlosas = $despesaModel->getResumoDespesas($idProc)['glosas'];
        $saldoFinalResumo = $totalReceitas - $totalDespesas + $totalGlosas;
    } else {
        redirecionar('pddeFinanc.php', 'erro', "ERRO CRÍTICO: Nenhum registro de saldo inicial encontrado para este processo!");        
    }
    
    // Prepara as variáveis para a Validação 2
    $bancoFinal = $bancoModel->getSaldoFinal($idProc);
    $sdConc = $conciliacaoModel->getSaldoConciliacao($idProc);
        
    // (Atenção: Garanta que a variável $saldoFinalT foi calculada antes disto no seu código do topo)    
    
    // Validação 2: Compara com o saldo final do resumo geral
    if (round($saldoFinalResumo, 2) != round(($bancoFinal + $sdConc), 2)) {
        redirecionar('pddeFinanc.php', 'erro', "ERRO! Verifique o saldo bancário final e/ou Conciliação Bancária. Diferença: R$ " . (round($saldoFinalResumo, 2) - round(($bancoFinal + $sdConc), 2)));
    } 
    
    // Prepara as variáveis para a Validação 3
    $totalRentabilidade = $rentabilidadeModel->getSaldoTotalRentabilidade($idProc); 
    // Garanta que $rentT foi calculado antes
    $rentResumo = $saldoModel->getRentFinalPDDE($idProc);
    
    // Validação 3: Validação da Rentabilidade                                                
    if (round($rentResumo, 2) != round($totalRentabilidade, 2)) {
        redirecionar('pddeFinanc.php', 'erro', "ERRO! Valores de rentabilidade inconsistentes! Diferença: R$ " . (round($rentResumo, 2) - round($totalRentabilidade, 2)));
    }  
    
    // ==========================================================
    // PREPARAÇÃO PARA GRAVAÇÃO (Cálculo Individual por Linha)
    // ==========================================================
    
    // 1. Buscamos a lista COMPLETA de saldos (não a soma)
    // Certifique-se que este método traz 'acao_id' e 'categoria' (Custeio/Capital)
    $listaDetalhada = $saldoModel->findByProcId($idProc); 
    
    $arrSaldoF = []; // Array que vai guardar ID e Valor Final para o Update

    if (!empty($listaDetalhada)) {
        foreach ($listaDetalhada as $item) {
            
            // A. Busca o Repasse ESPECÍFICO para esta linha (Ação + Categoria)
            // Ex: Repasse do Programa PDDE Básico (Ação 1) para Custeio
            $repasseEspecifico = $repasseModel->getSomaRepasse($idProc, $item->acao_id, $item->categoria);

            // B. Busca as Despesas ESPECÍFICAS desta linha de saldo (pelo ID do saldo)            
            $somaDespesas = $despesaModel->somaByCatAcaoProc($idProc, $item->acao_id, $item->categoria);
            $somaGlosas = $despesaModel->somaGlosaByAcaoProc($idProc, $item->acao_id, $item->categoria);            

            // C. A Matemática da Linha:
            // (Saldo Ant + Repasse + RP + Rent - Devl) - (Pago - Glosa)
            // Nota: Usei os nomes que vêm do seu método findByProcId (saldo_anterior, rp, etc)
            $receitasLinha = ($item->saldo_anterior + $repasseEspecifico + $item->rp + $item->rent - $item->devl);
            $saidasLinha = ($somaDespesas - $somaGlosas);
            
            $saldoFinalCalculado = $receitasLinha - $saidasLinha;

            // D. Adiciona ao array de atualização
            $arrSaldoF[] = [
                'id' => $item->id,
                'saldo' => $saldoFinalCalculado
            ];
        }
    }

    // ==========================================================
    // EXECUTA A GRAVAÇÃO NO BANCO DE DADOS
    // ==========================================================
   
    $idSts = Processo::STATUS_AF_CONCLUIDO;    
    $teveErroSalvar = false;

    // 1. Atualiza os saldos individuais
    if (!empty($arrSaldoF)) {
        foreach ($arrSaldoF as $linha) {
            // Usamos o método do Model para ficar limpo (ou SQL direto se preferir)
            if (!$saldoModel->atualizarSaldoFinal($linha['id'], round($linha['saldo'], 2), $currentUser)) {
                $teveErroSalvar = true;
            }
        }
    }

    if ($teveErroSalvar) {
        redirecionar('pddeFinanc.php', 'erro', "ERRO AO GRAVAR SALDO!");
    }

    // 2. Atualiza o status da Análise PDDE para Concluído    
    if ($analiseModel->updateAnalise($idSts, $currentUser, $idProc)) {
        $logModel->save([
            'usuario' => $_SESSION['matricula'] ?? 'Sistema',
            'acao' => "Concluiu a Análise Financeira do processo ID " . $idProc
        ]);
        redirecionar('pddeFinanc.php', 'sucesso', "Análise Financeira concluída com sucesso!");
    } else {        
        redirecionar('pddeFinanc.php', 'erro', "ERRO AO GRAVAR DADOS DA ANÁLISE!");
    }  
}

// ====================================================================
// Variável para controlar qual modal deve ser aberto (ex: 'resumoModal', 'pagamentoModal', etc.)
// Ela será definida com base nos parâmetros GET recebidos (ex: ?editSaldo=1, ?editDesp=2, etc.)
// No HTML, verificaremos esta variável para decidir qual modal abrir automaticamente.
// Exemplo: Se receber ?editSaldo=1, a variável $modalToOpen será setada para 'resumoModal', e o modal de edição de saldo será aberto automaticamente.
// Inicialmente, nenhuma modal está marcada para abrir.
// ====================================================================

$modalToOpen = '';

if (isset($_GET['editSaldo'])) {
    $modalToOpen = 'resumoModal';
    $_SESSION['aba_ativa'] = 'resumo';
    $idSaldo = (int)$_GET['idSaldo'];
    $saldo = $saldoModel->findById($idSaldo);                        
    
    if ($saldo) {
        $idSaldoM = $saldo->id;
        $acaoIdM = $saldo->acao_id;
        $categoriaM = $saldo->categoria;
        $rpCYM = $saldo->rpCY;
        $rentCYM = $saldo->rentCY;
        $devolCYM = $saldo->devlCY;

        $prog = $programaModel->findById($acaoIdM);                            
        if ($prog) {
            $acaoM = $prog->acao;
        }

        if ($categoriaM == "C") {
            $categoriaM = "Custeio";
        } elseif ($categoriaM == "K") {
            $categoriaM = "Capital";
        }
    }
}    

if (isset($_GET['editDesp'])) {
    $modalToOpen = 'pagamentoModal';
    $_SESSION['aba_ativa'] = 'despesas';
    $idDespM = (int)$_GET['idDesp'];
    $despData = $despesaModel->findById($idDespM);
    if ($despData) {
        $numPgtoM = $despData->pagamento;
        $dataPgM = $despData->data_pg;
        $valorPgReal = 'R$ ' . number_format($despData->valor_pg, 2, ",", ".");
    }    
}    

if (isset($_GET['glosarDesp'])) {
    $modalToOpen = 'glosaModal';           
    $_SESSION['aba_ativa'] = 'despesas';
    $idDespM = (int)$_GET['idDesp'];
    $despData = $despesaModel->findById($idDespM);
    if ($despData) {                            
        $valorGlReal = 'R$ ' . number_format($despData->valor_gl, 2, ",", ".");
        $motivoGlM = $despData->motivo_gl;
    }    
}

if (isset($_GET['editRent'])) {
    $modalToOpen = 'rentabilidadeModal';
    $_SESSION['aba_ativa'] = 'rentabilidade';
    $idRentM = (int)$_GET['idRent'];

    $actionRent = "?updateRent=true";
    $tituloRent = "Atualizar Rentabilidade";
    $botaoRent = '<input type="submit" class="btn btn-warning" value="Atualizar"/>';

    $rentData = $rentabilidadeModel->findById($idRentM);

    if ($rentData) {        
        $idContaM = $rentData->conta_id;
        $variacaoM = $rentData->variacao;
        $rJanM = $rentData->jan;
        $rFevM = $rentData->fev;
        $rMarM = $rentData->mar;
        $rAbrM = $rentData->abr;
        $rMaiM = $rentData->mai;
        $rJunM = $rentData->jun;
        $rJulM = $rentData->jul;
        $rAgoM = $rentData->ago;
        $rSetM = $rentData->setb;
        $rOutM = $rentData->outb;
        $rNovM = $rentData->nov;
        $rDezM = $rentData->dez;
    }    
} else {
    $actionRent = "?includeRent=true";
    $tituloRent = "Nova Rentabilidade";
    $botaoRent = '<input type="submit" class="btn btn-success" value="Incluir"/>';
}

if (isset($_GET['editBanco'])) {
    $modalToOpen = 'bancoModal';
    $_SESSION['aba_ativa'] = 'saldoBancario';
    $idContaM = (int)$_GET['idConta'];
    $contaData = $bancoModel->findById($idContaM);
    if ($contaData) {        
        $agenciaM = $contaData->agencia;
        $contaM = $contaData->conta;
        $fCorrenteM = $contaData->cc_2025;
        $fPoupanca01M = $contaData->pp_01_2025;
        $fPoupanca51M = $contaData->pp_51_2025;
        $fSPubAutM = $contaData->spubl_2025;
        $fBbRfCpM = $contaData->bb_rf_cp_2025;
    }
    
}

// Lê qual aba deve estar ativa. Se não houver nenhuma ordem, abre o 'resumo'.
$abaAtiva = $_SESSION['aba_ativa'] ?? 'resumo';

// Limpa a sessão logo a seguir
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
    <title>Análise Financeira - PDDE</title>
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
            <div class="text-center">
                <h1>
                    Análise Financeira 2025 - PDDE
                </h1>
            </div>
            <!-- Início do Conteúdo  -->

            <div class="container-fluid">
                <div class="row">
                    <div class="col-6">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-nomeEnt">Entidade</span>
                            <input type="text" name="nomeEnt" value="<?= $instNome; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-nomeEnt" readonly />
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-cnpj">CNPJ</span>
                            <input type="text" name="cnpj" value="<?= $cnpj; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-cnpj" readonly />
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-processo">Processo</span>
                            <input type="text" name="campo3" value="<?= $numProcesso ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-processo" readonly />
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-assuntoProc">Assunto</span>
                            <input type="text" name="assuntoProc" value="<?= $tipoProcesso; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-assuntoProc" readonly />
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-statusProc">Status</span>
                            <input type="text" name="statusProc" value="<?= $statusPC; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-statusProc" readonly />
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <button class="nav-link <?= $abaAtiva == 'resumo' ? 'active' : '' ?>" id="nav-resumo-tab" data-bs-toggle="tab" data-bs-target="#nav-resumo" type="button" role="tab" aria-controls="nav-resumo" aria-selected="<?= $abaAtiva == 'resumo' ? 'true' : 'false' ?>">Resumo Geral</button>        
                        <button class="nav-link <?= $abaAtiva == 'ingresso' ? 'active' : '' ?>" id="nav-ingresso-tab" data-bs-toggle="tab" data-bs-target="#nav-ingresso" type="button" role="tab" aria-controls="nav-ingresso" aria-selected="<?= $abaAtiva == 'ingresso' ? 'true' : 'false' ?>">Ingresso no Período</button>        
                        <button class="nav-link <?= $abaAtiva == 'saldoBancario' ? 'active' : '' ?>" id="nav-saldoBancario-tab" data-bs-toggle="tab" data-bs-target="#nav-saldoBancario" type="button" role="tab" aria-controls="nav-saldoBancario" aria-selected="<?= $abaAtiva == 'saldoBancario' ? 'true' : 'false' ?>">Saldo Bancário</button>        
                        <button class="nav-link <?= $abaAtiva == 'rentabilidade' ? 'active' : '' ?>" id="nav-rentabilidade-tab" data-bs-toggle="tab" data-bs-target="#nav-rentabilidade" type="button" role="tab" aria-controls="nav-rentabilidade" aria-selected="<?= $abaAtiva == 'rentabilidade' ? 'true' : 'false' ?>">Rentabilidade</button>        
                        <button class="nav-link <?= $abaAtiva == 'despesas' ? 'active' : '' ?>" id="nav-despesas-tab" data-bs-toggle="tab" data-bs-target="#nav-despesas" type="button" role="tab" aria-controls="nav-despesas" aria-selected="<?= $abaAtiva == 'despesas' ? 'true' : 'false' ?>">Despesas</button>        
                        <button class="nav-link <?= $abaAtiva == 'ocorrencias' ? 'active' : '' ?>" id="nav-ocorrencias-tab" data-bs-toggle="tab" data-bs-target="#nav-ocorrencias" type="button" role="tab" aria-controls="nav-ocorrencias" aria-selected="<?= $abaAtiva == 'ocorrencias' ? 'true' : 'false' ?>">Conciliação Ocorrências</button>                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">

                    <!-- ABA RESUMO GERAL -->
                    <div class="tab-pane fade <?= $abaAtiva == 'resumo' ? 'show active' : '' ?>" id="nav-resumo" role="tabpanel" aria-labelledby="nav-resumo-tab" tabindex="0">
                        <br />
                        <form action="?concluirAf=true" method="post">                                
                            <div class="row">
                                <h6 class="text-center">RESUMO GERAL</h6>

                                <table class="table table-hover">
                                    <thead>
                                        <tr class="text-center align-middle table-secondary">
                                            <th class="col w-auto fw-semibold">SEGMENTO</th>
                                            <th class="col w-auto fw-semibold">SALDO INICIAL</th>
                                            <th class="col w-auto fw-semibold">INGRESSO</th>
                                            <th class="col w-auto fw-semibold">RECURSOS PRÓPRIOS</th>
                                            <th class="col w-auto fw-semibold">RENTABILIDADE</th>
                                            <th class="col w-auto fw-semibold">DEVOLUÇÃO AO FNDE</th>
                                            <th class="col w-auto fw-semibold">RECEITA TOTAL</th>
                                            <th class="col w-auto fw-semibold">DESPESAS</th>
                                            <th class="col w-auto fw-semibold">GLOSAS</th>
                                            <th class="col w-auto fw-semibold">SALDO FINAL</th>
                                            <th class="col w-auto fw-semibold">EDITAR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="13" class="text-center"><b>CUSTEIO</b></td>
                                        </tr>
                                        <?php
                                        $arrSaldoF = array();

                                        $cat = "C";
                                        $saldoC = 0;
                                        $repasseC = 0;
                                        $rpC = 0;
                                        $rentC = 0;
                                        $devolucaoC = 0;
                                        $receitaC = 0;
                                        $despesaC = 0;
                                        $saldoFinalC = 0;
                                        $glosasC = 0;
                                        $procC = $saldoModel->findSaldoByProcCat($idProc, $cat);
                                        if ($procC) 
                                        {
                                            foreach ($procC as $proc):
                                                $repasse = 0;
                                                $despesa = 0;
                                                $glosas = 0;
                                                $idSaldoC = $proc->id;
                                                $saldo = $proc->saldoLY;
                                                $rp = $proc->rpCY;
                                                $rent = $proc->rentCY;
                                                $devolucao = $proc->devlCY;
                                                $acaoId = $proc->acao_id;
                                                
                                                $acao = $programaModel->findById($acaoId)->acao;

                                                echo '<tr class="text-end align-middle">';
                                                echo '<td scope="row" class="text-start">' . $acao . '</td>';
                                                echo '<td>' . number_format($saldo, 2, ",", ".") . '</td>';
                                                $value = $repasseModel->somaRepasseCByProcAcao($idProc, $acaoId);                                                    
                                                if (!empty($value)) {
                                                    $repasse = $value;
                                                }                                            
                                                if ($repasse == null) {
                                                    $repasse = 0;
                                                }
                                                echo '<td>' . number_format($repasse, 2, ",", ".") . '</td>';
                                                echo '<td>' . number_format($rp, 2, ",", ".") . '</td>';
                                                echo '<td>' . number_format($rent, 2, ",", ".") . '</td>';
                                                echo '<td>' . number_format($devolucao, 2, ",", ".") . '</td>';
                                                $receita = $saldo + $repasse + $rp + $rent - $devolucao;
                                                echo '<td>' . number_format($receita, 2, ",", ".") . '</td>';
                                                $value = $despesaModel->somaByCatAcaoProc($idProc, $acaoId, $cat);
                                                if (!empty($value)) {                                                        
                                                    $despesa = $value;
                                                }                                                    
                                                if ($despesa == null) {
                                                    $despesa = 0;
                                                }
                                                echo '<td>' . number_format($despesa, 2, ",", ".") . '</td>';
                                                $valueG = $despesaModel->somaGlosaByAcaoProc($idProc, $acaoId, $cat);
                                                if (!empty($valueG)) {                                                        
                                                    $glosas = $valueG;
                                                }                                                    
                                                if ($glosas == null) {
                                                    $glosas = 0;
                                                }                                                    
                                                echo '<td>' . number_format($glosas, 2, ",", ".") . '</td>';
                                                $saldoFinal = $receita - $despesa + $glosas;
                                                echo '<td>' . number_format($saldoFinal, 2, ",", ".") . '</td>';
                                                echo '<td class="text-center"><a href="?editSaldo=true&idSaldo=' . $idSaldoC . '"><img src="img/icons/currency-dollar.svg" alt="Editar Saldo" title="Editar Saldo" /></a></td>';
                                                $currentSaldo = array('id' => $idSaldoC, 'saldo' => $saldoFinal);
                                                array_push($arrSaldoF, $currentSaldo);
                                                echo '</tr>';
                                                $saldoC = $saldoC + $saldo;
                                                $repasseC = $repasseC + $repasse;
                                                $rpC = $rpC + $rp;
                                                $rentC = $rentC + $rent;
                                                $devolucaoC = $devolucaoC + $devolucao;
                                                $receitaC = $receitaC + $receita;
                                                $despesaC = $despesaC + $despesa;
                                                $glosasC = $glosasC + $glosas;
                                                $saldoFinalC = $saldoFinalC + $saldoFinal;
                                            endforeach;
                                        }
                                        ?>

                                        <tr class="table-group-divider text-end align-middle table-light">
                                            <th scope="row" class="text-start">Total Custeio</th>
                                            <th><?= number_format($saldoC, 2, ",", "."); ?></th>
                                            <th><?= number_format($repasseC, 2, ",", "."); ?></th>
                                            <th><?= number_format($rpC, 2, ",", "."); ?></th>
                                            <th><?= number_format($rentC, 2, ",", "."); ?></th>
                                            <th><?= number_format($devolucaoC, 2, ",", "."); ?></th>
                                            <th><?= number_format($receitaC, 2, ",", "."); ?></th>
                                            <th><?= number_format($despesaC, 2, ",", "."); ?></th>
                                            <th><?= number_format($glosasC, 2, ",", "."); ?></th>
                                            <th><?= number_format($saldoFinalC, 2, ",", "."); ?></th>
                                            <th></th>
                                        </tr>
                                        <tr>
                                            <td colspan="13" class="text-center table-group-divider"><b>CAPITAL</b></td>
                                        </tr>

                                        <?php
                                        $cat = 'K';
                                        $saldoK = 0;
                                        $repasseK = 0;
                                        $rpK = 0;
                                        $rentK = 0;
                                        $devolucaoK = 0;
                                        $receitaK = 0;
                                        $despesaK = 0;
                                        $saldoFinalK = 0;
                                        $glosasK = 0;
                                        $procK = $saldoModel->findSaldoByProcCat($idProc, $cat);
                                        if ($procK) 
                                        {
                                            foreach ($procK as $proc):
                                                $repasse = 0;
                                                $despesa = 0;
                                                $glosas = 0;
                                                $idSaldoK = $proc->id;
                                                $saldo = $proc->saldoLY;
                                                $rp = $proc->rpCY;
                                                $rent = $proc->rentCY;
                                                $devolucao = $proc->devlCY;
                                                $acaoId = $proc->acao_id;
                                                
                                                $acao = $programaModel->findById($acaoId)->acao;                                          

                                                echo '<tr class="text-end align-middle">';
                                                echo '<td scope="row" class="text-start">' . $acao . '</td>';
                                                echo '<td>' . number_format($saldo, 2, ",", ".") . '</td>';
                                                $value = $repasseModel->somaRepasseKByProcAcao($idProc, $acaoId);                                                    
                                                if (!empty($value)) {
                                                    $repasse = $value;
                                                }                                            
                                                if ($repasse == null) {
                                                    $repasse = 0;
                                                }                                                    
                                                echo '<td>' . number_format($repasse, 2, ",", ".") . '</td>';
                                                echo '<td>' . number_format($rp, 2, ",", ".") . '</td>';
                                                echo '<td>' . number_format($rent, 2, ",", ".") . '</td>';
                                                echo '<td>' . number_format($devolucao, 2, ",", ".") . '</td>';
                                                $receita = $saldo + $repasse + $rp + $rent - $devolucao;
                                                echo '<td>' . number_format($receita, 2, ",", ".") . '</td>';
                                                    $value = $despesaModel->somaByCatAcaoProc($idProc, $acaoId, $cat);
                                                if (!empty($value)) {                                                        
                                                    $despesa = $value;
                                                }                                                    
                                                if ($despesa == null) {
                                                    $despesa = 0;
                                                }
                                                echo '<td>' . number_format($despesa, 2, ",", ".") . '</td>';
                                                $valueG = $despesaModel->somaGlosaByAcaoProc($idProc, $acaoId, $cat);
                                                if (!empty($valueG)) {                                                        
                                                    $glosas = $valueG;
                                                }                                                    
                                                if ($glosas == null) {
                                                    $glosas = 0;
                                                } 
                                                echo '<td>' . number_format($glosas, 2, ",", ".") . '</td>';
                                                $saldoFinal = $receita - $despesa + $glosas;
                                                echo '<td>' . number_format($saldoFinal, 2, ",", ".") . '</td>';
                                                echo '<td class="text-center"><a href="?editSaldo=true&idSaldo=' . $idSaldoK . '"><img src="img/icons/currency-dollar.svg" alt="Editar Saldo" title="Editar Saldo" /></a></td>';
                                                $currentSaldo = array('id' => $idSaldoK, 'saldo' => $saldoFinal);
                                                array_push($arrSaldoF, $currentSaldo);
                                                $saldoK = $saldoK + $saldo;
                                                $repasseK = $repasseK + $repasse;
                                                $rpK = $rpK + $rp;
                                                $rentK = $rentK + $rent;
                                                $devolucaoK = $devolucaoK + $devolucao;
                                                $receitaK = $receitaK + $receita;
                                                $despesaK = $despesaK + $despesa;
                                                $glosasK = $glosasK + $glosas;
                                                $saldoFinalK = $saldoFinalK + $saldoFinal;
                                            endforeach;
                                        }
                                        ?>
                                        <tr class="table-group-divider text-end align-middle table-light">
                                            <th scope="row" class="text-start">Total Capital</th>
                                            <th><?= number_format($saldoK, 2, ",", "."); ?></th>
                                            <th><?= number_format($repasseK, 2, ",", "."); ?></th>
                                            <th><?= number_format($rpK, 2, ",", "."); ?></th>
                                            <th><?= number_format($rentK, 2, ",", "."); ?></th>
                                            <th><?= number_format($devolucaoK, 2, ",", "."); ?></th>
                                            <th><?= number_format($receitaK, 2, ",", "."); ?></th>
                                            <th><?= number_format($despesaK, 2, ",", "."); ?></th>
                                            <th><?= number_format($glosasK, 2, ",", "."); ?></th>
                                            <th><?= number_format($saldoFinalK, 2, ",", "."); ?></th>
                                            <th></th>
                                        </tr>
                                    </tbody>
                                    <?php
                                    $saldoT = $saldoC + $saldoK;
                                    $repasseT = $repasseC + $repasseK;
                                    $rpT = $rpC + $rpK;
                                    $rentT = $rentC + $rentK;
                                    $devolucaoT = $devolucaoC + $devolucaoK;
                                    $receitaT = $receitaC + $receitaK;
                                    $despesaT = $despesaC + $despesaK;
                                    $glosasT = $glosasC + $glosasK;
                                    $saldoFinalT = $saldoFinalC + $saldoFinalK;

                                    ?>
                                    <tfoot class="table-group-divider">
                                        <tr class="text-end align-middle table-secondary">
                                            <th scope="row" class="text-start">Total Geral</th>
                                            <th><?= number_format($saldoT, 2, ",", "."); ?></th>
                                            <th><?= number_format($repasseT, 2, ",", "."); ?></th>
                                            <th><?= number_format($rpT, 2, ",", "."); ?></th>
                                            <th><?= number_format($rentT, 2, ",", "."); ?></th>
                                            <th><?= number_format($devolucaoT, 2, ",", "."); ?></th>
                                            <th><?= number_format($receitaT, 2, ",", "."); ?></th>
                                            <th><?= number_format($despesaT, 2, ",", "."); ?></th>
                                            <th><?= number_format($glosasT, 2, ",", "."); ?></th>
                                            <th><?= number_format($saldoFinalT, 2, ",", "."); ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="row">
                                <div class="col text-start">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novoSaldoModal">Novo Saldo</button>
                                </div>
                                <div class="col text-end">
                                    <input type="submit" class="btn btn-primary" value="Concluir Análise Financeira" />
                                    <?php                                        
                                    $dataAnaliseFin = $processoModel->abrirTramitacao($idProc)?->data_analise_fin;                                        

                                    if (isset($dataAnaliseFin) && $dataAnaliseFin != null) {
                                        echo '<a href="aFinanceira.php?idProc=' . $idProc . '" target="_blank" class="col-2 mx-2"><button type="button" class="btn btn-warning">Gerar Demonstrativo</button></a>';
                                        //echo '<a href="aFinanceira.php?idProc=' . $idProc . '&pdf=1" target="_blank" class="btn btn-danger">🖨️ Gerar PDF</a>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- INGRESSO -->
                    <div class="tab-pane fade <?= $abaAtiva == 'ingresso' ? 'show active' : '' ?>" id="nav-ingresso" role="tabpanel" aria-labelledby="nav-ingresso-tab" tabindex="0">
                        <div class="container-fluid">
                            <br />
                            <div class="row">
                                <h6 class="text-center">INGRESSO</h6>
                                <div class="col">
                                    <table class="table table-hover m-auto">
                                        <thead>
                                            <tr class="text-center align-middle">
                                                <th class="fw-semibold">Destinação</th>
                                                <th class="fw-semibold">Valor Custeio</th>
                                                <th class="fw-semibold">Valor Capital</th>
                                                <th class="fw-semibold">Total</th>
                                                <th class="fw-semibold">Data Pagamento</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $repCTotal = 0;
                                            $repKTotal = 0;

                                            $reps = $repasseModel->findById($idProc);                                            
                                            
                                            foreach ($reps as $rep):
                                                $acaoId = $rep->acao_id;
                                                $destinacao = $rep->destinacao;
                                                $repC = $rep->custeio;
                                                $repK = $rep->capital;
                                                $repData = $rep->data;

                                                $repTotal = $repC + $repK;

                                                $dataRepasse = new DateTime($repData, $timezone);
                                                $dataRepasse = $dataRepasse->format('d/m/Y');
                                                $repProg = $programaModel->findById($acaoId)->programa;
                                                echo '<tr>';
                                                echo '<td>' . $repProg . ' - ' . $destinacao . '</td>';
                                                echo '<td class="text-center">R$ ' . number_format($repC, 2, ",") . '</td>';
                                                echo '<td class="text-center">R$ ' . number_format($repK, 2, ",") . '</td>';
                                                echo '<td class="text-center">R$ ' . number_format($repTotal, 2, ",") . '</td>';
                                                echo '<td class="text-center">' . $dataRepasse . '</td>';
                                                echo '</tr>';

                                                $repCTotal = $repCTotal + $repC;
                                                $repKTotal = $repKTotal + $repK;
                                            endforeach;
                                            $repCKTotal = $repCTotal + $repKTotal;
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Total</th>
                                                <th class="text-center"><?= 'R$ ' . number_format($repCTotal, 2, ",") ?></th>
                                                <th class="text-center"><?= 'R$ ' . number_format($repKTotal, 2, ",") ?></th>
                                                <th class="text-center"><?= 'R$ ' . number_format($repCKTotal, 2, ",") ?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SALDO BANCÁRIO -->
                    <div class="tab-pane fade <?= $abaAtiva == 'saldoBancario' ? 'show active' : '' ?>" id="nav-saldoBancario" role="tabpanel" aria-labelledby="nav-saldoBancario-tab" tabindex="0">
                        <div class="container-fluid">
                            <br />
                            <div class="row">
                                <h6 class="text-center">SALDO BANCÁRIO</h6>
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-saldoInicial">Saldo Total Inicial</span>
                                        <?php                                                  
                                        $bancoInicial = $bancoModel->somaBancoLY($idProc);
                                        ?>
                                        <input type="text" name="saldoInicial" value="R$ <?= number_format($bancoInicial ?? 0, 2, ",", "."); ?>" class="col-9 form-control" aria-describedby="inputGroup-saldoInicial" readonly />
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-saldoFinal">Saldo Total Final</span>
                                        <?php
                                        $bancoFinal = $bancoModel->somaBancoCY($idProc);

                                        $sdConc = $conciliacaoModel->getSaldoConciliacao($idProc);
                                        
                                        if (round($saldoFinalT, 2) == round(($bancoFinal + $sdConc), 2)) {
                                            $backErro = "#D1E7DD";
                                        } else {
                                            $backErro = "#F8D7DA";
                                        }
                                        ?>
                                        <input type="text" name="saldoFinal" value="R$ <?= number_format($bancoFinal ?? 0, 2, ",", ".") ?>" class="col-9 form-control" style="background-color: <?= $backErro ?>" aria-describedby="inputGroup-saldoFinal" readonly />

                                    </div>
                                </div>
                            </div>
                            <br>

                            <div class="row">
                                <?php
                                $contas = $bancoModel->findByProcId($idProc);
                                if($contas) 
                                {                                    
                                    foreach($contas as $cont):                                                                              
                                        $idConta = $cont->id;
                                        $banco = $cont->banco;
                                        $agencia = $cont->agencia;
                                        $conta = $cont->conta;
                                        $ccSI = $cont->cc_LY;
                                        $ccSF = $cont->cc_CY;
                                        $pp01SI = $cont->pp_01_LY;
                                        $pp01SF = $cont->pp_01_CY;
                                        $pp51SI = $cont->pp_51_LY;
                                        $pp51SF = $cont->pp_51_CY;
                                        $spublSI = $cont->spubl_LY;
                                        $spublSF = $cont->spubl_CY;
                                        $bbrfSI = $cont->bb_rf_cp_LY;
                                        $bbrfSF = $cont->bb_rf_cp_CY;

                                        if(empty($ccSI)) { $ccSI = 0; }
                                        if(empty($ccSF)) { $ccSF = 0; }
                                        if(empty($pp01SI)) { $pp01SI = 0; }
                                        if(empty($pp01SF)) { $pp01SF = 0; }
                                        if(empty($pp51SI)) { $pp51SI = 0; }
                                        if(empty($pp51SF)) { $pp51SF = 0; }
                                        if(empty($spublSI)) { $spublSI = 0; }
                                        if(empty($spublSF)) { $spublSF = 0; }
                                        if(empty($bbrfSI)) { $bbrfSI = 0; }
                                        if(empty($bbrfSF)) { $bbrfSF = 0; }

                                        $totalSI = $ccSI + $pp01SI + $pp51SI + $spublSI + $bbrfSI;                                        
                                        $totalSF = $ccSF + $pp01SF + $pp51SF + $spublSF + $bbrfSF;
                                        
                                        echo '<div class="col">';
                                        echo '<div style="max-width: 576px" class="table-responsive-sm">';
                                        echo '<table class="table table-sm table-hover m-auto">';
                                        echo '<tbody>';
                                        echo '<tr>';
                                        echo '<td>Banco</td>';
                                        echo '<td colspan="2">' . $banco . '</td>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td>Agência</td>';
                                        echo '<td colspan="2">' . $agencia . '</td>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td>Conta</td>';
                                        echo '<td colspan="2">' . $conta . '</td>';
                                        echo '</tr>';
                                        echo '<tr class="align-middle">';
                                        echo '<td class="text-center"><a href="?editBanco=true&idConta=' . $idConta . '"><img src="img/icons/currency-dollar.svg" alt="Editar Saldo" title="Editar Saldo Bancário" /></a></td>';
                                        echo '<th>Saldo Inicial</th>';
                                        echo '<th>Saldo Final</th>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td>Conta Corrente</td>';
                                        echo '<td>R$ ' . number_format($ccSI, 2, ",", ".") . '</td>';
                                        echo '<td>R$ ' . number_format($ccSF, 2, ",", ".") . '</td>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td>Poupança 01</td>';
                                        echo '<td>R$ ' . number_format($pp01SI, 2, ",", ".") . '</td>';
                                        echo '<td>R$ ' . number_format($pp01SF, 2, ",", ".") . '</td>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td>Poupança 51</td>';
                                        echo '<td>R$ ' . number_format($pp51SI, 2, ",", ".") . '</td>';
                                        echo '<td>R$ ' . number_format($pp51SF, 2, ",", ".") . '</td>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td>S. Público Aut.</td>';
                                        echo '<td>R$ ' . number_format($spublSI, 2, ",", ".") . '</td>';
                                        echo '<td>R$ ' . number_format($spublSF, 2, ",", ".") . '</td>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td>BB RF CP Aut.</td>';
                                        echo '<td>R$ ' . number_format($bbrfSI, 2, ",", ".") . '</td>';
                                        echo '<td>R$ ' . number_format($bbrfSF, 2, ",", ".") . '</td>';
                                        echo '</tr>';
                                        echo '</tbody>';
                                        echo '<tfoot>';
                                        echo '<tr>';
                                        echo '<th>Total</th>';
                                        echo '<th>R$ ' . number_format($totalSI, 2, ",", ".") . '</th>';
                                        echo '<th>R$ ' . number_format($totalSF, 2, ",", ".") . '</th>';
                                        echo '</tr>';
                                        echo '</tfoot>';
                                        echo '</table>';
                                        echo '</div>';
                                        echo '</div>';
                                    endforeach;
                                }
                                ?>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col text-start">
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novaContaModal">Nova Conta</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ABA RENTABILIDADE -->
                    <div class="tab-pane fade <?= $abaAtiva == 'rentabilidade' ? 'show active' : '' ?>" id="nav-rentabilidade" role="tabpanel" aria-labelledby="nav-rentabilidade-tab" tabindex="0">
                        <div class="container-fluid">
                            <br />
                            <div class="row">
                                <h6 class="text-center">RENTABILIDADE</h6>
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-desp">Rentabilidade Total</span>
                                        <input type="text" name="rentTotal" value="R$ <?= number_format($rentT, 2, ",", "."); ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-desp" readonly />
                                    </div>
                                    <br />
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rentabilidadeModal">Adicionar Rentabilidade</button>
                                </div>
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-conc">Rentabilidade Lançada</span>
                                        <?php                                        
                                        $tRent = $rentabilidadeModel->somaRendimentosCY($idProc) ?? 0.0;
                                        if ($tRent == 0 || $tRent != $rentT) {
                                            $backErro = "#F8D7DA";
                                        } else {
                                            $backErro = "#D1E7DD";
                                        }
                                        ?>
                                        <input type="text" name="rentLanc" value="R$ <?= number_format($tRent, 2, ",", "."); ?>" class="w-50 col-9 form-control" style="background-color: <?= $backErro ?>" aria-describedby="inputGroup-conc" readonly />
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">                            
                                <table class="table table-sm table-hover m-auto">
                                    <thead>
                                        <tr class="text-center align-middle">
                                            <th class="fw-semibold">AG / Conta</th>
                                            <th class="fw-semibold">Poupança / Apl. Financeira</th>
                                            <th class="fw-semibold">Janeiro</th>
                                            <th class="fw-semibold">Fevereiro</th>
                                            <th class="fw-semibold">Março</th>
                                            <th class="fw-semibold">Abril</th>
                                            <th class="fw-semibold">Maio</th>
                                            <th class="fw-semibold">Junho</th>
                                            <th class="fw-semibold">Julho</th>
                                            <th class="fw-semibold">Agosto</th>
                                            <th class="fw-semibold">Setembro</th>
                                            <th class="fw-semibold">Outubro</th>
                                            <th class="fw-semibold">Novembro</th>
                                            <th class="fw-semibold">Dezembro</th>
                                            <th class="fw-semibold">Total</th>
                                            <th class="fw-semibold">Editar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalJan = 0;
                                        $totalFev = 0;
                                        $totalMar = 0;
                                        $totalAbr = 0;
                                        $totalMai = 0;
                                        $totalJun = 0;
                                        $totalJul = 0;
                                        $totalAgo = 0;
                                        $totalSet = 0;
                                        $totalOut = 0;
                                        $totalNov = 0;
                                        $totalDez = 0;
                                        $totalRentabilidade = 0;
                                        $rTotal = 0;                                        
                                        
                                        if ($rentabilidades = $rentabilidadeModel->findByProcId($idProc)) {
                                            foreach ($rentabilidades as $rent) {
                                                $idRent = $rent->id;
                                                $idConta = $rent->conta_id;
                                                $variacao = $rent->variacao;
                                                $rJan = $rent->jan;
                                                $rFev = $rent->fev;
                                                $rMar = $rent->mar;
                                                $rAbr = $rent->abr;
                                                $rMai = $rent->mai;
                                                $rJun = $rent->jun;
                                                $rJul = $rent->jul;
                                                $rAgo = $rent->ago;
                                                $rSet = $rent->setb;
                                                $rOut = $rent->outb;
                                                $rNov = $rent->nov;
                                                $rDez = $rent->dez;

                                                $rTotal = $rJan + $rFev + $rMar + $rAbr + $rMai + $rJun + $rJul + $rAgo + $rSet + $rOut + $rNov + $rDez;
                                                
                                                if ($banco = $bancoModel->findById($idConta)) {
                                                    $agencia = $banco->agencia;
                                                    $conta = $banco->conta;
                                                }
                                                
                                                echo '<tr class="fw-lighter align-middle">';
                                                echo '<td class="">' . $agencia . ' / ' . $conta . '</td>';
                                                echo '<td scope="row" class="">' . $variacao . '</td>';
                                                echo '<td class="">R$ ' . number_format($rJan, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rFev, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rMar, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rAbr, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rMai, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rJun, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rJul, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rAgo, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rSet, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rOut, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rNov, 2, ",", ".") . '</td>';
                                                echo '<td class="">R$ ' . number_format($rDez, 2, ",", ".") . '</td>';
                                                echo '<td class=""><b>R$ ' . number_format($rTotal, 2, ",", ".") . '</b></td>';
                                                echo '<td class="text-center">';
                                                echo '<a href="?editRent=true&idRent=' . $idRent . '"><img src="img/icons/currency-dollar.svg" alt="Editar" title="Editar" /></a><br />';
                                                echo '</td>';
                                                echo '</tr>';

                                                $totalJan = $totalJan + $rJan;
                                                $totalFev = $totalFev + $rFev;
                                                $totalMar = $totalMar + $rMar;
                                                $totalAbr = $totalAbr + $rAbr;
                                                $totalMai = $totalMai + $rMai;
                                                $totalJun = $totalJun + $rJun;
                                                $totalJul = $totalJul + $rJul;
                                                $totalAgo = $totalAgo + $rAgo;
                                                $totalSet = $totalSet + $rSet;
                                                $totalOut = $totalOut + $rOut;
                                                $totalNov = $totalNov + $rNov;
                                                $totalDez = $totalDez + $rDez;
                                                $totalRentabilidade = $totalJan + $totalFev + $totalMar + $totalAbr + $totalMai + $totalJun + $totalJul + $totalAgo + $totalSet + $totalOut + $totalNov + $totalDez;
                                            }
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot class="table-group-divider">
                                        <?php
                                        if ($totalRentabilidade > 0.1) {
                                        ?>
                                            <tr>
                                                <th scope="row" colspan="2">Total</th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalJan, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalFev, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalMar, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalAbr, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalMai, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalJun, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalJul, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalAgo, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalSet, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalOut, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalNov, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalDez, 2, ",", "."); ?></th>
                                                <th class="text-center"><?php echo 'R$ ' . number_format($totalRentabilidade, 2, ",", "."); ?></th>
                                                <th></th>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- DESPESAS -->
                    <div class="tab-pane fade <?= $abaAtiva == 'despesas' ? 'show active' : '' ?>" id="nav-despesas" role="tabpanel" aria-labelledby="nav-despesas-tab" tabindex="0">
                        <div class="container-fluid">
                            <br />
                            <div class="row">
                                <h6 class="text-center">DESPESAS</h6>
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-desp">Valor Despesas</span>
                                        <?php $resumoDesp = $despesaModel->getResumoDespesas($_SESSION['idProc']); ?>                                        
                                        <input type="text" name="totalDespesas" value="R$ <?= number_format($resumoDesp['despesa'], 2, ",", "."); ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-desp" readonly />
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-conc">Valor Pago</span>
                                        <input type="text" name="despConc" value="R$ <?= number_format($resumoDesp['pagamento'], 2, ",", "."); ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-conc" readonly />
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-conc">Valor Glosado</span>
                                        <input type="text" name="despGl" value="R$ <?= number_format($resumoDesp['glosas'], 2, ",", "."); ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-conc" readonly />
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <table class="table table-sm table-hover m-auto">
                                    <thead>
                                        <tr class="text-center align-middle">
                                            <th class="col w-auto fw-semibold">Item</th>
                                            <th class="col w-auto fw-semibold">Ident. Pagamento</th>
                                            <th class="col w-auto fw-semibold">Segmento</th>
                                            <th class="col w-auto fw-semibold">Nº Documento</th>
                                            <th class="col w-auto fw-semibold">Dt Emissão</th>
                                            <th class="col w-auto fw-semibold">Valor</th>
                                            <th class="col w-auto fw-semibold">Data Pagamento</th>
                                            <th class="col w-auto fw-semibold">Valor Pago</th>
                                            <th class="col w-auto fw-semibold">Valor da Glosa</th>
                                            <th class="col w-auto fw-semibold">Editar</th>

                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $nItem = 0;
                                        $total = 0;
                                        $totalPg = 0;
                                        $totalGl = 0;
                                        $despesasPendentes = 0;
                                        $desps = $despesaModel->findByProcId($idProc);                                        
                                        if ($desps) 
                                        {
                                            foreach ($desps as $desp):                                                
                                                $idDesp = $desp->id;
                                                $idAcao = $desp->acao_id;
                                                $categoria = $desp->categoria;
                                                $numDoc = $desp->documento;
                                                $numPgto = $desp->pagamento;
                                                $dataDesp = $desp->data_desp;
                                                $valor = $desp->valor;
                                                $dataPg = $desp->data_pg;
                                                $valorPg = $desp->valor_pg;
                                                $valorGl = $desp->valor_gl;
                                                $motivoGl = $desp->motivo_gl;

                                                if ($categoria == "C") {
                                                    $categoria = "Custeio";
                                                } else if ($categoria == "K") {
                                                    $categoria = "Capital";
                                                }

                                                $prog = $programaModel->findById($idAcao);                                                
                                                if ($prog) 
                                                {                                                    
                                                    $acaoDesp = $prog->acao;
                                                }

                                                $data = new DateTime($dataDesp, $timezone);
                                                $dataDesp = $data->format('d/m/Y');

                                                if (isset($dataPg) && $dataPg != null) {
                                                    $dataP = new DateTime($dataPg, $timezone);
                                                    $dataPg = $dataP->format('d/m/Y');
                                                } else {
                                                    $dataPg = '';
                                                }

                                                if (isset($valorPg) && $valorPg != null) {
                                                    $valorPg = $valorPg;
                                                } else {
                                                    $valorPg = 0;
                                                }

                                                if (isset($valorGl) && $valorGl != null) {
                                                    $valorGl = $valorGl;
                                                } else {
                                                    $valorGl = 0;
                                                }

                                                if($valorGl > 0 && $valorGl == $valor){
                                                    $backPendente = "table-dark";
                                                } elseif($valorGl > 0) {
                                                    $backPendente = "table-secondary";
                                                } elseif ($valor == $valorPg) {
                                                    $backPendente = "table-success";
                                                } elseif ($valorPg > 0 && $valorPg != $valor) {
                                                    $backPendente = "table-danger";
                                                } else {
                                                    $backPendente = "table-warning";
                                                }

                                                $nItem = $nItem + 1;
                                                echo '<tr class="fw-lighter align-middle ' . $backPendente . '">';
                                                echo '<td scope="row" class="text-center">' . $nItem . '</td>';
                                                echo '<td class="text-center">' . $numPgto . '</td>';
                                                echo '<td class="">' . $acaoDesp . ' - ' . $categoria . '</td>';
                                                echo '<td class="text-center">' . $numDoc . '</td>';
                                                echo '<td class="text-center">' . $dataDesp . '</td>';
                                                echo '<td class="text-center">' . 'R$ ' . number_format($valor, 2, ",", ".") . '</td>';
                                                echo '<td class="text-center">' . $dataPg . '</td>';
                                                echo '<td class="text-center">' . 'R$ ' . number_format($valorPg, 2, ",", ".") . '</td>';
                                                echo '<td class="text-center">' . 'R$ ' . number_format($valorGl, 2, ",", ".") . '</td>';
                                                echo '<td class="text-center">';                                                
                                                echo '<a href="?editDesp=true&idDesp=' . $idDesp . '"><img src="img/icons/currency-dollar.svg" alt="Editar" title="Editar" /></a>&nbsp;&nbsp;';
                                                echo '<a href="?glosarDesp=true&idDesp=' . $idDesp . '"><img src="img/na.svg" alt="Glosar" title="Glosar" /></a><br />';                                                
                                                echo '</td>';
                                                echo '</tr>';

                                                $total = $total + $valor;
                                                $totalPg = $totalPg + $valorPg;
                                                $totalGl = $totalGl + $valorGl;
                                            endforeach;
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot class="table-group-divider">
                                        <tr>
                                            <th scope="row" colspan="5" class="text-center">Total</th>
                                            <th class="text-center"><?php echo 'R$ ' . number_format($total, 2, ",", "."); ?></th>
                                            <th scope="row"></th>
                                            <th class="text-center"><?php echo 'R$ ' . number_format($totalPg, 2, ",", "."); ?></th>
                                            <th class="text-center"><?php echo 'R$ ' . number_format($totalGl, 2, ",", "."); ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <br />
                        </div>
                    </div>
                </div>                

                <!-- CONCILIAÇÃO OCORRÊNCIAS -->
                <div class="tab-pane fade <?= $abaAtiva == 'ocorrencias' ? 'show active' : '' ?>" id="nav-ocorrencias" role="tabpanel" aria-labelledby="nav-ocorrencias-tab" tabindex="0">
                    <div class="container-fluid">
                        <br />
                        <div class="row">
                            <h6 class="text-center">CONCILIAÇÃO</h6>
                            <div class="col">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text col-3" id="inputGroup-conc">Saldo Contábil</span>
                                    <input type="text" name="despConc" value="R$ <?= number_format($saldoFinalT, 2, ",", "."); ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-conc" readonly />
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text col-3" id="inputGroup-banco">Saldo Bancário</span>
                                    <?php
                                    $sdConcOc = $conciliacaoModel->getSaldoConciliacao($idProc);
                                    
                                    if (round($saldoFinalT, 2) == round(($bancoFinal + $sdConcOc), 2)) {
                                        $backErro = "#D1E7DD";
                                    } else {
                                        $backErro = "#F8D7DA";
                                    }
                                    ?>
                                    <input type="text" name="totalBanco" value="R$ <?= number_format($bancoFinal, 2, ",", ".") ?>" class="col-9 form-control" style="background-color: <?= $backErro ?>" aria-describedby="inputGroup-banco" readonly />
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row my-auto">
                            <button type="button" class="col-2 btn btn-primary mx-2" data-bs-toggle="modal" data-bs-target="#ocorrenciaModal">Nova Ocorrência</button>
                            <div class="col-6 text-center mx-2 fw-semibold">Demonstração Contábil / Financeira</div>
                        </div>
                        <hr />
                        <div class="row">
                            <div class="col">
                                <table class="table table-sm table-striped table-hover m-auto">
                                    <thead>
                                        <tr class="text-center align-middle">
                                            <th colspan="3" class="col w-auto fw-semibold">Débitos não Demonstrados no Extrato</th>
                                        </tr>
                                        <tr class="text-center align-middle">
                                            <th class="col-8 fw-semibold" width="40%">Histórico</th>
                                            <th class="col-3 fw-semibold" width="10%">Valor (R$)</th>
                                            <th class="col-1 fw-semibold">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dadosConciliacao['debitos'] as $occD): ?>                                        
                                            <tr class="align-middle">
                                                <td><?= htmlspecialchars($occD->ocorrencia) ?> - <?= htmlspecialchars($occD->descricao) ?> - <?= htmlspecialchars($occD->dataOccFormatada) ?></td>
                                                <td class="text-center">R$ <?= number_format($occD->valorOcc, 2, ",", ".") ?></td>
                                                <td class="text-center">
                                                    <a href="?delOcc=true&idOcc=<?= $occD->id ?>"><img src="img/na.svg" alt="Deletar" title="Deletar"/></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="text-center align-middle">
                                            <th>Total</th>
                                            <th>R$ <?= number_format($dadosConciliacao['totalD'], 2, ",", ".") ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="col">
                                <table class="table table-sm table-striped table-hover m-auto">
                                    <thead>
                                        <tr class="text-center align-middle">
                                            <th colspan="3" class="col w-auto fw-semibold">Créditos não Demonstrados no Extrato</th>
                                        </tr>                                        
                                        <tr class="text-center align-middle">
                                            <th class="col-8 fw-semibold" width="40%">Histórico</th>
                                            <th class="col-3 fw-semibold" width="10%">Valor (R$)</th>
                                            <th class="col-1 fw-semibold">Ação</th>
                                        </tr>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dadosConciliacao['creditos'] as $occC): ?>
                                            <tr class="align-middle">
                                                <td><?= htmlspecialchars($occC->ocorrencia) ?> - <?= htmlspecialchars($occC->descricao) ?> - <?= $occC->dataOccFormatada ?></td>
                                                <td class="text-center">R$ <?= number_format($occC->valorOcc, 2, ",", ".") ?></td>
                                                <td class="text-center">
                                                    <a href="?delOcc=true&idOcc=<?= $occC->id ?>"><img src="img/na.svg" alt="Deletar" title="Deletar"/></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="text-center align-middle">
                                            <th>Total</th>
                                            <th>R$ <?= number_format($dadosConciliacao['totalC'], 2, ",", ".") ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <br>
                        <div class="row mt-4">
                            <div class="col">
                                <table class="table table-sm table-bordered m-auto" style="max-width: 600px;">
                                    <tbody>
                                        <?php
                                        // 1. Usando as variáveis corretas do array que criamos no Passo 1
                                        $tOcorr = $dadosConciliacao['totalD'] - $dadosConciliacao['totalC'];
                                        
                                        // 2. Definindo a mensagem e a cor (Bootstrap) dependendo do resultado
                                        if ($tOcorr > 0.001) { // Maior que zero
                                            $descConc = "Valor a Ressarcir";
                                            $corTexto = "text-danger"; // Vermelho
                                        } elseif ($tOcorr < -0.001) { // Menor que zero
                                            $descConc = "Valor Pertencente à Entidade";
                                            $corTexto = "text-success"; // Verde
                                        } else {
                                            $descConc = "Nenhum valor pendente";
                                            $corTexto = "text-secondary"; // Cinza
                                        }
                                        ?>
                                        <tr class="text-center align-middle <?= $corTexto ?> fs-6">
                                            <th class="w-50"><?= $descConc ?></th>
                                            
                                            <th class="w-50 fs-5">R$ <?= number_format(abs($tOcorr), 2, ",", ".") ?></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>                
            </div>
        </div>

        <!-- INÍCIO DOS MODAIS -->

        <!-- 1. Modal Novo Saldo -->                    
        <div class="modal fade modal-trigger" id="novoSaldoModal" tabindex="-1" aria-labelledby="novoSaldoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?novoSaldo=true" method="post" name="resumo">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="novoSaldoModalLabel">Novo Saldo</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="content-fluid">
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-acao">Ação</label>
                                        <select name="acao" class="form-select w-50 col-8" id="inputGroup-acao" required>
                                            <option <?= $variacaoM ?? 'selected'  ?> disabled="disabled">Selecione...</option>
                                            <?php
                                            $programa = $processoModel->findById($idProc)->tipo;
                                            $pddes = $programaModel->findByProgName($programa);
                                            if($pddes)
                                            {
                                                foreach($pddes as $pdde):
                                                    $acao = $pdde->acao;
                                                    $idAcao = $pdde->id;
                                                    echo '<option value="' . $idAcao . '">' . $acao . '</option>';
                                                endforeach;     
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-categoria">Categoria</label>
                                        <select name="categoria" class="form-select w-50 col-8" id="inputGroup-categoria" required>
                                            <option disabled="disabled" selected>Selecione...</option>
                                            <option value="C">Custeio</option>
                                            <option value="K">Capital</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-saldoInicial">Saldo Inicial</label>
                                        <input type="text" name="saldo24" value="" class="col-8 form-control" id="inputGroup-saldoInicial" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <input type="submit" class="btn btn-success" value="Gravar" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Modal Resumo -->
        <div class="modal fade modal-trigger" id="resumoModal" tabindex="-1" aria-labelledby="resumoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?updateSaldo=true" method="post" name="resumo">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="resumoModalLabel"><?= $acaoM . ' ' . $categoriaM ?></h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <input type="hidden" value="<?= $idSaldoM ?>" name="idSaldoM" />
                        </div>
                        <div class="modal-body">
                            <div class="content-fluid">
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-pgto">Recursos Próprios</label>
                                        <input type="text" name="rp25" value="<?= 'R$ ' . number_format($rpCYM, 2, ",", ".") ?>" class="col-8 form-control" id="inputGroup-pgto" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-pgto">Rentabilidade</label>
                                        <input type="text" name="rent25" value="<?= 'R$ ' . number_format($rentCYM, 2, ",", ".") ?>" class="col-8 form-control" id="inputGroup-pgto" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-pgto">Devolução ao FNDE</label>
                                        <input type="text" name="devol25" value="<?= 'R$ ' . number_format($devolCYM, 2, ",", ".") ?>" class="col-8 form-control" id="inputGroup-pgto" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <input type="submit" class="btn btn-warning" value="Atualizar" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. Nova Conta Modal -->
        <div class="modal fade modal-trigger" id="novaContaModal" tabindex="-1" aria-labelledby="novaContaModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?createBanco=true" method="post" name="saldo">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="novaContaModal">Inserir Nova Conta</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="content-fluid">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-5" for="inputGroup-agencia">Agência</label>
                                            <input type="text" name="novaAgencia" class="col-7 form-control" id="inputGroup-agencia" required />
                                        </div>
                                    </div>
                                    <div class="col-7">
                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-5" for="inputGroup-conta">Conta</label>
                                            <input type="text" name="novaConta" class="col-7 form-control" id="inputGroup-conta" required />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <h6>Saldo Inicial</h6>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-4" id="inputGroup-CC">Conta Corrente</span>
                                            <input type="text" name="siCorrente" value="R$ 0,00" class="col-8 form-control" aria-describedby="inputGroup-CC" required />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-PP01">Poupança 01</span>
                                        <input type="text" name="siPoup01" value="R$ 0,00" class="col-8 form-control" aria-describedby="inputGroup-PP01" required />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-PP51">Poupança 51</span>
                                        <input type="text" name="siPoup51" value="R$ 0,00" class="col-8 form-control" aria-describedby="inputGroup-PP51" required />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-inv">S. Público Aut.</span>
                                        <input type="text" name="siInvSPubl" value="R$ 0,00" class="col-8 form-control" aria-describedby="inputGroup-invSPubl" required />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-inv">BB RF CP Aut.</span>
                                        <input type="text" name="siInvBbRf" value="R$ 0,00" class="col-8 form-control" aria-describedby="inputGroup-invBbRf" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <input type="submit" class="btn btn-success" value="Adicionar" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 4. Modal Banco -->
        <div class="modal fade modal-trigger" id="bancoModal" tabindex="-1" aria-labelledby="bancoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?updateBanco=true" method="post" name="saldo">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="bancoModalLabel">Editar Saldo Final</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <input type="hidden" value="<?= $idContaM ?? ''; ?>" name="idContaM" />
                        </div>
                        <div class="modal-body">
                            <div class="content-fluid">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-5" for="inputGroup-agencia">Agência</label>
                                            <input type="text" name="agencia" value="<?= $agenciaM ?? '1234-5'; ?>" class="col-7 form-control" id="inputGroup-agencia" readonly />
                                        </div>
                                    </div>
                                    <div class="col-7">
                                        <div class="input-group input-group-sm mb-2">
                                            <label class="input-group-text col-5" for="inputGroup-conta">Conta</label>
                                            <input type="text" name="conta" value="<?= $contaM ?? '98765-5'; ?>" class="col-7 form-control" id="inputGroup-conta" readonly />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-4" id="inputGroup-CC">Conta Corrente</span>
                                            <input type="text" name="corrente" class="col-8 form-control" value="R$ <?= number_format($fCorrenteM ?? 0, 2, ',', '.') ?>" aria-describedby="inputGroup-CC" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-PP01">Poupança 01</span>
                                        <input type="text" name="poup01" class="col-8 form-control" value="R$ <?= number_format($fPoupanca01M ?? 0, 2, ',', '.') ?>" aria-describedby="inputGroup-PP01" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-PP51">Poupança 51</span>
                                        <input type="text" name="poup51" class="col-8 form-control" value="R$ <?= number_format($fPoupanca51M ?? 0, 2, ',', '.') ?>" aria-describedby="inputGroup-PP51" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-inv">S. Público Aut.</span>
                                        <input type="text" name="invSPubl" class="col-8 form-control" value="R$ <?= number_format($fSPubAutM ?? 0, 2, ',', '.') ?>" aria-describedby="inputGroup-invSPubl" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-invBbRf">BB RF CP Aut.</span>
                                        <input type="text" name="invBbRf" class="col-8 form-control" value="R$ <?= number_format($fBbRfCpM ?? 0, 2, ',', '.') ?>" aria-describedby="inputGroup-invBbRf" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <input type="submit" class="btn btn-warning" value="Atualizar" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 5. Modal Rentabilidade -->
        <div class="modal fade modal-trigger" id="rentabilidadeModal" tabindex="-1" aria-labelledby="rentabilidadeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="<?= $actionRent ?? '?includeRent=true' ?>" method="post" name="rentabilidade">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="rentabilidadeModalLabel"><?= $tituloRent ?? 'Nova Rentabilidade' ?></h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <input type="hidden" value="<?= $idRentM ?? '' ?>" name="idRentM" />
                        </div>
                        <div class="modal-body">
                            <div class="content-fluid">
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-agConta">AG / Conta</label>
                                        <?php
                                        if (isset($idRentM) && $idRentM != null) {
                                            if ($proc = $bancoModel->findById($idContaM)) {
                                                $agencia = $proc->agencia;
                                                $conta = $proc->conta;
                                            }
                                            
                                        ?>
                                            <input type="text" name="agConta" class="col-8 form-control" value="<?= $agencia . ' / ' . $conta ?>" aria-describedby="inputGroup-rMar" />
                                        <?php
                                        } else {
                                        ?>
                                            <select name="agConta" class="form-select w-50 col-8" id="inputGroup-agConta" required>
                                                <option selected disabled="disabled">Selecione...</option>
                                                <?php
                                                $contas = $bancoModel->findByProcId($_SESSION['idProc']);
                                                foreach ($contas as $conta):
                                                    $idContaR = $conta->id;
                                                    $agenciaR = $conta->agencia;
                                                    $contaR = $conta->conta;
                                                    echo '<option value="' . $idContaR . '">' . $agenciaR . ' / ' . $contaR . '</option>';
                                                endforeach;
                                                
                                                ?>
                                            </select>
                                        <?php
                                        }
                                        ?>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-ppApl">Poup. / Apl. Financeira</label>
                                        <select name="variacao" class="form-select w-50 col-8" id="inputGroup-ppApl" required>
                                            <option <?= $variacaoM ?? 'selected'  ?> disabled="disabled">Selecione...</option>
                                            <option value="Poupança 01" <?= isset($variacaoM) && $variacaoM == 'Poupança 01' ? 'selected' : ''; ?>>Poupança 01</option>
                                            <option value="Poupança 51" <?= isset($variacaoM) && $variacaoM == 'Poupança 51' ? 'selected' : ''; ?>>Poupança 51</option>
                                            <option value="S. Público Aut." <?= isset($variacaoM) && $variacaoM == 'S. Público Aut.' ? 'selected' : ''; ?>>S. Público Aut.</option>
                                            <option value="BB RF CP Aut." <?= isset($variacaoM) && $variacaoM == 'BB RF CP Aut.' ? 'selected' : ''; ?>>BB RF CP Aut.</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rJan">Janeiro</span>
                                            <input type="text" name="rJan" class="col-7 form-control" value="<?= isset($rJanM) ? 'R$ ' . number_format($rJanM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rJan" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rFev">Fevereiro</span>
                                            <input type="text" name="rFev" class="col-7 form-control" value="<?= isset($rFevM) ? 'R$ ' . number_format($rFevM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rFev" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rMar">Março</span>
                                            <input type="text" name="rMar" class="col-7 form-control" value="<?= isset($rMarM) ? 'R$ ' . number_format($rMarM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rMar" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rAbr">Abril</span>
                                            <input type="text" name="rAbr" class="col-7 form-control" value="<?= isset($rAbrM) ? 'R$ ' . number_format($rAbrM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rAbr" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rMai">Maio</span>
                                            <input type="text" name="rMai" class="col-7 form-control" value="<?= isset($rMaiM) ? 'R$ ' . number_format($rMaiM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rMai" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rJun">Junho</span>
                                            <input type="text" name="rJun" class="col-7 form-control" value="<?= isset($rJunM) ? 'R$ ' . number_format($rJunM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rJun" />
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rJul">Julho</span>
                                            <input type="text" name="rJul" class="col-7 form-control" value="<?= isset($rJulM) ? 'R$ ' . number_format($rJulM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rJul" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rAgo">Agosto</span>
                                            <input type="text" name="rAgo" class="col-7 form-control" value="<?= isset($rAgoM) ? 'R$ ' . number_format($rAgoM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rAgo" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rSet">Setembro</span>
                                            <input type="text" name="rSet" class="col-7 form-control" value="<?= isset($rSetM) ? 'R$ ' . number_format($rSetM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rSet" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rOut">Outubro</span>
                                            <input type="text" name="rOut" class="col-7 form-control" value="<?= isset($rOutM) ? 'R$ ' . number_format($rOutM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rOut" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rNov">Novembro</span>
                                            <input type="text" name="rNov" class="col-7 form-control" value="<?= isset($rNovM) ? 'R$ ' . number_format($rNovM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rNov" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-5" id="inputGroup-rDez">Dezembro</span>
                                            <input type="text" name="rDez" class="col-7 form-control" value="<?= isset($rDezM) ? 'R$ ' . number_format($rDezM, 2, ",", ".") : ''; ?>" aria-describedby="inputGroup-rDez" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <?= $botaoRent; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- 6. Modal Pagamento -->
        <div class="modal fade modal-trigger" id="pagamentoModal" tabindex="-1" aria-labelledby="pagamentoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?update=true" method="post" name="pagamento">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="pagamentoModalLabel">Atualizar Pagamento</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <input type="hidden" value="<?= $idDespM ?? '' ?>" name="idDespM" />
                        </div>
                        <div class="modal-body">
                            <div class="content-fluid">                                        
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-pgto">Ident. Pagamento</label>
                                        <input type="text" name="pagamento" value="<?= $numPgtoM ?? '' ?>" class="col-8 form-control" id="inputGroup-pgto" readonly />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-4" id="inputGroup-dataDoc">Data Pagamento</span>
                                            <input type="date" name="dataPg" class="col-8 form-control" value="<?= $dataPgM ?? '' ?>" aria-describedby="inputGroup-dataDoc" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-valDesp">Valor Pago</span>
                                        <input type="text" name="valPago" class="col-8 form-control" value="<?= $valorPgReal ?? '' ?>" aria-describedby="inputGroup-valDesp" />
                                    </div>
                                </div>                                    
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <input type="submit" class="btn btn-warning" value="Atualizar" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 7. Modal Glosa -->
        <div class="modal fade modal-trigger" id="glosaModal" tabindex="-1" aria-labelledby="glosaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?glosa=true" method="post" name="glosa">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="glosaModalLabel">Glosar Despesa</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <input type="hidden" value="<?= $idDespM ?? '' ?>" name="idDespM" />
                        </div>
                        <div class="modal-body">
                            <div class="content-fluid">
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-valGlosa">Valor da Glosa</span>
                                        <input type="text" name="valGlosa" class="col-8 form-control" value="<?= $valorGlReal ?? '' ?>" aria-describedby="inputGroup-valGlosa" required />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-motGlosa">Motivo</label>
                                        <input type="text" name="motivoGlosa" value="<?= $motivoGlM ?? '' ?>" class="col-8 form-control" id="inputGroup-motGlosa" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <input type="submit" class="btn btn-warning" value="Atualizar" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 8. Modal Ocorrências -->
        <div class="modal fade" id="ocorrenciaModal" tabindex="-1" aria-labelledby="ocorrenciaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?novaOcorrencia=true" method="post" name="ocorrencia">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="ocorrenciaModalLabel">Nova Ocorrência</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="content-fluid">
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-ocorrencia">Ocorrência</span>
                                        <select name="ocorrencia" class="form-select col-9" id="inputGroup-ocorrencia" required>
                                            <option disabled selected>Selecione...</option>
                                            <?php
                                            $ocorrencias = $conciliacaoModel->listarOcorrencias();
                                            if ($ocorrencias) {
                                                foreach ($ocorrencias as $occ) {
                                                    $idOcc = $occ->id;
                                                    $ocorrencia = $occ->ocorrencia;
                                                    echo '<option value="' . $idOcc . '">' . $ocorrencia . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-descricao">Descrição</span>
                                        <textarea name="descricao" class="w-50 col-9 form-control" aria-describedby="inputGroup-descricao" rows="3" maxlength="1025"></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-dataOcc">Data</span>
                                            <input type="date" name="dataOcc" class="col-9 form-control" aria-describedby="inputGroup-dataOcc" required />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-valorOcc">Valor</span>
                                        <input type="text" name="valorOcc" class="col-9 form-control" aria-describedby="inputGroup-valorOcc" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                            <input type="submit" class="btn btn-success" value="Incluir" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Fim Modal Ocorrencias -->
        <!-- Fim do Conteúdo  -->

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
</body>

</html>