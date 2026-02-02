<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

$timezone = new DateTimeZone("America/Sao_Paulo");

use Source\Contabilidade;
use Source\Logs;
use Source\User;
use Source\Instituicao;
use Source\Processo;
use Source\Banco;
use Source\Repasse;
use Source\Despesa;
use Source\Documento;
use Source\Pendencia;
use Source\Programa;

// Criar instâncias do modelo.
// A conexão com o banco já é feita dentro da classe.
$userModel = new User();
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

// Verifica se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}
else
{
    $loggedUser = $userModel->findById($_SESSION['user_id']);
    if ($loggedUser) {
        $userName = $loggedUser->nome;
        $perfil = $loggedUser->perfil;
    }
}

$currentUser = $_SESSION['user_id'];
$currentProcess = (int) $_SESSION['idProc'];
$statusProcesso = $processoModel->abrirTramitacao($currentProcess);
if(empty($statusProcesso))
{
    $currentStatus = 1;
    $statusPC = "Aguardando Entrega";
}
else
{
    $currentStatus = $statusProcesso->status_id;
    $statusPC = $statusProcesso->status_pc;
}

$despesas = $despesaModel->findByProcId($currentProcess);

$numPendencias = $pendenciaModel->contarPendencias($currentProcess);
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
    <link rel="stylesheet" href="./css/boostrap/bootstrap.min.css" rel="stylesheet">    
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
    <?php

    if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
        $_SESSION['flag'] = false;
        session_unset();
        header("Location:index.php?status=logoff");
    }
    $firstName = substr($userName,0,strpos($userName," "));

    if(isset($_REQUEST['pddeAE']) && $_REQUEST['pddeAE'] == true){
        $_SESSION['nav'] = array("active","","","","");
        $_SESSION['navShow'] = array("show active","","","","");
        $_SESSION['sel'] = array("true","false","false","false","false");
        header("Location:pddePC.php");
    }

    if(isset($_REQUEST['pddeAF']) && $_REQUEST['pddeAF'] == true){
        $_SESSION['navF'] = array("active","","","","","");
        $_SESSION['navShowF'] = array("show active","","","","","");
        $_SESSION['selF'] = array("true","false","false","false","false","false");
        header("Location:pddeFinanc.php");
    }

    if(isset($_REQUEST['analiseTC']) && $_REQUEST['analiseTC'] == true){
        $_SESSION['nav'] = array("active","","","","");
        $_SESSION['navShow'] = array("show active","","","","");
        $_SESSION['sel'] = array("true","false","false","false","false");
        header("Location:termoPC.php");
    }

    if(isset($currentProcess) && $currentProcess > 0)
    {   
        $proc = $processoModel->findById($currentProcess);
        if($proc){
            $idProc = $proc->id;
            $orgao = $proc->orgao;
            $numero = $proc->numero;
            $ano = $proc->ano;
            $digito = $proc->digito;
            $assunto = $proc->assunto;                       
            $tipo = $proc->tipo;
            $idInst = $proc->instituicao_id;

            $inst = $instituicaoModel->findById($idInst);
            if($inst)
            {
                $instituicao = $inst->instituicao;
                $cnpj = $inst->cnpj;
                $iEmail = $inst->email;
                $iEndereco = $inst->endereco;
                $inep = $inst->inep;
                $iTelefone = $inst->telefone;
                $iCont = $inst->cont_id;
            }
        }
    }    
    else {
        header('Location:buscar.php');
    }
    
    $cnpj = substr($cnpj,0,2) . "." . substr($cnpj,2,3) . "." . substr($cnpj,5,3) . "/" . substr($cnpj,8,4) . "-" . substr($cnpj,12,2);
        
    ?>

    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Prestação de Contas 2024 - PDDE
                </h1>
            </div>
            <!-- Início do Conteúdo  -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-6">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-nomeEnt">Entidade</span>
                            <input type="text" name="nomeEnt" value="<?php echo $instituicao; ?>" class="col-9 form-control" aria-describedby="inputGroup-nomeEnt" readonly/>
                        </div>        
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-processo">Processo</span>
                            <input type="text" name="campo3" value="<?php echo $orgao . '.' . $numero . '/' . $ano . '-' . $digito; ?>" class="col-9 form-control" aria-describedby="inputGroup-processo" readonly/>
                        </div>
                    </div>    
                    <div class="col-6">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-assuntoProc">Assunto</span>
                            <input type="text" name="assuntoProc" value="<?php echo $assunto . ' - ' . $tipo; ?>" class="col-9 form-control" aria-describedby="inputGroup-assuntoProc" readonly/>
                        </div>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-statusProc">Status</span>
                            <input type="text" name="statusProc" value="<?php echo $statusPC; ?>" class="col-9 form-control" aria-describedby="inputGroup-statusProc" readonly/>
                        </div>                          
                    </div>
                </div>
            </div>

            <div class="container-fluid">        
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link <?php echo $_SESSION['nav'][0]; ?>" id="nav-dados-tab" data-bs-toggle="tab" data-bs-target="#nav-dados" type="button" role="tab" aria-controls="nav-dados" aria-selected="<?php echo $_SESSION['sel'][0]; ?>">Dados Gerais</button>
                    <button class="nav-link <?php echo $_SESSION['nav'][1]; ?>" id="nav-dadosfin-tab" data-bs-toggle="tab" data-bs-target="#nav-dadosfin" type="button" role="tab" aria-controls="nav-dadosfin" aria-selected="<?php echo $_SESSION['sel'][1]; ?>">Dados Financeiros</button>
                    <button class="nav-link <?php echo $_SESSION['nav'][2]; ?>" id="nav-quali-tab" data-bs-toggle="tab" data-bs-target="#nav-quali" type="button" role="tab" aria-controls="nav-quali" aria-selected="<?php echo $_SESSION['sel'][2]; ?>">Análise da Execução</button>
                    <button class="nav-link <?php echo $_SESSION['nav'][3]; ?>" id="nav-finan-tab" data-bs-toggle="tab" data-bs-target="#nav-finan" type="button" role="tab" aria-controls="nav-finan" aria-selected="<?php echo $_SESSION['sel'][3]; ?>">Análise Financeira</button>
                    <button class="nav-link <?php echo $_SESSION['nav'][4]; ?>" id="nav-pendencia-tab" data-bs-toggle="tab" data-bs-target="#nav-pendencia" type="button" role="tab" aria-controls="nav-pendencia" aria-selected="<?php echo $_SESSION['sel'][4]; ?>">Histórico de Pendências</button>              
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">

                    <!-- DADOS GERAIS -->
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][0]; ?>" id="nav-dados" role="tabpanel" aria-labelledby="nav-dados-tab" tabindex="0">
                        <div class="container-fluid">
                            <br />
                            <div class="row">
                                <div class="col">
                                    <h6>Entidade</h6>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-inep">INEP</span>
                                        <input type="text" name="inep" value="<?php echo $inep; ?>" class="col-9 form-control" aria-describedby="inputGroup-inep" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-cnpj">CNPJ</span>
                                        <input type="text" name="cnpj" value="<?php echo $cnpj; ?>" class="col-9 form-control" aria-describedby="inputGroup-cnpj" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-email">E-mail</span>
                                        <input type="text" name="email" value="<?php echo $iEmail; ?>" class="col-9 form-control" aria-describedby="inputGroup-email" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-end">Endereço</span>
                                        <input type="text" name="campo2" value="<?php echo $iEndereco; ?>" value="Rua Tiradentes, 3180 - Montanhão" class="col-9 form-control" aria-describedby="inputGroup-end" readonly/>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-telefone">Telefone</span>
                                        <input type="text" name="telefone" value="<?php echo $iTelefone; ?>" class="col-9 form-control" aria-describedby="inputGroup-telefone" readonly/>
                                    </div>
                                </div>
                                <div class="col">
                                    <?php
                                    $cont = $contModel->findById($iCont);
                                    if($cont)
                                    {
                                        $cNome = $cont->c_nome;
                                        $cTelefone = $cont->c_telefone;
                                        $cEmail = $cont->c_email;
                                        ?>
                                        <h6>Contabilidade</h6>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-nomeC">Nome</span>
                                            <input type="text" name="nomeC" value="<?php echo $cNome; ?>" class="col-9 form-control" aria-describedby="inputGroup-nomeC" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-telefoneC">Telefone</span>
                                            <input type="text" name="telefoneC" value="<?php echo $cTelefone; ?>" class="col-9 form-control" aria-describedby="inputGroup-telefoneC" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-emailC">E-mail</span>
                                            <input type="text" name="emailC" value="<?php echo $cEmail; ?>" class="col-9 form-control" aria-describedby="inputGroup-emailC" readonly/>
                                        </div>
                                        <?php
                                    }?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DADOS FINANCEIROS -->
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][1]; ?>" id="nav-dadosfin" role="tabpanel" aria-labelledby="nav-dadosfin-tab" tabindex="0">
                        <div class="container-fluid">                    
                            <br />
                            <div class="row">
                                <div class="col">
                                    <h6>Saldo Bancário em 31/12/2023</h6>
                                    <table class="table table-light table-hover table-sm">
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

                                            $somaCorrenteLY =  0;
                                            $somaPoupancaLY =  0;
                                            $somaFundosLY =  0;

                                            $lys = $bancoModel->findLYById($currentProcess);                                            
                                            foreach($lys as $ly):                                            
                                                $agencia = $ly->agencia;
                                                $conta = $ly->conta;
                                                $sCorrenteLY = $ly->cc_LY;
                                                $pp_01_LY = $ly->pp_01_LY;
                                                $pp_51_LY = $ly->pp_51_LY;
                                                $spubl_LY = $ly->spubl_LY;
                                                $bb_rf_cp_LY = $ly->bb_rf_cp_LY;

                                                $sPoupancaLY = $pp_01_LY + $pp_51_LY;
                                                $sFundosLY =  $spubl_LY + $bb_rf_cp_LY;

                                                echo '<tr>';
                                                echo '<td scope="row">' . $agencia . '</td>';
                                                echo '<td>' . $conta . '</td>';
                                                echo '<td>R$ ' . number_format($sCorrenteLY, '2', ',', '.'). '</td>';
                                                echo '<td>R$ ' . number_format($sPoupancaLY, '2', ',', '.'). '</td>';
                                                echo '<td>R$ ' . number_format($sFundosLY, '2', ',', '.'). '</td>';
                                                echo '</tr>';

                                                $somaCorrenteLY =  $somaCorrenteLY + $sCorrenteLY;
                                                $somaPoupancaLY =  $somaPoupancaLY + $sPoupancaLY;
                                                $somaFundosLY =  $somaFundosLY + $sFundosLY;
                                            endforeach;

                                            ?>                                    
                                        </tbody>
                                        <tfoot class="table-group-divider">
                                            <tr>
                                                <th scope="row" colspan="2">Total</th>
                                                <th>R$ <?= number_format($somaCorrenteLY, '2', ',', '.') ?></th>
                                                <th>R$ <?= number_format($somaPoupancaLY, '2', ',', '.') ?></th>
                                                <th>R$ <?= number_format($somaFundosLY, '2', ',', '.') ?></th>
                                                
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <br />
                                    <br />
                                    <h6>Saldo Bancário em 31/12/2024</h6>
                                    <table class="table table-light table-hover table-sm">
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

                                            $somaCorrenteCY =  0;
                                            $somaPoupancaCY =  0;
                                            $somaFundosCY =  0;
                                            $sCorrenteCY = 0;
                                            $pp_01_CY = 0;
                                            $pp_51_CY = 0;
                                            $spubl_CY = 0;
                                            $bb_rf_cp_CY = 0;

                                            $cys = $bancoModel->findCYById($currentProcess);
                                            foreach($cys as $cy):                                            
                                                $agencia = $cy->agencia;
                                                $conta = $cy->conta;
                                                $sCorrenteCY = $cy->cc_CY;
                                                $pp_01_CY = $cy->pp_01_CY;
                                                $pp_51_CY = $cy->pp_51_CY;
                                                $spubl_CY = $cy->spubl_CY;
                                                $bb_rf_cp_CY = $cy->bb_rf_cp_CY;

                                                if(empty($sCorrenteCY))
                                                {
                                                    $sCorrenteCY = 0;
                                                }
                                                $sPoupancaCY = $pp_01_CY + $pp_51_CY;
                                                $sFundosCY =  $spubl_CY + $bb_rf_cp_CY;

                                                echo '<tr>';
                                                echo '<td scope="row">' . $agencia . '</td>';
                                                echo '<td>' . $conta . '</td>';                                                
                                                echo '<td>R$ ' . number_format($sCorrenteCY, '2', ',', '.') . '</td>';
                                                echo '<td>R$ ' . number_format($sPoupancaCY, '2', ',', '.') . '</td>';
                                                echo '<td>R$ ' . number_format($sFundosCY, '2', ',', '.') . '</td>';
                                                echo '</tr>';

                                                $somaCorrenteCY = $somaCorrenteCY + $sCorrenteCY;
                                                $somaPoupancaCY = $somaPoupancaCY + $sPoupancaCY;
                                                $somaFundosCY = $somaFundosCY + $sFundosCY;
                                            endforeach;

                                            ?>                                    
                                        </tbody>
                                        <tfoot class="table-group-divider">
                                            <tr>
                                                <th scope="row" colspan="2">Total</th>
                                                <th>R$ <?= number_format($somaCorrenteCY, '2', ',', '.') ?></th>
                                                <th>R$ <?= number_format($somaPoupancaCY, '2', ',', '.') ?></th>
                                                <th>R$ <?= number_format($somaFundosCY, '2', ',', '.') ?></th>
                                                
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="col">
                                    <h6>Repasse 2024</h6>
                                    <table class="table table-light table-hover table-sm">
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

                                            $somaCusteio =  0;
                                            $somaCapital =  0;
                                            $somaRepasse =  0;

                                            $repasses = $repasseModel->findById($currentProcess);                                            
                                            if($repasses)
                                            {
                                                foreach($repasses as $repasse):
                                                    $destinacao = $repasse->destinacao;
                                                    $rCusteio = $repasse->custeio;
                                                    $rCapital = $repasse->capital;
                                                    $rData = $repasse->data;                                        

                                                    $rTotal = $rCusteio + $rCapital;
                                                    $dataRepasse = new DateTime($rData, $timezone);
                                                    $dataRepasse = $dataRepasse->format('d/m/Y');

                                                    echo '<tr>';
                                                    echo '<td scope="row">' . $destinacao . '</td>';
                                                    echo '<td>R$ ' . number_format($rCusteio, '2', ',', '.'). '</td>';
                                                    echo '<td>R$ ' . number_format($rCapital, '2', ',', '.'). '</td>';                                        
                                                    echo '<td>R$ ' . number_format($rTotal, '2', ',', '.'). '</td>';
                                                    echo '<td>' . $dataRepasse . '</td>';
                                                    echo '</tr>';

                                                    $somaCusteio =  $somaCusteio + $rCusteio;
                                                    $somaCapital =  $somaCapital + $rCapital;
                                                    $somaRepasse =  $somaRepasse + $rTotal;
                                                endforeach;
                                            }                                           

                                            ?>
                                        </tbody>
                                        <tfoot class="table-group-divider">
                                            <tr>
                                                <th scope="row">Total</th>
                                                <th>R$ <?= number_format($somaCusteio, '2', ',', '.') ?></th>
                                                <th>R$ <?= number_format($somaCapital, '2', ',', '.') ?></th>
                                                <th>R$ <?= number_format($somaRepasse, '2', ',', '.') ?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>                            
                                </div>
                            </div>                    
                        </div>
                    </div>

                    <!-- ANÁLISE DA EXECUÇÃO -->
                    <?php
                        if(isset($_REQUEST['entrega']) && $_REQUEST['entrega'] == true)
                        {
                            if(isset($entrega) && $entrega != ""){
                                echo "<script>alert('ERRO!! A entrega já foi registrada');</script>";
                                header('Location:pddePC.php?status=entrega');
                            }
                            elseif($_SESSION['perfil'] == 'ofc') 
                            {
                                $acao = "Tentou receber o processo de id " . $currentProcess;
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                echo "<script>alert('ERRO!! Usuário não autorizado');</script>";
                            }
                            else
                            {                                    
                                $receber = $processoModel->receberProcesso($currentProcess);
                                if($receber)
                                {
                                    $acao = "Recebeu o processo de id " . $currentProcess;
                                     $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                    ]);
                                }
                            }
                            
                            $_SESSION['nav'] = array("","","active","","");
                            $_SESSION['navShow'] = array("","","show active","","");
                            $_SESSION['sel'] = array("false","false","true","false","false"); 
                            header('Location:pddePC.php?status=success');
                        }
                        
                        $entrega = "";
                        $usuarioExId = "";
                        $dataAnaliseEx = "";
                        $dataEncAf = "";
                        $obsAnaliseEx = "";
                        $movimento = "";
                        $savedFlag = "";
                        $pendente = "";

                        $proc = $processoModel->abrirTramitacao($currentProcess);                        
                                               
                        if($proc)
                        {
                            $entrega = $proc->data_ent;
                            $usuarioExId = $proc->usuario_ex_id;
                            $dataAnaliseEx = $proc->data_analise_ex;
                            $dataEncAf = $proc->data_enc_af;
                            $obsAnaliseEx = $proc->obs_analise_ex;
                            $movimento = $proc->s_movimento;
                            $savedFlag = $proc->saved_flag;
                            $pendente = $proc->pendente;                    
                        }
                        
                        if(isset($entrega) && $entrega != ""){
                            $entrega = new DateTime($entrega,$timezone);
                            $entrega = $entrega->format('d/m/Y');
                        }

                        if(empty($usuarioExId))
                        {
                            $nomeUsuarioEx = "";
                        }
                        else
                        {
                            $nomeUsuarioEx = $userModel->findById($usuarioExId)->nome;
                        }                        

                        if(isset($dataAnaliseEx) && $dataAnaliseEx != ""){
                            $dataAnaliseEx = new DateTime($dataAnaliseEx,$timezone);
                            $dataAnaliseEx = $dataAnaliseEx->format('d/m/Y');
                        }

                        if(isset($dataEncAf) && $dataEncAf != ""){
                            $dataEncAf = new DateTime($dataEncAf,$timezone);
                            $dataEncAf = $dataEncAf->format('d/m/Y');
                        }

                        if(isset($movimento) && $movimento != "" && $movimento == "1"){
                            $movimento = "checked";                    
                        }                        
                    ?>
                    
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][2]; ?>" id="nav-quali" role="tabpanel" aria-labelledby="nav-quali-tab" tabindex="0">                
                        <form method="POST" action="?saveExec=true">
                            <div class="container-fluid">
                                <br />
                                <div class="row">
                                    <div class="col">
                                        <h6>Análise da Execução</h6>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-dataEntrega">Data da Entrega</span>
                                            <input type="text" name="dataEntrega" value="<?= $entrega; ?>" class="col-9 form-control" aria-describedby="inputGroup-dataEntrega" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-usuarioQ">Responsável</span>
                                            <input type="text" name="usuario" value="<?= $nomeUsuarioEx ?>" class="col-9 form-control" aria-describedby="inputGroup-usuario" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-dataAnalQ">Data da Análise</span>
                                            <input type="text" name="dataAnal" value="<?= $dataAnaliseEx; ?>" class="col-9 form-control" aria-describedby="inputGroup-dataAnal" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-dataEncFin">Enc. Anál. Financeira</span>
                                            <input type="text" name="dataEncFin" value="<?= $dataEncAf; ?>" class="col-9 form-control" aria-describedby="inputGroup-dataEncFin" readonly/>
                                        </div>
                                        <div class="mb-2">
                                            <?php
                                            if($currentStatus == 1){
                                                echo '<button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#entregaModal">Registrar Entrega</button>';
                                            } else {
                                                echo '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#despesaModal">Incluir Nova Despesa</button>';
                                            }
                                            ?>                                
                                            <!-- Modal Entrega -->
                                            <div class="modal fade" id="entregaModal" tabindex="-1" aria-labelledby="entregaModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h2 class="modal-title fs-5" id="entregaModalLabel">Deseja registrar a entrega da Prestação de Contas?</h2>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <!--<div class="modal-body">
                                                            Deseja realmente sair?
                                                        </div>-->
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">NÃO</button>
                                                            <button type="button" class="btn btn-success" onclick="location.href='?entrega=true'">SIM</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input type="checkbox" name="checkMov" class="form-check-input" value="1" role="switch" id="checkMovimento" <?= $movimento ?>>
                                            <label class="form-check-label" for="checkMovimento">Sem Movimento</label>
                                        </div>
                                    </div>                            
                                    <div class="col">
                                        <h6>Observações</h6>
                                        <div class="form-floating">
                                            <textarea name="analObs" class="form-control" placeholder="" id="analObs" style="height: 120px"><?= $obsAnaliseEx ?></textarea>
                                            <label for="analObs"></label>
                                        </div>
                                        <!-- <input type="submit" form="execucao" method="get" action="?obsExe=true" class="btn btn-primary my-2" value="Salvar Observações" /> -->
                                    </div>                            
                                </div>                        

                                <div class="row">
                                    <table class="table table-sm table-hover m-auto">
                                        <thead>
                                            <tr class="text-center align-middle">
                                                <th class="col w-auto fw-semibold">Item</th>
                                                <th class="col w-auto fw-semibold">Categoria</th>
                                                <th class="col w-auto fw-semibold">Ação</th>
                                                <th class="col w-auto fw-semibold">Fornecedor</th>
                                                <th class="col w-auto fw-semibold">CNPJ</th>
                                                <th class="col w-auto fw-semibold">Bens e Materiais ou Serviços</th>
                                                <th class="col w-auto fw-semibold">Nº Documento</th>
                                                <th class="col w-auto fw-semibold">Dt Emissão</th>
                                                <th class="col w-auto fw-semibold">Ident. Pgto</th>
                                                <th class="col w-auto fw-semibold">Valor</th>
                                                <th class="col w-auto fw-semibold">Ação</th>                                        
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $nItem = 0;
                                            $total = 0;                                            
                                            $despesasPendentes = 0;                                                
                                            if(!empty($despesas))
                                            {
                                                foreach($despesas as $despesa):                                                                                                            
                                                    $idDesp = $despesa->id;
                                                    $idAcao = $despesa->acao_id;
                                                    $categoria = $despesa->categoria;
                                                    $fornecedor = $despesa->fornecedor;
                                                    $cnpjForn = $despesa->cnpj_forn;
                                                    $descricao = $despesa->descricao;
                                                    $numDoc = $despesa->documento;
                                                    $numPgto = $despesa->pagamento;
                                                    $dataDesp = $despesa->data_desp;
                                                    $valor = $despesa->valor;
                                                    $checkProg = $despesa->check_prog;
                                                    $checkAta = $despesa->check_ata;
                                                    $checkEnq = $despesa->check_enq;
                                                    $checkCons = $despesa->check_cons;
                                                    
                                                    if($categoria == "C"){
                                                        $categoria = "Custeio";
                                                    }
                                                    else if ($categoria == "K"){
                                                        $categoria = "Capital";
                                                    }

                                                    $programa = $programaModel->findById($idAcao);                                                       
                                                    if($programa) 
                                                    {                                                                                                                                    
                                                        $acaoDesp = $programa->acao;                                                            
                                                    }                                                        

                                                    $cnpjForn = substr($cnpjForn,0,2) . "." . substr($cnpjForn,2,3) . "." . substr($cnpjForn,5,3) . "/" . substr($cnpjForn,8,4) . "-" . substr($cnpjForn,12,2); 
                                                    $data = new DateTime($dataDesp, $timezone);
                                                    $dataDesp = $data->format('d/m/Y');
                                                    
                                                    
                                                    if($checkProg == false || $checkCons == false || $checkEnq == false || $checkAta == false){
                                                        $backPendente = "table-danger";
                                                        $despesasPendentes = $despesasPendentes + 1;
                                                    }
                                                    else{
                                                        $backPendente = "table-success";
                                                    }
                                                    
                                                    $nItem = $nItem + 1;
                                                    echo '<tr class="fw-lighter align-middle ' . $backPendente . '">';
                                                    echo '<td scope="row" class="text-center">' . $nItem .'</td>';
                                                    echo '<td class="">' . $categoria . '</td>';
                                                    echo '<td class="">' . $acaoDesp . '</td>';
                                                    echo '<td>' . $fornecedor .'</td>';
                                                    echo '<td class="text-center">' . $cnpjForn .'</td>';
                                                    echo '<td>' . $descricao . '</td>';
                                                    echo '<td class="text-center">' . $numDoc .'</td>';
                                                    echo '<td class="text-center">' . $dataDesp .'</td>';
                                                    echo '<td class="text-center">' . $numPgto .'</td>';
                                                    echo '<td class="text-center">' . 'R$ ' . number_format($valor, 2, ",", ".") . '</td>';
                                                    echo '<td class="text-center">';
                                                    //echo '<a href="?editDesp=true&idDesp=' . $idDesp . '" ><img src="img/pencil-alt.svg" alt="Editar" title="Editar" /></a><br />';
                                                    //echo '<a href="?editDesp=true&idDesp=' . $idDesp . '" role="button" data-bs-toggle="modal" data-bs-target="#despesaModal" id="modalDespesa"><img src="img/pencil-alt.svg" alt="Editar" title="Editar" /></a><br />';
                                                    echo '<a href="?editDesp=true&idDesp=' . $idDesp . '"><img src="img/pencil-alt.svg" alt="Editar" title="Editar" /></a><br />';
                                                    //echo '<a onclick="location.href=\'?editDesp=true&idDesp=' . $idDesp . '\'"><img src="img/pencil-alt.svg" alt="Editar" title="Editar" /></a><br />';
                                                    echo '<a href="?delDesp=true&idDesp=' . $idDesp . '"><img src="img/na.svg" alt="Deletar" title="Deletar"/></a>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                    
                                                    $total = $total + $valor;
                                                endforeach;
                                            }
                                            ?>                                   
                                        </tbody>
                                        <tfoot class="table-group-divider">
                                            <tr>
                                            <th scope="row" colspan="8">Total</th>
                                            <th class="text-end" colspan="2"><?php echo 'R$ ' . number_format($total, 2, ",", "."); ?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <br />                        
                                <div class="row">
                                    <div class="col">
                                    </div>                            
                                    <div class="col text-end">                            
                                        <?php
                                        if($currentStatus == 2) {
                                            ?>
                                            <input type="submit" class="btn btn-success" value="Gravar Status" />
                                            <?php
                                        }
                                        else if ($currentStatus > 2 && $savedFlag == 1)
                                        {
                                            ?>
                                            
                                            <input type="submit" class="btn btn-warning" value="Atualizar Status" />
                                            <button type="button" class="btn btn-primary" onclick="location.href='?encFin=true'">Encaminhar Análise Financeira</button>
                                            <?php
                                        }
                                        ?>
                                        <div></div><br />
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php                    
                            if(isset($_REQUEST['saveExec']) && $_REQUEST['saveExec'] == true){  
                                $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);                                
                                $despesasPendentes = $despesasPendentes + $numPendencias;

                                if($despesasPendentes > 0)
                                {                            
                                    $idSts = 4;
                                    $pendente = 1;
                                }
                                else
                                {
                                    $idSts = 3;
                                    $pendente = 0;
                                }
                                
                                $proc = $processoModel->abrirTramitacao($currentProcess);                                
                                if($proc)
                                {
                                    $idUserEx = $proc->usuario_ex_id;
                                    $analExDate = $proc->data_analise_ex;
                                    $exFlag = $proc->saved_flag;
                                }

                                if(isset($exFlag) && $exFlag == 1){
                                    $idUserEx = $idUserEx;
                                    //$analExDate = $analExDate;
                                }
                                else if($exFlag == 0)
                                {
                                    $idUserEx = $_SESSION['user_id'];
                                    //$analExDate = $dtAgora;
                                }                                
                                
                                $saveExec = $processoModel->saveExecucao($postData, $idSts, $idUserEx, $pendente, $currentProcess);
                                
                                $_SESSION['nav'] = array("","","active","","");
                                $_SESSION['navShow'] = array("","","show active","","");
                                $_SESSION['sel'] = array("false","false","true","false","false");  
                                header('Location:pddePC.php');                        
                            }
                            
                            if(isset($_REQUEST['encFin']) && $_REQUEST['encFin'] == true){                                
                                $despesasPendentes = $despesasPendentes + $numPendencias;

                                if($despesasPendentes > 0)
                                {
                                    //echo "<script>alert('Ainda existe(m) " . $despesasPendentes . " pendência(s)!!!');</script>";
                                    ?>
                                    <a data-bs-toggle="modal" data-bs-target="#avancarPend" id="avancarPendencia"></a>
                                    <script language="javascript" type="text/javascript">
                                        window.onload = function()
                                        {                                                
                                            document.getElementById("avancarPendencia").click();
                                        }
                                    </script>
                                    <?php
                                }                                
                                else if($despesasPendentes == 0)
                                {
                                    $proc = $processoModel->abrirTramitacao($currentProcess);                                    
                                    if($proc)
                                    {
                                        $pcStatus = $proc->status_id;                                        
                                    }

                                    if($pcStatus >= 5)
                                    {
                                        echo "<script>alert('O processo já foi encaminhado para análise financeira!!!');</script>";
                                    }                                    
                                    else
                                    {
                                        $encFin = $processoModel->encaminharFin($currentProcess);
                                        
                                        if($encFin)
                                        {
                                            header('Location:pddePC.php?status=success');
                                        }  
                                    }
                                }                                
                            }
                            ?>
                            <div class="modal fade modal-trigger" id="avancarPend" tabindex="-1" aria-labelledby="avancarPendLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2 class="modal-title fs-5" id="avancarPendLabel">Atenção!</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="content-fluid">
                                                <span>Existe(m) <?= $despesasPendentes ?> pendência(s). <br>Deseja realmente avançar para análise financeira?</span>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-success" onclick="location.href='?forceFin=true'">SIM</button>
                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">NÃO</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php

                            if(isset($_REQUEST['forceFin']) && $_REQUEST['forceFin'] == true)
                            {
                                if($currentStatus == 4)
                                {
                                   $encFin = $processoModel->encaminharFin($currentProcess);                                        
                                    if($encFin)
                                    {
                                        header('Location:pddePC.php?status=success');
                                    }                                    
                                }
                                else
                                {                                    
                                    header('Location:pddePC.php?status=error');
                                }
                            }

                            if(isset($_GET['delDesp']) && $_GET['delDesp'] == true){
                                if($currentStatus >=5)
                                {
                                    $_SESSION['nav'] = array("","","active","","");
                                    $_SESSION['navShow'] = array("","","show active","","");
                                    $_SESSION['sel'] = array("false","false","true","false","false");                              
                                    echo "<script>alert('Não foi possível excluir a despesa. O processo já foi encaminhado para análise financeira.');</script>";
                                }
                                else
                                {
                                    $idDesp = $_GET['idDesp'];
                                    $delete = $despesaModel->delete($idDesp);
                                    if($delete)
                                    {
                                        $acao = "Deletou a depesa de id " . $idDesp;
                                        $log = $logModel->save([
                                        'usuario' => $_SESSION['matricula'],
                                        'acao' => $acao
                                        ]);
                                    }
                                    $_SESSION['nav'] = array("","","active","","");
                                    $_SESSION['navShow'] = array("","","show active","","");
                                    $_SESSION['sel'] = array("false","false","true","false","false");
                                    header('Location:pddePC.php?status=despesaDeletada');                        
                                }
                            }
                        ?>
                    </div>
                    
                    <?php
                    if(isset($_REQUEST['include']) && $_REQUEST['include'] == true)
                    {          
                        $_SESSION['nav'] = array("","","active","","");
                        $_SESSION['navShow'] = array("","","show active","","");
                        $_SESSION['sel'] = array("false","false","true","false","false");           
                        
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                        {
                            $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                            
                            if($novaDespesa = $despesaModel->save($postData, $currentProcess, $currentUser))
                            {
                                $acao = "Inseriu nova despesa no processo de id " . $currentProcess;
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                    ]);
                                header('Location:pddePC.php?status=success');
                            }
                            else
                            {
                                echo '<script>alert("ERRO!!!!")</script>';
                            }
                        }
                    }
                    
                    if(isset($_REQUEST['update']) && $_REQUEST['update'] == true)
                    {   
                        $_SESSION['nav'] = array("","","active","","");
                        $_SESSION['navShow'] = array("","","show active","","");
                        $_SESSION['sel'] = array("false","false","true","false","false");           
                       
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                        {
                            $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        
                            if($atualizarDespesa = $despesaModel->save($postData, $currentProcess, $currentUser))
                            {
                                $acao = "Atualizou a despesa no processo de id " . $postData['idDespM'];
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                    ]);
                                header('Location:pddePC.php?status=success');
                            }
                            else
                            {
                                echo '<script>alert("ERRO!!!!")</script>';
                            }
                        }                        
                        else
                        {
                            echo '<script>alert("ERRO!!!!")</script>';
                        }
                    }

                    if(isset($_GET['editDesp']) && $_GET['editDesp'] == true){
                        $idDesp = $_GET['idDesp'];
                        
                        $_SESSION['nav'] = array("","","active","","");
                        $_SESSION['navShow'] = array("","","show active","","");
                        $_SESSION['sel'] = array("false","false","true","false","false");
                        
                        $desp = $despesaModel->findById($idDesp);                        
                        
                        if($desp)
                        {
                            $idDespM = $desp->id;
                            $idAcaoM = $desp->acao_id;
                            $categoriaM = $desp->categoria;
                            $fornecedorM = $desp->fornecedor;
                            $cnpjFornM = $desp->cnpj_forn;
                            $descricaoM = $desp->descricao;
                            $numDocM = $desp->documento;
                            $numPgtoM = $desp->pagamento;
                            $dataDespM = $desp->data_desp;
                            $valorM = $desp->valor;
                            $progM = $desp->check_prog;
                            $ataM = $desp->check_ata;
                            $enqM = $desp->check_enq;
                            $consM = $desp->check_cons;
                                                    
                            $checkProgM = $progM == 1 ? "checked" : "";
                            $checkAtaM = $ataM == 1 ? "checked" : "";
                            $checkEnqM = $enqM == 1 ? "checked" : "";
                            $checkConsM = $consM == 1 ? "checked" : "";

                            $valorReal = 'R$ ' . number_format($valorM, 2, ",", ".");                                                
                        }
                                                    
                        ?>
                        <a data-bs-toggle="modal" data-bs-target="#despesaModal" id="modalDespesa"></a>
                        <script language="javascript" type="text/javascript">
                            window.onload = function()
                            {                                                
                                document.getElementById("modalDespesa").click();
                            }
                        </script>

                        <?php  
                        
                        $action = "?update=true";
                        $titulo = "Atualizar Despesa";
                        $botao = '<input type="submit" class="btn btn-warning" value="Atualizar"/>';                
                    } 
                    else 
                    {
                        $action = "?include=true";
                        $titulo = "Nova Despesa";
                        $botao = '<input type="submit" class="btn btn-success" value="Incluir"/>';                
                    }            
                    ?>            
                    <!-- Modal Despesa -->
                    <div class="modal fade modal-trigger" id="despesaModal" tabindex="-1" aria-labelledby="despesaModalLabel" aria-hidden="true">                
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form action="<?= $action; ?>" method="post" name="despesa">
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5" id="despesaModalLabel"><?= $titulo; ?></h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        <input type="hidden" value="<?= $idDespM ?? ''; ?>" name="idDespM" />
                                    </div>
                                    <div class="modal-body">
                                        <div class="content-fluid">                                    
                                            <div class="row">
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <label class="input-group-text col-4" for="inputGroup-acao">Ação</label>
                                                        <select name="acaoId" class="form-select w-50 col-8" id="inputGroup-acao" required>
                                                            <option <?= isset($idAcaoM) && $idAcaoM != null ? '' : 'selected'; ?> disabled="disabled">Selecione...</option>
                                                            <?php
                                                            $progs = $programaModel->findByProgName($tipo);                                                            
                                                            if($progs)
                                                            {
                                                                foreach($progs as $prog):                                                                
                                                                    $idAcao = $prog->id;                                                                                
                                                                    $acao = $prog->acao; 
                                                                    if(isset($idAcaoM) && $idAcaoM == $idAcao){
                                                                        echo '<option value="' . $idAcao . '" selected>' . $acao . '</option>';
                                                                    } else {
                                                                        echo '<option value="' . $idAcao . '">' . $acao . '</option>';
                                                                    }
                                                                endforeach;
                                                            }
                                                            ?>                                                                        
                                                        </select>                                                            
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <label class="input-group-text col-4" for="inputGroup-categDesp">Categoria</label>
                                                        <select name="categoria" class="form-select w-50 col-8" id="inputGroup-categDesp" required>
                                                            <option <?= isset($categoriaM) && $categoriaM != null ? '' : 'selected'; ?> disabled="disabled">Selecione...</option>                                                    
                                                            <option value="C" <?= isset($categoriaM) && $categoriaM == 'C' ? 'selected' : ''; ?>>Custeio</option>
                                                            <option value="K" <?= isset($categoriaM) && $categoriaM == 'K' ? 'selected' : ''; ?>>Capital</option>                                                                
                                                        </select>                                                            
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-7">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-fornecedor">Fornecedor</span>
                                                        <input type="text" name="fornecedor" class="col-8 form-control" value="<?= $fornecedorM ?? ''; ?>" aria-describedby="inputGroup-fornecedor" />
                                                    </div>
                                                </div>
                                                <div class="col-5">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-cnpjForn">CNPJ</span>
                                                        <input type="text" name="cnpjForn" class="col-8 form-control" value="<?= $cnpjFornM ?? ''; ?>" aria-describedby="inputGroup-cnpjForn" maxlength="14" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="input-group input-group-sm mb-2">
                                                <span class="input-group-text col-2" id="inputGroup-descDesp">Aquisição</span>
                                                <input type="text" name="descDesp" class="col-10 form-control" value="<?= $descricaoM ?? ''; ?>" aria-describedby="inputGroup-descDesp" />
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-numDoc">Nº Documento</span>
                                                        <input type="text" name="numDoc" class="col-8 form-control" value="<?= $numDocM ?? ''; ?>"aria-describedby="inputGroup-numDoc" />
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-numPgto">Ident. Pagamento</span>
                                                        <input type="text" name="numPgto" class="col-8 form-control" value="<?= $numPgtoM ?? ''; ?>" aria-describedby="inputGroup-numPgto" />
                                                    </div>                                                  
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-dataDoc">Data</span>
                                                        <input type="date" name="dataDoc" class="col-8 form-control" value="<?= $dataDespM ?? ''; ?>" aria-describedby="inputGroup-dataDoc" />
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-valDesp">Valor da Despesa</span>
                                                        <input type="text" name="valDesp" class="col-8 form-control" value="<?= $valorReal  ?? ''; ?>" aria-describedby="inputGroup-valDesp" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" name="checkProg" class="form-check-input" value="1" role="switch" id="checkProg" <?= $checkProgM ?? '';?>>
                                                        <label class="form-check-label" for="checkProg">De Acordo com o Programa?</label>
                                                    </div>
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" name="checkEnquad" class="form-check-input" value="1" role="switch" id="checkEnquad" <?= $checkEnqM ?? '';?>>
                                                        <label class="form-check-label" for="checkEnquad">Enquadramento Correto?</label>
                                                    </div>
                                                </div>
                                                <div class="col">                                                            
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" name="checkAta" class="form-check-input" value="1" role="switch" id="checkAta" <?= $checkAtaM ?? '';?>>
                                                        <label class="form-check-label" for="checkAta">Possui Ata de deliberação?</label>
                                                    </div>
                                                    <div class="form-check form-switch mb-2">
                                                        <input type="checkbox" name="checkConso" class="form-check-input" value="1" role="switch" id="checkConso" <?= $checkConsM ?? '';?>>
                                                        <label class="form-check-label" for="checkConso">Possui Consolidação de Pesquisa de Preços?</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                                                    
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                                        <?= $botao; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ANÁLISE FINANCEIRA -->
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][3]; ?>" id="nav-finan" role="tabpanel" aria-labelledby="nav-finan-tab" tabindex="0">
                        <div class="container-fluid">                    
                            <br />
                            <form action="?saveFin=true" method="post">
                                <div class="row">
                                    <div class="col">
                                        <h6>Análise Financeira</h6>
                                        <br />
                                        <?php
                                        
                                        $proc = $processoModel->abrirTramitacao($currentProcess);
                                        
                                        if(!empty($proc))
                                        {
                                            $userFinId = $proc->usuario_fin_id;
                                            $dataAnaliseFin = $proc->data_analise_fin;
                                            $dataSigpc = $proc->data_sigpc;
                                            $finObs = $proc->obs_analise_fin;
                                            $emailAf = $proc->email_af;
                                            
                                            //var_dump($proc);
                                            //exit();
                                            if(!empty($userFinId))
                                            {
                                                $user = $userModel->findById($userFinId);
                                                if($user)
                                                {
                                                    $userFin = $user->nome;
                                                }
                                                else
                                                {
                                                    $userFin = null;
                                                }
                                            }
                                        }
                                        

                                        if(isset($dataAnaliseFin) && $dataAnaliseFin != null)
                                        {
                                            $dataAnaliseFin = new DateTime($dataAnaliseFin, $timezone);
                                            $dataAnaliseFin = $dataAnaliseFin->format("d/m/Y");
                                        }

                                        if(isset($dataSigpc) && $dataSigpc != null)
                                        {
                                            $dataSigpc = new DateTime($dataSigpc, $timezone);
                                            $dataSigpc = $dataSigpc->format("d/m/Y");
                                        }

                                        if(isset($emailAf) && $emailAf == "1"){
                                            $checkEmailFin = "checked";                    
                                        }

                                        ?>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-usuarioF">Responsável</span>
                                            <input type="text" name="userFin" class="col-9 form-control" value="<?= $userFin ?? '' ?>" aria-describedby="inputGroup-usuario" readonly/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-dataAnalF">Data da Análise</span>
                                            <input type="text" name="dataAnalFin" class="col-9 form-control" value="<?= $dataAnaliseFin ?? '' ?>"aria-describedby="inputGroup-dataAnal" />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-dataSigpc">Data SIGPC</span>
                                            <input type="text" name="dataSigpc" class="col-9 form-control" value="<?= $dataSigpc ?? '' ?>"aria-describedby="inputGroup-dataSigpc" />
                                        </div>
                                        <br /><br />
                                        <div class="form-check form-switch">
                                            <input type="checkbox" name="checkEmailFin" class="form-check-input" value="1" role="switch" id="checkEmailFin" <?= $checkEmailFin ?? '' ?>>
                                            <label class="form-check-label" for="checkEmailFin">Encaminhado E-mail com Análise Financeira</label>
                                        </div>                         
                                    </div>
                                    <div class="col">
                                        <h6>Observações</h6>
                                        <div class="form-floating">
                                            <textarea name="finObs" class="form-control" placeholder="" id="finObs" style="height: 120px"><?= $finObs ?? '' ?></textarea>
                                            <label for="finObs"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col text-end">                                
                                        <input type="submit" class="btn btn-warning" value="Atualizar Status" />
                                        <button type="button" class="btn btn-primary" onclick="location.href='?registrarSIGPC=true'">Registrar lançamento no SIGPC</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <?php
                    if(isset($_REQUEST['saveFin']) && $_REQUEST['saveFin'] == true)
                    {                        
                        if($currentStatus < 5)
                        {                            
                            echo "<script>alert('ERRO! O status do processo não está disponível para Análise Financeira!');</script>";
                        }
                        else
                        {
                            $_SESSION['nav'] = array("","","","active","");
                            $_SESSION['navShow'] = array("","","","show active","");
                            $_SESSION['sel'] = array("false","false","false","true","false");
                            
                            if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                            {
                                $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                            
                                if($atualizaFin = $processoModel->atualizarFinan($postData, $currentProcess))
                                {
                                    $acao = "Atualizou o status da análise financeira do processo de " . $currentProcess;
                                    $log = $logModel->save([
                                        'usuario' => $_SESSION['matricula'],
                                        'acao' => $acao
                                        ]);
                                    header('Location:pddePC.php?status=success');
                                }
                                else
                                {
                                    echo '<script>alert("ERRO!!!!")</script>';
                                }
                            }                        
                            else
                            {
                                echo '<script>alert("ERRO!!!!")</script>';
                            }                              
                        }              
                    }

                    if(isset($_REQUEST['registrarSIGPC']) && $_REQUEST['registrarSIGPC'] == true)
                    {                        
                        if($currentStatus < 6)
                        {
                            echo "<script>alert('A Análise Financeira da Prestação de Contas ainda não foi concluída');</script>";
                        }
                        else if($currentStatus == 7)
                        {
                            echo "<script>alert('A Prestação de Contas já foi concluída');</script>";
                        }
                        else
                        {                               
                            $reg = $processoModel->registrarSIGPC($currentProcess);
                            if($reg)
                            {
                                $_SESSION['nav'] = array("","","","active","");
                                $_SESSION['navShow'] = array("","","","show active","");
                                $_SESSION['sel'] = array("false","false","false","true","false");                         
                                header('Location:pddePC.php?status=success');
                            }
                        }
                    }

                    ?>
                    <!-- PENDÊNCIAS -->
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][4]; ?>" id="nav-pendencia" role="tabpanel" aria-labelledby="nav-pendencia-tab" tabindex="0">
                        <div class="container-fluid">
                            <br />
                            <div class="row my-auto">                        
                                <button type="button" class="col-2 btn btn-primary mx-2" data-bs-toggle="modal" data-bs-target="#pendenciaModal">+ Nova Pendência</button>                        
                                <a href="emailPendencias.php?idProc=<?= $currentProcess ?>" target="_blank" class="col-2 mx-2"><button type="button" class="col-12 btn btn-success">Escrever E-mail</button></a>                        
                                <div class="col-6 text-center mx-2 fw-semibold">Histórico de Pendências</div>                                        
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col">
                                    <table class="table table-sm table-striped table-hover m-auto">
                                        <thead>
                                            <tr class="text-center align-middle">
                                                <th class="col w-auto fw-semibold">Data</th>
                                                <th class="col w-auto fw-semibold">Item do DRD</th>
                                                <th class="col w-auto fw-semibold">Documento</th>
                                                <th class="col w-auto fw-semibold">Favorecido</th>
                                                <th class="col w-auto fw-semibold">Nº Doc</th>
                                                <th class="col w-auto fw-semibold">Data de Emissão</th>
                                                <th class="col w-auto fw-semibold">Pendência</th>
                                                <th class="col w-auto fw-semibold">Providências</th>
                                                <th class="col w-auto fw-semibold">Etapa</th>                                        
                                                <th class="col w-auto fw-semibold">Data Regularização</th>
                                                <th class="col w-auto fw-semibold">Regularizado?</th>
                                                <th class="col w-auto fw-semibold">Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $pends = $pendenciaModel->findByProcId($currentProcess);
                                            if($pends)
                                            {
                                                foreach($pends as $pend):                                                
                                                    $idPend = $pend->id;
                                                    $dataPend = $pend->dataPend;
                                                    $itemDRD = $pend->itemDRD;
                                                    $idDocPend = $pend->docPend_id;        
                                                    $favorecido = $pend->favorecido;
                                                    $dataDocPend = $pend->dataDocPend;
                                                    $numDocPend = $pend->numDocPend;
                                                    $idPendencia = $pend->pend_id;
                                                    $providencias = $pend->providencias;
                                                    $etapaId = $pend->etapa_id;
                                                    $checkResolved = $pend->resolvido;
                                                    $dataResolved = $pend->dataResolvido;
                                                    $pendAtiva = $pend->ativado;

                                                    $documento = $documentoModel->findById($idDocPend)->documento;
                                                    $pendencia = $pendenciaModel->findTipoById($idPendencia)->pendencia;

                                                    $dataPend = new DateTime($dataPend,$timezone);
                                                    $dataPend = $dataPend->format('d/m/Y');

                                                    $dataDocPend = new DateTime($dataDocPend,$timezone);
                                                    $dataDocPend = $dataDocPend->format('d/m/Y');

                                                    if($dataResolved != ""){
                                                        $dataResolved = new DateTime($dataResolved,$timezone);
                                                        $dataResolved = $dataResolved->format('d/m/Y');
                                                    }

                                                    switch($etapaId)
                                                    {
                                                        case 1:
                                                            $etapa = "Juntada";
                                                            break;
                                                        case 2:
                                                            $etapa = "Execução";
                                                            break;
                                                        case 3:
                                                            $etapa = "Financeira";
                                                            break;
                                                    }
                                                    if($pendAtiva == 1){
                                                        echo '<tr class="align-middle fw-lighter">';
                                                        echo '<td class="col text-center">' . $dataPend . '</td>';
                                                        echo '<td class="col text-center">' . $itemDRD . '</td>';
                                                        echo '<td class="col">' . $documento . '</td>';
                                                        echo '<td class="col">' . $favorecido . '</td>';
                                                        echo '<td class="col">' . $numDocPend . '</td>';
                                                        echo '<td class="col text-center">' . $dataDocPend . '</td>';
                                                        echo '<td class="col">' . $pendencia . '</td>';
                                                        echo '<td class="col">' . $providencias . '</td>';
                                                        echo '<td class="col">' . $etapa . '</td>';
                                                        echo '<td class="col text-center">' . $dataResolved . '</td>';
                                                        if($checkResolved == 0)
                                                        {
                                                            echo '<td class="col text-center"><button type="button" class="btn btn-success" onclick="location.href=\'?reg=true&idPend=' . $idPend . '\'")">Marcar</button></td>';
                                                            echo '<td class="col">';
                                                            echo '<a href="?editPend=true&idPend=' . $idPend . '" ><img src="img/pencil-alt.svg" alt="Editar" title="Editar"/></a><br/>';
                                                            echo '<a href="?delPend=true&idPend=' . $idPend . '" ><img src="img/na.svg" alt="Excluir" title="Excluir"/></a>';
                                                            echo '</td>';
                                                        }
                                                        else
                                                        {
                                                            echo '<td class="col text-center">SIM</td>';
                                                            echo '<td class="col">';                                                
                                                            echo '</td>';
                                                        }
                                                        echo '</tr>';                                            
                                                    }
                                                endforeach;
                                            }
                                            ?>                        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    
                    if(isset($_GET['reg']) && $_GET['reg'] == true)
                    {
                        $_SESSION['nav'] = array("","","","","active");
                        $_SESSION['navShow'] = array("","","","","show active");
                        $_SESSION['sel'] = array("false","false","false","false","true");

                        $idPend = $_GET['idPend'];
                        $regPend = $pendenciaModel->regularizarPendencia($idPend);                        
                        if($regPend)
                        {    
                            $acao = "Regularizou a pendência de id " . $idPend;
                            $log = $logModel->save([
                                'usuario' => $_SESSION['matricula'],
                                'acao' => $acao
                                ]);
                            header('Location:pddePC.php?status=success');
                        }
                        else
                        {
                            echo '<script>alert("ERRO!!!!")</script>';
                        }
                    }
                    if(isset($_GET['delPend']) && $_GET['delPend'] == true)
                    {
                        $_SESSION['nav'] = array("","","","","active");
                        $_SESSION['navShow'] = array("","","","","show active");
                        $_SESSION['sel'] = array("false","false","false","false","true");

                        $idPend = $_GET['idPend'];
                        $delPend = $pendenciaModel->deletarPendencia($idPend, $currentUser);
                        if($delPend)
                        {
                            $acao = "Deletou a pendência de id " . $idPend;
                            $log = $logModel->save([
                                'usuario' => $_SESSION['matricula'],
                                'acao' => $acao
                                ]);          
                            header('Location:pddePC.php?status=success');
                        }
                        else
                        {
                            echo '<script>alert("ERRO!!!!")</script>';
                        }                    
                    }

                    if(isset($_REQUEST['newPend']) && $_REQUEST['newPend'] == true)
                    {                        
                        $_SESSION['nav'] = array("","","","","active");
                        $_SESSION['navShow'] = array("","","","","show active");
                        $_SESSION['sel'] = array("false","false","false","false","true");        
                        
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                        {
                            $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        
                            if($savePend = $pendenciaModel->save($postData, $currentProcess, $currentUser))
                            {
                                $acao = "Inseriu nova pendência no processo de id " . $currentProcess;
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                    ]);
                                header('Location:pddePC.php?status=success');
                            }
                            else
                            {
                                echo '<script>alert("ERRO!!!!")</script>';
                            }
                        }                        
                        else
                        {
                            echo '<script>alert("ERRO!!!!")</script>';
                        }                       
                    }
                    
                    if(isset($_REQUEST['updatePend']) && $_REQUEST['updatePend'] == true)
                    {   
                        $_SESSION['nav'] = array("","","","","active");
                        $_SESSION['navShow'] = array("","","","","show active");
                        $_SESSION['sel'] = array("false","false","false","false","true");           
                        
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                        {
                            $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        
                            if($savePend = $pendenciaModel->save($postData, $currentProcess, $currentUser))
                            {
                                $acao = "Atualizou a pendência de " . $postData['idPendM'];
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                    ]);
                                header('Location:pddePC.php?status=success');
                            }
                            else
                            {
                                echo '<script>alert("ERRO!!!!")</script>';
                            }
                        }                        
                        else
                        {
                            echo '<script>alert("ERRO!!!!")</script>';
                        }
                    }

                    if(isset($_GET['editPend']) && $_GET['editPend'] == true){
                        
                        $_SESSION['nav'] = array("","","","","active");
                        $_SESSION['navShow'] = array("","","","","show active");
                        $_SESSION['sel'] = array("false","false","false","false","true");
                        
                        $idPend = $_GET['idPend'];
                        $pend = $pendenciaModel->findById($idPend);
                        if($pend)
                        {
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
                                                    
                        ?>
                        <a data-bs-toggle="modal" data-bs-target="#pendenciaModal" id="modalPendencia"></a>
                        <script language="javascript" type="text/javascript">
                            window.onload = function()
                            {                                                
                                document.getElementById("modalPendencia").click();
                            }
                        </script>

                        <?php  
                        
                        $actionP = "?updatePend=true";
                        $tituloP = "Atualizar Pendência";
                        $botaoP = '<input type="submit" class="btn btn-warning" value="Atualizar"/>';                
                    } 
                    else 
                    {
                        $actionP = "?newPend=true";
                        $tituloP = "Nova Pendência";
                        $botaoP = '<input type="submit" class="btn btn-success" value="Incluir"/>';                
                    }      
                    ?>
                    
                    <!-- Modal Pendências -->
                    <div class="modal fade" id="pendenciaModal" tabindex="-1" aria-labelledby="pendenciaModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form action="<?= $actionP ?>" method="post" name="pendencia">
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5" id="pendenciaModalLabel"><?= $tituloP ?></h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        <input type="hidden" value="<?= $idPendM ?? ''; ?>" name="idPendM" />
                                    </div>
                                    <div class="modal-body">
                                        <div class="content-fluid">
                                            <div class="row">                                        
                                                <div class="col-3">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-6" id="inputGroup-itemDRD">Item DRD</span>
                                                        <input type="text" name="itemDRD" value="<?= $iDRDM ?? ''; ?>" class="col-6 form-control" aria-describedby="inputGroup-itemDRD" />
                                                    </div>
                                                </div>
                                                <div class="col-9">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <label class="input-group-text col-3" for="inputGroup-docPend">Documento</label>
                                                        <select name="docPend" class="form-select col-9" id="inputGroup-docPend" required>                                                    
                                                            <option disabled <?= isset($docPendIdM) && $docPendIdM != null ? '' : 'selected'; ?>>Selecione...</option>
                                                            <?php
                                                            $docs = $documentoModel->all();
                                                            if($docs)
                                                            {
                                                                foreach($docs as $doc):
                                                                
                                                                    $idDoc = $doc->id;                                                                                
                                                                    $docPend = $doc->documento;
                                                                    $pdde = $doc->pdde;
                                                                    if($pdde == 1){
                                                                        if(isset($docPendIdM) && $docPendIdM == $idDoc){
                                                                            echo '<option value="' . $idDoc . '" selected>' . $docPend . '</option>';
                                                                        } else {  
                                                                            echo '<option value="' . $idDoc . '">' . $docPend . '</option>';
                                                                        }
                                                                    }                                                                    
                                                                endforeach;
                                                            }
                                                            ?>                                                     
                                                        </select>                                                            
                                                    </div>
                                                </div>
                                            </div>                                    
                                            <div class="row">                                        
                                                <div class="input-group input-group-sm mb-2">
                                                    <span class="input-group-text col-2" id="inputGroup-favorecido">Favorecido</span>
                                                    <input type="text" name="favorecido" value="<?= $favorecidoM ?? ''; ?>" class="col-10 form-control" aria-describedby="inputGroup-favorecido" />
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-dataDocP">Data Documento</span>
                                                        <input type="date" name="dataDocP" value="<?= $dataDocPendM ?? ''; ?>" class="col-8 form-control" aria-describedby="inputGroup-dataDocP" />
                                                    </div>                                                  
                                                </div>
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-numDocP">Nº Documento</span>
                                                        <input type="text" name="numDocP" value="<?= $numDocPendM ?? ''; ?>" class="col-8 form-control" aria-describedby="inputGroup-numDocP" />
                                                    </div>
                                                </div>                                        
                                            </div>
                                            <div class="row">                                        
                                                <div class="input-group input-group-sm mb-2">
                                                    <label class="input-group-text col-2" for="inputGroup-pendencia">Pendência</label>
                                                    <select name="pendencia" class="form-select col-10" id="inputGroup-pendencia">
                                                        <option disabled <?= isset($pendIdM) && $pendIdM != null ? '' : 'selected'; ?>>Selecione...</option>
                                                        <?php
                                                        $tipos = $pendenciaModel->allTipos();
                                                        if($tipos)
                                                        {
                                                            foreach($tipos as $tipo)
                                                            {
                                                                $idTipoPend = $tipo->id;
                                                                $tipoPend = $tipo->pendencia;
                                                                if(isset($pendIdM) && $pendIdM == $idTipoPend){
                                                                    echo '<option value="' . $idTipoPend . '" selected>' . $tipoPend . '</option>';
                                                                } else {
                                                                    echo '<option value="' . $idTipoPend . '">' . $tipoPend . '</option>';
                                                                }                                                        
                                                            }
                                                        }
                                                        ?>                                                                                                  
                                                    </select>                                                            
                                                </div>
                                            </div>
                                            <div class="row">                                        
                                                <div class="input-group input-group-sm mb-2">
                                                    <span class="input-group-text col-2" id="inputGroup-providencias">Providências</span>
                                                    <textarea name="providencias" class="col-10 form-control" aria-describedby="inputGroup-providencias" rows="3" maxlength="1024"><?= $providenciasM ?? ''; ?></textarea>
                                                </div>
                                            </div>                                 
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
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                                        <?= $botaoP ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Fim Modal Pendências -->            
                </div>        
            </div>
            
            <!-- Fim do Conteúdo  -->
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>
    
    <script src="./js/script.js"></script>
    <script src="./js/bootstrap/bootstrap.bundle.min.js"></script>    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>    
</body>
</html>