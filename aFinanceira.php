<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Database\Connect;

$pdo = Connect::getInstance();

$timezone = new DateTimeZone("America/Sao_Paulo");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    <?php 
        $stmt = $pdo->prepare("SELECT p.tipo, i.instituicao, i.cnpj FROM processos p JOIN instituicoes i ON i.id = p.instituicao_id WHERE p.id = :idProc");
        $stmt->bindParam("idProc", $_GET['idProc']);
        $stmt->execute();
        if($proc = $stmt->fetch()){
            $programa = $proc->tipo;
            $instituicao = $proc->instituicao;
            $cnpj = $proc->cnpj;
            $cnpj = substr($cnpj,0,2) . "." . substr($cnpj,2,3) . "." . substr($cnpj,5,3) . "/" . substr($cnpj,8,4) . "-" . substr($cnpj,12,2);
        }
    ?>

    <title><?= "Análise Financeira - " . $programa . " - " . $instituicao ?></title>
    
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
        body{
            font-size: 12px;
        }
        table{
            font-size: 11px;
        }
        .cabecalho{
            font-size: 11px;
            line-height: 13px;
        }
        .assinatura{
            line-height: 14px;
        }
        .tabela{
            line-height: 12px;
        }
        @media print {
            .pagebreak {
            clear: both;
            page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">    
        <div class="text-center">
            <img src="img/brasao.png" class="mx-auto" width="70px" alt="brasao" />
        </div>    
        <div class="row text-center cabecalho">        
            <span><b>MUNICÍPIO DE SÃO BERNARDO DO CAMPO</b><br>
            Secretaria de Educação<br>
            Departamento de Gestão de Ajustes, Pessoas e Sistemas - SE-3<br>
            Divisão de Gestão e Controle de Ajustes - SE-33<br>
            Seção de Gestão de Ajustes - SE-331</span><hr>    
        </div>
        
        <div class="row text-center">
            <span><b>ANÁLISE DE PRESTAÇÃO DE CONTAS DO PROGRAMA DINHEIRO DIRETO NA ESCOLA - <?= mb_strtoupper($programa) ?></b></span><br>
            <span>Período: 01/01/2025 a 31/12/2025</span>
        </div>
        <div class="row">
            <div class="col-4 text-end">
                <span>Unidade Executora:</span>
            </div>
            <div class="col-8">
                <span><b><?= $instituicao ?></b></span>
            </div>            
        </div>
        <div class="row">
            <div class="col-4 text-end">
                <span>CNPJ:</span>
            </div>
            <div class="col-8">
                <span><b><?= $cnpj ?></b></span>
            </div>            
        </div>
        <br>
        <table class="table table-sm table-striped table-light table-bordered tabela table-responsive">
            <thead>
                <?php
                $sql = $pdo->prepare("SELECT COUNT(*) AS contagem FROM saldo_pdde WHERE proc_id = :idProc");
                $sql->bindParam("idProc", $_GET['idProc']);
                $sql->execute();
                if($count = $sql->fetch())
                {
                    $contCol = $count->contagem;
                }
                $colTitulo = 4 + $contCol;
                $colFoot = 3 + $contCol;
                ?>
                <tr class="text-center align-middle"><th colspan="<?= $colTitulo ?>">ANÁLISE FINANCEIRA</th></tr>
                <tr class="text-center align-middle">
                    <th style="min-width: 75px; max-width: 90px;" scope="col" class="col">DESCRIÇÃO</th>
                    <?php
                    $arrayAf = array();
                    $sql = $pdo->prepare("SELECT s.acao_id, p.acao, s.categoria, s.saldo4, s.rp25, s.rent25, s.devl25, s.saldo25 FROM saldo_pdde s JOIN programaspdde p ON s.acao_id = p.id WHERE proc_id = :idProc ORDER BY s.acao_id");
                    $sql->bindParam("idProc", $_GET['idProc']);
                    $sql->execute();
                    while($af = $sql->fetch())
                    {
                        $acaoId = $af->acao_id;
                        $acao = $af->acao;
                        $categoria = $af->categoria;
                        $saldo4 = $af->saldo4;
                        $rp25 = $af->rp25;
                        $rent25 = $af->rent25;
                        $devl25 = $af->devl25;
                        $saldo25 = $af->saldo25;                        

                        $stmt = $pdo->prepare("SELECT SUM(valor) AS despesa, SUM(valor_gl) AS glosa FROM pdde_despesas_25 WHERE acao_id = :idAcao AND proc_id = :idProc AND categoria = :cat");
                        $stmt->bindParam('idAcao',$acaoId);
                        $stmt->bindParam('idProc',$_GET['idProc']);
                        $stmt->bindParam('cat',$categoria);
                        if($stmt->execute())
                        {
                            if($value = $stmt->fetch())
                            {
                                $despesa = $value->despesa;
                                $glosa = $value->glosa;
                            }
                        }
                        if($categoria == "C")
                        {
                            $stmt = $pdo->prepare("SELECT SUM(custeio) AS repasse FROM repasse_25 WHERE acao_id = :idAcao AND proc_id = :idProc");
                            $stmt->bindParam('idAcao',$acaoId);
                            $stmt->bindParam('idProc',$_GET['idProc']);                                            
                            if($stmt->execute())
                            {
                                if($value = $stmt->fetch())
                                {
                                    $repasse = $value->repasse;                                                    
                                }
                            }
                        }
                        else if($categoria == "K")
                        {
                            $stmt = $pdo->prepare("SELECT SUM(capital) AS repasse FROM repasse_25 WHERE acao_id = :idAcao AND proc_id = :idProc");
                            $stmt->bindParam('idAcao',$acaoId);
                            $stmt->bindParam('idProc',$_GET['idProc']);                                            
                            if($stmt->execute())
                            {
                                if($value = $stmt->fetch())
                                {
                                    $repasse = $value->repasse;                                                    
                                }
                            }
                        }
                        $currAf = array(
                            "acao" => $acao,
                            "categoria" => $categoria,
                            "saldoInicial" => $saldo24,
                            "repasse" => $repasse,
                            "rp" => $rp25,
                            "rent" => $rent25,
                            "devol" => $devl25,
                            "despesa" => $despesa,                            
                            "glosa" => $glosa,
                            "saldoParcial" => $af->saldo25 - $glosa,
                            "saldoFinal" => $af->saldo25
                        );                        
                        array_push($arrayAf,$currAf);                        
                    }
                    
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<th style="min-width: 75px; max-width: 100px;" scope="col" class="col">CUSTEIO ' . mb_strtoupper($arrayAf[$i]['acao']) . '</th>';
                        }                        
                    }                    
                    ?>        
                    <th style="min-width: 75px; max-width: 100px;" scope="col" class="col">TOTAL CUSTEIO</th>
                    <?php
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<th style="min-width: 75px; max-width: 100px;" scope="col" class="col">CAPITAL ' . mb_strtoupper($arrayAf[$i]['acao']) . '</th>';
                        }                        
                    }                    
                    ?>
                    <th style="min-width: 75px; max-width: 100px;" scope="col" class="col">TOTAL CAPITAL</th>
                    <th style="min-width: 85px; max-width: 100px;" scope="col" class="col">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr class="align-middle">
                    <th>Saldo reprogramado do exercício anterior</th>
                    <?php
                    $cSiT = 0;
                    $kSiT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['saldoInicial'],"2", ",", ".") . '</td>';
                            $cSiT = $cSiT + $arrayAf[$i]['saldoInicial'];
                        }                                            
                    }
                    echo '<th class="text-center">R$ ' . number_format($cSiT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['saldoInicial'],"2", ",", ".") . '</td>';
                            $kSiT = $kSiT + $arrayAf[$i]['saldoInicial'];
                        }                                  
                    }
                    echo '<th class="text-center">R$ ' . number_format($kSiT,"2", ",", ".") . '</th>';
                    $siTotal = $cSiT + $kSiT;
                    echo '<th class="text-center">R$ ' . number_format($siTotal,"2", ",", ".") . '</th>';                    
                    ?>
                </tr>
                <tr class="align-middle">
                    <th>Ingresso no período</th>
                    <?php
                    $cRepT = 0;
                    $kRepT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['repasse'],"2", ",", ".") . '</td>';
                            $cRepT = $cRepT + $arrayAf[$i]['repasse'];
                        }                   
                    }
                    echo '<th class="text-center">R$ ' . number_format($cRepT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['repasse'],"2", ",", ".") . '</td>';
                            $kRepT = $kRepT + $arrayAf[$i]['repasse'];
                        }                  
                    }
                    echo '<th class="text-center">R$ ' . number_format($kRepT,"2", ",", ".") . '</th>';
                    $RepTotal = $cRepT + $kRepT;
                    echo '<th class="text-center">R$ ' . number_format($RepTotal,"2", ",", ".") . '</th>';                    
                    ?>
                </tr>
                <tr class="align-middle">
                    <th>Recursos próprios</th>
                    <?php
                    $cRproT = 0;
                    $kRproT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo '<td class="text-center">R$ ' . number_format($arrayAf[$i]['rp'],"2", ",", ".") . '</td>';
                            $cRproT = $cRproT + $arrayAf[$i]['rp'];
                        }               
                    }
                    echo '<th class="text-center">R$ ' . number_format($cRproT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['rp'],"2", ",", ".") . '</td>';
                            $kRproT = $kRproT + $arrayAf[$i]['rp'];
                        }               
                    }
                    echo '<th class="text-center">R$ ' . number_format($kRproT,"2", ",", ".") . '</th>';
                    $RproTotal = $cRproT + $kRproT;
                    echo '<th class="text-center">R$ ' . number_format($RproTotal,"2", ",", ".") . '</th>';                    
                    ?>
                </tr>
                <tr class="align-middle">
                    <th>Rentabilidade apurada</th>
                    <?php
                    $cRentT = 0;
                    $kRentT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['rent'],"2", ",", ".") . '</td>';
                            $cRentT = $cRentT + $arrayAf[$i]['rent'];
                        }                  
                    }
                    echo '<th class="text-center">R$ ' . number_format($cRentT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['rent'],"2", ",", ".") . '</td>';
                            $kRentT = $kRentT + $arrayAf[$i]['rent'];
                        }            
                    }
                    echo '<th class="text-center">R$ ' . number_format($kRentT,"2", ",", ".") . '</th>';
                    $RentTotal = $cRentT + $kRentT;
                    echo '<th class="text-center">R$ ' . number_format($RentTotal,"2", ",", ".") . '</th>';                    
                    ?>
                </tr>
                <tr class="align-middle">
                    <th>Devolução de recursos ao FNDE</th>
                    <?php
                    $cDevolT = 0;
                    $kDevolT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['devol'],"2", ",", ".") . '</td>';
                            $cDevolT = $cDevolT + $arrayAf[$i]['devol'];
                        }                   
                    }
                    echo '<th class="text-center">R$ ' . number_format($cDevolT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center"> R$ ' . number_format($arrayAf[$i]['devol'],"2", ",", ".") . '</td>';
                            $kDevolT = $kDevolT + $arrayAf[$i]['devol'];
                        }                   
                    }
                    echo '<th class="text-center">R$ ' . number_format($kDevolT,"2", ",", ".") . '</th>';
                    $DevolTotal = $cDevolT + $kDevolT;
                    echo '<th class="text-center">R$ ' . number_format($DevolTotal,"2", ",", ".") . '</th>';                    
                    ?>
                </tr>
                <tr class="align-middle">
                    <th>Valor total da receita</th>
                    <?php
                    $cRecT = 0;
                    $kRecT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            $cReceita = $arrayAf[$i]['saldoInicial'] + $arrayAf[$i]['repasse'] + $arrayAf[$i]['rp'] + $arrayAf[$i]['rent'] - $arrayAf[$i]['devol'];
                            echo   '<td class="text-center">R$ ' . number_format($cReceita,"2", ",", ".") . '</td>';
                            $cRecT = $cRecT + $cReceita;
                        }                     
                    }
                    echo '<th class="text-center">R$ ' . number_format($cRecT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            $kReceita = $arrayAf[$i]['saldoInicial'] + $arrayAf[$i]['repasse'] + $arrayAf[$i]['rp'] + $arrayAf[$i]['rent'] - $arrayAf[$i]['devol'];
                            echo   '<td class="text-center">R$ ' . number_format($kReceita,"2", ",", ".") . '</td>';
                            $kRecT = $kRecT + $kReceita;
                        }                  
                    }
                    echo '<th class="text-center">R$ ' . number_format($kRecT,"2", ",", ".") . '</th>';
                    $ReceitaTotal = $cRecT + $kRecT;
                    echo '<th class="text-center">R$ ' . number_format($ReceitaTotal,"2", ",", ".") . '</th>';                    
                    ?>                    
                </tr>
                <tr class="align-middle">
                    <th>Valor da despesa realizada</th>
                    <?php
                    $cDespT = 0;
                    $kDespT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['despesa'],"2", ",", ".") . '</td>';
                            $cDespT = $cDespT + $arrayAf[$i]['despesa'];
                        }                   
                    }
                    echo '<th class="text-center">R$ ' . number_format($cDespT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['despesa'],"2", ",", ".") . '</td>';
                            $kDespT = $kDespT + $arrayAf[$i]['despesa'];
                        }                  
                    }
                    echo '<th class="text-center">R$ ' . number_format($kDespT,"2", ",", ".") . '</th>';
                    $DespTotal = $cDespT + $kDespT;
                    echo '<th class="text-center">R$ ' . number_format($DespTotal,"2", ",", ".") . '</th>';                    
                    ?>
                </tr>
                <tr class="align-middle">
                    <th>Saldo a reprogramar para o exercício seguinte</th>
                    <?php
                    $cSpT = 0;
                    $kSpT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['saldoParcial'],"2", ",", ".") . '</td>';
                            $cSpT = $cSpT + $arrayAf[$i]['saldoParcial'];
                        }                      
                    }
                    echo '<th class="text-center">R$ ' . number_format($cSpT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['saldoParcial'],"2", ",", ".") . '</td>';
                            $kSpT = $kSpT + $arrayAf[$i]['saldoParcial'];
                        }
                    }
                    echo '<th class="text-center">R$ ' . number_format($kSpT,"2", ",", ".") . '</th>';
                    $SpTotal = $cSpT + $kSpT;
                    echo '<th class="text-center">R$ ' . number_format($SpTotal,"2", ",", ".") . '</th>';                    
                    ?>
                </tr>
                <tr class="align-middle">
                    <th>Valores não acatados (glosas)</th>
                    <?php
                    $cGlosaT = 0;
                    $kGlosaT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['glosa'],"2", ",", ".") . '</td>';
                            $cGlosaT = $cGlosaT + $arrayAf[$i]['glosa'];
                        }
                    }
                    echo '<th class="text-center">R$ ' . number_format($cGlosaT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['glosa'],"2", ",", ".") . '</td>';
                            $kGlosaT = $kGlosaT + $arrayAf[$i]['glosa'];
                        }
                    }
                    echo '<th class="text-center">R$ ' . number_format($kGlosaT,"2", ",", ".") . '</th>';
                    $GlosaTotal = $cGlosaT + $kGlosaT;
                    echo '<th class="text-center">R$ ' . number_format($GlosaTotal,"2", ",", ".") . '</th>';                    
                    ?>
                </tr>
                <tr class="align-middle">
                    <th>Saldo total do segmento a reprogramar para o exercício seguinte</th>
                    <?php
                    $cSfT = 0;
                    $kSfT = 0;
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "C"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['saldoFinal'],"2", ",", ".") . '</td>';
                            $cSfT = $cSfT + $arrayAf[$i]['saldoFinal'];
                        }
                    }
                    echo '<th class="text-center">R$ ' . number_format($cSfT,"2", ",", ".") . '</th>';
                    for($i = 0; $i < count($arrayAf); $i++){
                        if($arrayAf[$i]['categoria'] == "K"){
                            echo   '<td class="text-center">R$ ' . number_format($arrayAf[$i]['saldoFinal'],"2", ",", ".") . '</td>';
                            $kSfT = $kSfT + $arrayAf[$i]['saldoFinal'];
                        }
                    }
                    echo '<th class="text-center">R$ ' . number_format($kSfT,"2", ",", ".") . '</th>';
                    $SfTotal = $cSfT + $kSfT;
                    echo '<th class="text-center">R$ ' . number_format($SfTotal,"2", ",", ".") . '</th>';
                    ?>
                </tr>
            </tbody>
            <tfoot>
                <tr class="text-end align-middle">                    
                    <th colspan="<?= $colFoot ?>">SALDO TOTAL A REPROGRAMAR PARA O EXERCÍCIO SEGUINTE</th>
                    <th class="text-center">R$ <?= number_format($SfTotal,"2", ",", ".") ?></th>
                </tr>
            </tfoot>
        </table>

        <table class="table table-sm table-hover tabela">
            <thead>
                <tr class="text-center table-light"><th colspan="6">ANÁLISE BANCÁRIA</th></tr>
            </thead>
            <tbody>
                <tr class="text-center align-middle">
                    <th colspan="2">Saldo da conta corrente/poupança em 31/12/2025</th>
                    <?php
                    $stmt = $pdo->prepare("SELECT SUM(cc_2025) AS ccSF, SUM(pp_01_2025) AS pp01SF, SUM(pp_51_2025) AS pp51SF, SUM(spubl_2025) AS spublSF, SUM(bb_rf_cp_2025) AS bbrfSF FROM banco WHERE proc_id = :idProc");                                    
                    $stmt->bindParam('idProc',$_GET['idProc']);                                    
                    if($stmt->execute())
                    {
                        if($saldo = $stmt->fetch())
                        {
                            $ccSF = $saldo->ccSF;
                            $pp01SF = $saldo->pp01SF;
                            $pp51SF = $saldo->pp51SF;
                            $spublSF = $saldo->spublSF;
                            $bbrfSF = $saldo->bbrfSF;
                        }
                        else 
                        {
                            $ccSF = 0;
                            $pp01SF = 0;
                            $pp51SF = 0;
                            $spublSF = 0;
                            $bbrfSF = 0;
                        }
                    }
                    $bancoFinal = $ccSF + $pp01SF + $pp51SF + $spublSF + $bbrfSF;
                    ?>
                    <th>R$ <?= number_format($bancoFinal,"2", ",", ".") ?></th>
                </tr>
                <tr>
                    <th colspan="2">Total de pagamentos em trânsito</th>
                    <?php
                    $transito = 0;
                    $sql = $pdo->prepare("SELECT occ_id, valorOcc FROM conciliacao WHERE proc_id = :idProc");
                    $sql->bindParam("idProc", $_GET['idProc']);
                    $sql->execute();
                    while($occ = $sql->fetch())
                    {
                        $idOcc = $occ->occ_id;                        
                        $valorTst = $occ->valorOcc;
                        if($idOcc == 10) 
                        {                           
                            $transito = $transito + $valorTst;
                        }                        
                    }
                    if($transito != 0)
                    {
                        echo '<th class="text-center">R$ ' . number_format($transito,"2", ",", ".") . '</th>';
                    }
                    else
                    {
                        echo '<th class="text-center"></th>';
                    }
                    
                    ?>                    
                </tr>
                <tr><td colspan="3"></td></tr>
                <tr>                    
                    <th colspan="3">Valores a Débito</th>                    
                </tr>
                <?php
                $debOcc = 0;
                $sql = $pdo->prepare("SELECT occ_id, descricao, dataOcc, valorOcc FROM conciliacao WHERE proc_id = :idProc");
                $sql->bindParam("idProc", $_GET['idProc']);
                $sql->execute();
                while($occ = $sql->fetch())
                {
                    $idOcc = $occ->occ_id;
                    $descOcc = $occ->descricao;
                    $dataOcc = $occ->dataOcc;
                    $valorOcc = $occ->valorOcc;
                    if($idOcc <= 7) 
                    {
                        echo '<tr>';
                        echo '<td></td>';
                        echo '<td>' . $descOcc . '</td>';
                        echo '<td class="text-center">R$ ' . number_format($valorOcc,"2", ",", ".") . '</td>';
                        echo '</tr>';
                        $debOcc = $debOcc + $valorOcc;
                    }
                }
                
                ?>
                <tr><td colspan="3"></td></tr>
                <tr>                    
                    <th colspan="3">Valores a Crédito</th>
                </tr>
                <?php
                $credOcc = 0;
                $sql = $pdo->prepare("SELECT occ_id, descricao, dataOcc, valorOcc FROM conciliacao WHERE proc_id = :idProc");
                $sql->bindParam("idProc", $_GET['idProc']);
                $sql->execute();
                while($occ = $sql->fetch())
                {
                    $idOcc = $occ->occ_id;
                    $descOcc = $occ->descricao;
                    $dataOcc = $occ->dataOcc;
                    $valorOcc = $occ->valorOcc;
                    if($idOcc == 8 || $idOcc == 9 || $idOcc == 11 || $idOcc == 12) 
                    {
                        echo '<tr>';
                        echo '<td></td>';
                        echo '<td>' . $descOcc . '</td>';
                        echo '<td class="text-center">(R$ ' . number_format($valorOcc,"2", ",", ".") . ')</td>';
                        echo '</tr>';
                        $credOcc = $credOcc + $valorOcc;
                    }
                }
                ?>
                
                <tr><td colspan="3"></td></tr>
                
                <tr>
                <?php
                $sdConc = $debOcc - $credOcc;
                if($sdConc == 0)
                {
                    echo '<td colspan="3"></td>';
                }
                else if($sdConc > 0)
                {
                    echo '<th class="text-center" colspan="2">Valores a ressarcir</th>';
                    echo '<th class="text-center">R$ ' . number_format($sdConc,"2", ",", ".") . '</th>';
                }
                else if($sdConc < 0)
                {
                    echo '<th class="text-center" colspan="2">Valores pertencentes à entidade</th>';
                    echo '<th class="text-center">R$ ' . number_format($sdConc,"2", ",", ".") . '</th>';
                }
                ?>
                </tr>
            
                <tr class="text-center align-middle">
                    <th colspan="2">Saldo a reprogramar para o exercício seguinte</th>
                    <?php
                    $saldoRep = $bancoFinal + $sdConc - $transito;
                    echo '<th class="text-center">R$ ' . number_format($saldoRep,"2", ",", ".") . '</th>';
                    ?>
                    
                </tr>
            </tbody>                
        </table>
       
       <?php 
                
        $hoje = new DateTime();
        $hoje->setTimezone($timezone);
        
        $dia = $hoje->format('d'); 
        $mes = $hoje->format('m');
        $ano = $hoje->format('Y');

        switch($mes)
        {
            case 1:
                $mes = "janeiro";
                break;
            case 2:
                $mes = "fevereiro";
                break;
            case 3:
                $mes = "março";
                break;
            case 4:
                $mes = "abril";
                break;
            case 5:
                $mes = "maio";
                break;
            case 6:
                $mes = "junho";
                break;
            case 7:
                $mes = "julho";
                break;
            case 8:
                $mes = "agosto";
                break;
            case 9:
                $mes = "setembro";
                break;
            case 10:
                $mes = "outubro";
                break;
            case 11:
                $mes = "novembro";
                break;
            case 12:
                $mes = "dezembro";
                break;
        }
        
        ?>
       
        <div class="row text-center">
            <span>SE-331.2, <?= $dia . ' de ' . $mes . ' de ' . $ano ?>.</span>
        </div>
        
        <br>
        <div class="row text-center assinatura">
            <span><b>FABIANA SOUZA OLIVEIRA</b><br>
            Encarregada do Serviço de Gestão<br>
            de Ajustes Federais - SE-331.2</span>
        </div>
        <br>        
        <div class="row">
            <div class="col text-center assinatura">
                <span><b>WAGNER TEIXEIRA DE ALMEIDA</b><br>
                Diretor da Seção de Gestão<br>
                de Ajustes - SE-331</span>
            </div>
            <div class="col text-center assinatura">
                <span><b>ERENILDA DE SOUZA MELO</b><br>
                Diretora da Seção de Controle da<br>
                Execução de Ajustes - SE-332</span>
            </div>
        </div>
        <br>
        <div class="row text-center assinatura">
            <span><b>DANIELA FERREIRA DIAS</b><br>
            Diretora da Divisão de Gestão e<br>
            Controle de Ajustes - SE-33</span>
        </div>       
        
  
    <div class="pagebreak">
        <div class="container-lg">
            <img src="img/folhadeinformacao.png" width="100%" alt="folha de informação" />
            <br>
            <p><b>Serviço:</b></p>
            <p style="text-indent: 3em; text-align: justify; text-justify: inter-word;">Procedemos a análise financeira da prestação de contas relativa ao Exercício 2025 do Programa Dinheiro Direto na Escola - <?= mb_strtoupper($programa) ?>, 
            referente aos recursos repassados pelo Fundo Nacional de Desenvolvimento da Educação - FNDE à <?= $instituicao ?> e constatamos que a entidade possui saldo a utilizar no próximo exercício, 
            conforme demonstrado abaixo:</p>
            <br><br>
            <table class="table table-sm tabela table-responsive">
                <thead>
                    <tr class="text-center align-middle"><th colspan="5">DEMONSTRATIVO FINANCEIRO</th></tr>
                </thead>
                <tbody>                                    
                    <?php
                    $saldoCusteio = 0;
                    $sql = $pdo->prepare("SELECT s.acao_id, p.acao, s.categoria, s.saldo25 FROM saldo_pdde s JOIN programaspdde p ON s.acao_id = p.id WHERE proc_id = :idProc ORDER BY s.acao_id");
                    $sql->bindParam("idProc", $_GET['idProc']);
                    $sql->execute();
                    while($af = $sql->fetch())
                    {
                        $acaoId = $af->acao_id;
                        $acao = $af->acao;
                        $categoria = $af->categoria;                        
                        $saldo25 = $af->saldo25;                        
                        
                        if($categoria == "C")
                        {
                            echo '<tr class="align-middle">';
                            echo '<td style="min-width: 15px;"></td>';
                            echo '<th style="min-width: 75px; max-width: 100px;" scope="col" class="col">CUSTEIO ' . mb_strtoupper($acao) . '</th>';
                            echo '<td></td>';
                            echo '<td style="min-width: 75px; max-width: 100px;" scope="col" class="col text-center">R$ ' . number_format($saldo25, "2", ",", ".") . '</td>';
                            echo '<td></td>';
                            echo '</tr>';
                            $saldoCusteio = $saldoCusteio + $saldo25;
                        }                                            
                    }                    
                    ?>                    
                    <tr>
                        <td style="min-width: 15px;"></td>
                        <td></td>
                        <th style="min-width: 75px; max-width: 100px;" scope="col" class="col">SALDO CUSTEIO</th>
                        <td></td>
                        <th style="min-width: 75px; max-width: 100px;" scope="col" class="col text-center">R$ <?= number_format($saldoCusteio, "2", ",", ".") ?></th>
                    </tr>
                    
                    <?php
                    $saldoCapital = 0;
                    $sql = $pdo->prepare("SELECT s.acao_id, p.acao, s.categoria, s.saldo25 FROM saldo_pdde s JOIN programaspdde p ON s.acao_id = p.id WHERE proc_id = :idProc ORDER BY s.acao_id");
                    $sql->bindParam("idProc", $_GET['idProc']);
                    $sql->execute();
                    while($af = $sql->fetch())
                    {
                        $acaoId = $af->acao_id;
                        $acao = $af->acao;
                        $categoria = $af->categoria;                        
                        $saldo25 = $af->saldo25;                        
                        
                        if($categoria == "K")
                        {
                            echo '<tr class="align-middle">';
                            echo '<td style="min-width: 15px;"></td>';
                            echo '<th style="min-width: 75px; max-width: 100px;" scope="col" class="col">CAPITAL ' . mb_strtoupper($acao) . '</th>';
                            echo '<td></td>';                           
                            echo '<td style="min-width: 75px; max-width: 100px;" scope="col" class="col text-center">R$ ' . number_format($saldo25, "2", ",", ".") . '</td>';
                            echo '<td></td>';
                            echo '</tr>';
                            $saldoCapital = $saldoCapital + $saldo25;
                        }                        
                    }                    
                    ?>                    
                    <tr>
                        <td style="min-width: 15px;"></td>
                        <td></td>
                        <th style="min-width: 75px; max-width: 100px;" scope="col" class="col">SALDO CAPITAL</th>
                        <td></td>
                        <th style="min-width: 75px; max-width: 100px;" scope="col" class="col text-center">R$ <?= number_format($saldoCapital, "2", ",", ".") ?></th>
                    </tr>
                    <tr><td colspan="5"></td></tr>
                    <tr>
                        <td style="min-width: 15px;"></td>
                        <td></td>
                        <th style="min-width: 75px; max-width: 100px;" scope="col" class="col">SALDO GERAL</th>
                        <td></td>
                        <th style="min-width: 75px; max-width: 100px;" scope="col" class="col text-center">R$ <?= number_format($saldoCusteio + $saldoCapital, "2", ",", ".") ?></th>
                    </tr>
                </tbody>
            </table>
            <br><br><br>
            
            <p style="text-indent: 3em; text-align: justify; text-justify: inter-word;">As cópias dos documentos juntadas ao processo tiveram sua autenticidade atestada por meio de declaração firmada pelo representante legal da entidade.</p>
            <br>
            <div class="row text-center">
                <span>SE-331.2, <?= $dia . ' de ' . $mes . ' de ' . $ano ?>.</span>
            </div> 
            <br>            
            <br>
            <div class="row text-center assinatura">
                <span><b>FABIANA SOUZA OLIVEIRA</b><br>
                Encarregada do Serviço de Gestão<br>
                de Ajustes Federais - SE-331.2</span>
            </div>
            <br>
            <div class="row">
                <div class="col text-center assinatura">
                    <span><b>WAGNER TEIXEIRA DE ALMEIDA</b><br>
                    Diretor da Seção de Gestão<br>
                    de Ajustes - SE-331</span>
                </div>
                <div class="col text-center assinatura">
                    <span><b>ERENILDA DE SOUZA MELO</b><br>
                    Diretora da Seção de Controle da<br>
                    Execução de Ajustes - SE-332</span>
                </div>
            </div>
            <br>
            <div class="row text-center assinatura">
                <span><b>DANIELA FERREIRA DIAS</b><br>
                Diretora da Divisão de Gestão e<br>
                Controle de Ajustes - SE-33</span>
            </div>
            
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
</body>
</html>
<?php
ob_flush();
?>