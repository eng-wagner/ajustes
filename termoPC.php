<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";
use Source\Database\Connect;

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
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    <title>Prestação de Contas - Termo de Colaboração</title>
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>
</head>

<body>
    <?php

    if($_SESSION['flag'] == false){
        header("Location:index.php");
    }

    if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
        $_SESSION['flag'] = false;
        session_unset();
        header("Location:index.php");
    }

    $sql = Connect::getInstance()->prepare("SELECT nome, perfil FROM usuarios WHERE id = :idUser");
    $sql->bindParam('idUser',$_SESSION['user_id']);
    if($sql->execute())
    {
        if($proc = $sql->fetch()){
            $userName = $proc->nome;
            $perfil = $proc->perfil;
        }
    }
    
    $firstName = substr($userName,0,strpos($userName," "));        

    if(isset($_REQUEST['pddeAE']) && $_REQUEST['pddeAE'] == true){
        $_SESSION['nav'] = array("active","","","","");
        $_SESSION['navShow'] = array("show active","","","","");
        $_SESSION['sel'] = array("true","false","false","false","false");
        header("Location:termoPC.php");
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

    if(isset($_SESSION['idProc']) && $_SESSION['idProc'] > 0 )
    {    
        $sql = Connect::getInstance()->prepare("SELECT i.instituicao, i.cnpj, i.email, i.endereco, i.inep, i.telefone, p.orgao, p.numero, p.ano, p.digito, p.assunto,
            p.tipo, p.id, c.c_nome, c.c_telefone, c.c_email FROM processos p JOIN instituicoes i ON p.instituicao_id = i.id JOIN contabilidades c 
            ON c.id = i.cont_id WHERE p.id = :idProc");
                    
        $sql->bindParam('idProc',$_SESSION['idProc']);
        if($sql->execute())
        {
            if($proc = $sql->fetch()){
                $instituicao = $proc->instituicao;
                $cnpj = $proc->cnpj;
                $iEmail = $proc->email;
                $iEndereco = $proc->endereco;
                $inep = $proc->inep;
                $iTelefone = $proc->telefone;
                $orgao = $proc->orgao;
                $numero = $proc->numero;
                $ano = $proc->ano;
                $digito = $proc->digito;
                $assunto = $proc->assunto;                       
                $tipo = $proc->tipo;
                $idProc = $proc->id;
                $cNome = $proc->c_nome;
                $cTelefone = $proc->c_telefone;
                $cEmail = $proc->c_email;
            }
        }
    
        $cnpj = substr($cnpj,0,2) . "." . substr($cnpj,2,3) . "." . substr($cnpj,5,3) . "/" . substr($cnpj,8,4) . "-" . substr($cnpj,12,2);
        
        $sql = Connect::getInstance()->prepare("SELECT a.status_id, s.status_pc FROM analise_pdde_23 a JOIN status_processo s ON a.status_id = s.id WHERE a.proc_id = :idProc");
        $sql->bindParam('idProc',$_SESSION['idProc']);
        if($sql->execute())
        {
            if($proc = $sql->fetch()){
                $idStatus = $proc->status_id;
                $statusPC = $proc->status_pc;                                       
            } else {
                $idStatus = 1;                                      
                $sql = Connect::getInstance()->prepare("SELECT status_pc FROM status_processo WHERE id = :idStatus");
                $sql->bindParam('idStatus',$idStatus);
                if($sql->execute())
                {
                    if($proc = $sql->fetch()){                            
                        $statusPC = $proc->status_pc;                            
                    }
                } 
            }                 
        }            
        
    } else {
        header('Location:buscar.php');
    }
        
    ?>

    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Prestação de Contas 2025 - Termo de Colaboração
                </h1>
            </div>
            <!-- Início do Conteúdo  -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-nomeEnt">Entidade</span>
                            <input type="text" name="nomeEnt" value="<?php echo $instituicao; ?>" class="col-9 form-control" aria-describedby="inputGroup-nomeEnt" readonly/>
                        </div>
                    </div>
                    <div class="col"> 
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text col-3" id="inputGroup-processo">Processo</span>
                            <input type="text" name="campo3" value="<?php echo $orgao . '.' . $numero . '/' . $ano . '-' . $digito; ?>" class="col-9 form-control" aria-describedby="inputGroup-processo" readonly/>
                        </div>
                    </div>                        
                </div>
            </div>

            <div class="container-fluid">        
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link <?php echo $_SESSION['nav'][0]; ?>" id="nav-dados-tab" data-bs-toggle="tab" data-bs-target="#nav-dados" type="button" role="tab" aria-controls="nav-dados" aria-selected="<?php echo $_SESSION['sel'][0]; ?>">Dados Gerais</button>
                    <button class="nav-link <?php echo $_SESSION['nav'][1]; ?>" id="nav-dados1q-tab" data-bs-toggle="tab" data-bs-target="#nav-1q" type="button" role="tab" aria-controls="nav-1q" aria-selected="<?php echo $_SESSION['sel'][1]; ?>">1º Quadrimestre</button>
                    <button class="nav-link <?php echo $_SESSION['nav'][2]; ?>" id="nav-dados2q-tab" data-bs-toggle="tab" data-bs-target="#nav-2q" type="button" role="tab" aria-controls="nav-2q" aria-selected="<?php echo $_SESSION['sel'][2]; ?>">2º Quadrimestre</button>
                    <button class="nav-link <?php echo $_SESSION['nav'][3]; ?>" id="nav-dados3q-tab" data-bs-toggle="tab" data-bs-target="#nav-3q" type="button" role="tab" aria-controls="nav-3q" aria-selected="<?php echo $_SESSION['sel'][3]; ?>">3º Quadrimestre</button>
                    <button class="nav-link <?php echo $_SESSION['nav'][4]; ?>" id="nav-pendencia-tab" data-bs-toggle="tab" data-bs-target="#nav-pendencia" type="button" role="tab" aria-controls="nav-pendencia" aria-selected="<?php echo $_SESSION['sel'][4]; ?>">Histórico de E-mails</button>              
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 1º QUADRIMESTRE -->
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][1]; ?>" id="nav-1q" role="tabpanel" aria-labelledby="nav-1q-tab" tabindex="0">                
                        <div class="container-fluid">
                            <br />
                            <div class="row my-auto">                        
                                <button type="button" class="col-2 btn btn-primary mx-2" data-bs-toggle="modal" data-bs-target="#pendenciaModal">+ Nova Pendência</button>                        
                                <a href="emailPendTc.php?idProc=<?= $_SESSION['idProc']; ?>" target="_blank" class="col-2 mx-2"><button type="button" class="col-12 btn btn-success">Escrever E-mail</button></a>                        
                                <div class="col-4 text-center mx-2 fw-semibold">Histórico de Pendências</div>                                        
                            </div>
                            <br>
                            <div class="row my-auto">
                                <div class="col">    
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-2" id="inputGroup-lastEmail">Último e-mail</span>
                                        <input type="text" value="<?= $pendFin1q ?? '' ?>" class="col-3 form-control" aria-describedby="inputGroup-lastEmail" readonly/>
                                    </div>
                                </div>
                                <div class="col">    
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-2" id="inputGroup-userEmail">Usuário</span>
                                        <input type="text" value="<?= $pendFin1q ?? '' ?>" class="col-3 form-control" aria-describedby="inputGroup-userEmail" readonly/>
                                    </div>
                                </div>
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
                                                <th class="col w-auto fw-semibold">Nº Doc</th>
                                                <th class="col w-auto fw-semibold">Data de Emissão</th>                                                
                                                <th class="col w-auto fw-semibold">Fornecedor</th>
                                                <th class="col w-auto fw-semibold">Valor</th>                                                
                                                <th class="col w-auto fw-semibold">Pendência</th>
                                                <th class="col w-auto fw-semibold">Providências</th>
                                                <th class="col w-auto fw-semibold">Glosa</th>
                                                <th class="col w-auto fw-semibold">Data E-mail</th>                                                                                                                                        
                                                <th class="col w-auto fw-semibold">Etapa</th>
                                                <th class="col w-auto fw-semibold">Regularização</th>
                                                <th class="col w-auto fw-semibold">Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $quad1 = 1;
                                            $sql = Connect::getInstance()->prepare("SELECT p.id, p.dataPend, p.itemDRD, d.documento, p.numDocPend, p.dataDocPend, p.fornecedor, 
                                                p.valorDoc, t.pendencia, p.providencias, p.glosaDoc, p.dataEmail, p.etapa_id, p.resolvido, p.dataResolvido, p.ativado FROM pendencias_tc24 p JOIN tipo_documento d ON 
                                                p.docPend_id = d.id JOIN tipo_pendencia t ON p.pend_id = t.id WHERE p.proc_id = :idProc AND p.quad = :quadrimestre");                                                    
                                            $sql->bindParam('idProc',$_SESSION['idProc']);
                                            $sql->bindParam('quadrimestre',$quad1);
                                            if($sql->execute())
                                            {
                                                while($proc = $sql->fetch())
                                                {
                                                    $idPend = $proc->id;
                                                    $dataPend = $proc->dataPend;
                                                    $itemDRD = $proc->itemDRD;
                                                    $documento = $proc->documento;
                                                    $numDocPend = $proc->numDocPend;
                                                    $dataDocPend = $proc->dataDocPend;
                                                    $fornecedor = $proc->fornecedor;
                                                    $valorDoc = $proc->valorDoc;
                                                    $pendencia = $proc->pendencia;
                                                    $providencias = $proc->providencias;
                                                    $valorGlosa = $proc->glosaDoc;
                                                    $dataEmail = $proc->dataEmail;
                                                    $etapaId = $proc->etapa_id;
                                                    $checkResolved = $proc->resolvido;
                                                    $dataResolved = $proc->dataResolvido;
                                                    $pendAtiva = $proc->ativado;
                                                    
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
                                                            $etapa = "Execução";
                                                            break;
                                                        case 2:
                                                            $etapa = "Financeira";
                                                            break;                                                        
                                                    }
                                                    if($pendAtiva == 1){
                                                        echo '<tr class="align-middle fw-lighter">';
                                                        echo '<td class="col text-center">' . $dataPend . '</td>';
                                                        echo '<td class="col text-center">' . $itemDRD . '</td>';
                                                        echo '<td class="col">' . $documento . '</td>';
                                                        echo '<td class="col">' . $numDocPend . '</td>';
                                                        echo '<td class="col text-center">' . $dataDocPend . '</td>';                                                        
                                                        echo '<td class="col">' . $fornecedor . '</td>';
                                                        echo '<td class="col">R$ ' . number_format($valorDoc, 2, ",", ".") . '</td>';
                                                        echo '<td class="col">' . $pendencia . '</td>';
                                                        echo '<td class="col">' . $providencias . '</td>';
                                                        echo '<td class="col">R$ ' . number_format($valorGlosa, 2, ",", ".") . '</td>';                                                        
                                                        echo '<td class="col text-center">' . $dataEmail . '</td>';                                                        
                                                        echo '<td class="col">' . $etapa . '</td>';
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
                                                            echo '<td class="col text-center">' . $dataResolved . '</td>';                                                        
                                                            echo '<td class="col">';          
                                                            echo '</td>';
                                                        }
                                                        echo '</tr>';                                            
                                                    }
                                                }
                                            }
                                            ?>                        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    if(isset($_GET['reg']) && $_GET['reg'] == true){
                        $dataReg = new DateTime('now',$timezone);
                        $dataReg = $dataReg->format("Y-m-d H:i:s");                
                        
                        $resolvido = 1;

                        $sql = Connect::getInstance()->prepare("UPDATE pendencias_tc24 SET resolvido=?, dataResolvido=? WHERE id=?;");
                        $sql->bindParam(1,$resolvido);
                        $sql->bindParam(2,$dataReg);
                        $sql->bindParam(3,$_GET['idPend']);                   
                        if($sql->execute()){    
                            $_SESSION['nav'] = array("","active","","","");
                            $_SESSION['navShow'] = array("","show active","","","");
                            $_SESSION['sel'] = array("false","true","false","false","false");                
                            header('Location:termoPC.php');
                        }
                        else
                        {
                            echo '<script>alert("ERRO!!!!")</script>';
                        }
                    }
                    if(isset($_GET['delPend']) && $_GET['delPend'] == true){
                        $ativado = 0;
                        $sql = Connect::getInstance()->prepare("UPDATE pendencias_tc24 SET usuario_id = :userId, ativado = :ativado WHERE id = :idPend");
                        $sql->bindParam('userId',$_SESSION['user_id']);
                        $sql->bindParam('ativado',$ativado);
                        $sql->bindParam('idPend',$_GET['idPend']);
                        $sql->execute();
                        $_SESSION['nav'] = array("","active","","","");
                        $_SESSION['navShow'] = array("","show active","","","");
                        $_SESSION['sel'] = array("false","true","false","false","false");
                        header('Location:termoPC.php');                    
                    }

                    if(isset($_REQUEST['newPend']) && $_REQUEST['newPend'] == true){
                        $_SESSION['nav'] = array("","active","","","");
                        $_SESSION['navShow'] = array("","show active","","","");
                        $_SESSION['sel'] = array("false","true","false","false","false");
                        
                        $dataHoje = new DateTime('now',$timezone);
                        $dataHoje = $dataHoje->format("Y-m-d H:i:s");

                        $dataDocPend = new DateTime($_POST['dataDocP'],$timezone);
                        $dataDocPend = $dataDocPend->format("Y-m-d");
                        
                        $valDocSQL = str_replace("R$ ", "", $_POST['valorDoc']);
                        $valDocSQL = str_replace(".", "", $valDocSQL);
                        $valDocSQL = str_replace(",", ".", $valDocSQL);

                        $valGlosaSQL = str_replace("R$ ", "", $_POST['valorGlosa']);
                        $valGlosaSQL = str_replace(".", "", $valGlosaSQL);
                        $valGlosaSQL = str_replace(",", ".", $valGlosaSQL);
                        
                        $sql = Connect::getInstance()->prepare("INSERT INTO pendencias_tc24 (proc_id, usuario_id, dataPend, quad, itemDRD, docPend_id, numDocPend, dataDocPend, fornecedor,  
                            valorDoc, pend_id, providencias, glosaDoc, etapa_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $sql->bindParam(1,$_SESSION['idProc']);
                        $sql->bindParam(2,$_SESSION['user_id']);
                        $sql->bindParam(3,$dataHoje);
                        $sql->bindParam(4,$_POST['quad1']);
                        $sql->bindParam(5,$_POST['itemDRD']);                
                        $sql->bindParam(6,$_POST['docPend']);
                        $sql->bindParam(7,$_POST['numDocP']);
                        $sql->bindParam(8,$dataDocPend);                        
                        $sql->bindParam(9,$_POST['fornecedor']);
                        $sql->bindParam(10,$valDocSQL);
                        $sql->bindParam(11,$_POST['pendencia']);
                        $sql->bindParam(12,$_POST['providencias']);
                        $sql->bindParam(13,$valGlosaSQL);
                        $sql->bindParam(14,$_POST['etapaPend']);                
                        if($sql->execute())
                        {                 
                            header('Location:termoPC.php');
                        }
                        else
                        {
                            echo '<script>alert("ERRO!!!!")</script>';
                        }
                        /*
                        
                        echo $_SESSION['idProc'] . " " . $_SESSION['user_id'] . " " . $dataHoje . " " . $_POST['quad1'] . " " . $_POST['itemDRD'];
                        echo $_POST['docPend'] . " " . $_POST['numDocP'] . " " . $dataDocPend . " " . $_POST['fornecedor'] . " " . $valDocSQL;
                        echo $_POST['pendencia'] . " " . $_POST['providencias'] . " " . $valGlosaSQL . " " . $_POST['etapaPend'];
                        header('Location:termoPC.php');
                        
                        var_dump($_POST);
                        var_dump($_SESSION);
                        */
                    }
                    
                    if(isset($_REQUEST['updatePend']) && $_REQUEST['updatePend'] == true)
                    {   
                        $_SESSION['nav'] = array("","active","","","");
                        $_SESSION['navShow'] = array("","show active","","","");
                        $_SESSION['sel'] = array("false","true","false","false","false");           
                        
                        $agora = new DateTime('now',$timezone);
                        $agora = $agora->format('Y-m-d H:i:s');

                        $dataDocPend = new DateTime($_POST['dataDocP'],$timezone);
                        $dataDocPend = $dataDocPend->format("Y-m-d");

                        $valDocSQL = str_replace("R$ ", "", $_POST['valorDoc']);
                        $valDocSQL = str_replace(".", "", $valDocSQL);
                        $valDocSQL = str_replace(",", ".", $valDocSQL);

                        $valGlosaSQL = str_replace("R$ ", "", $_POST['valorGlosa']);
                        $valGlosaSQL = str_replace(".", "", $valGlosaSQL);
                        $valGlosaSQL = str_replace(",", ".", $valGlosaSQL);
                                                
                        $sql = Connect::getInstance()->prepare("UPDATE pendencias_tc24 SET 
                        usuario_id = ?, 
                        dataPend = ?, 
                        itemDRD = ?, 
                        docPend_id = ?,
                        numDocPend = ?, 
                        dataDocPend = ?,
                        fornecedor = ?, 
                        valorDoc = ?,
                        pend_id = ?, 
                        providencias = ?,
                        glosaDoc = ?,
                        dataEmail = ?, 
                        etapa_id = ?
                        WHERE id = ?");
                        $sql->bindParam(1,$_SESSION['user_id']);
                        $sql->bindParam(2,$agora);
                        $sql->bindParam(3,$_POST['itemDRD']);
                        $sql->bindParam(4,$_POST['docPend']);
                        $sql->bindParam(5,$_POST['numDocP']);
                        $sql->bindParam(6,$dataDocPend);
                        $sql->bindParam(7,$_POST['fornecedor']);
                        $sql->bindParam(8,$valDocSQL);
                        $sql->bindParam(9,$_POST['pendencia']);
                        $sql->bindParam(10,$_POST['providencias']);
                        $sql->bindParam(11,$valGlosaSQL);
                        $sql->bindParam(12,$_POST['dataEmail']);
                        $sql->bindParam(13,$_POST['etapaPend']);
                        $sql->bindParam(14,$_POST['idPendM']);
                        if($sql->execute()){
                            header('Location:termoPC.php');
                        }
                        else
                        {
                            echo '<script>alert("ERRO!!!!")</script>';
                        }
                    }

                    if(isset($_GET['editPend']) && $_GET['editPend'] == true){
                        
                        $_SESSION['nav'] = array("","active","","","");
                        $_SESSION['navShow'] = array("","show active","","","");
                        $_SESSION['sel'] = array("false","true","false","false","false");
                        
                        $sql = Connect::getInstance()->prepare("SELECT * FROM pendencias_tc24 WHERE id = :idPend");
                        $sql->bindParam('idPend',$_GET['idPend']);
                        if($sql->execute())
                        {
                            if($pend = $sql->fetch())
                            {
                                $idPendM = $pend->id;
                                $iDRDM = $pend->itemDRD;
                                $docPendIdM = $pend->docPend_id;
                                $numDocPendM = $pend->numDocPend;
                                $dataDocPendM = $pend->dataDocPend;
                                $valorDocM = $pend->valorDoc;
                                $valorGlosaM = $pend->glosaDoc;
                                $fornecedorM = $pend->fornecedor;
                                $pendIdM = $pend->pend_id;
                                $providenciasM = $pend->providencias;
                                $etapaIdM = $pend->etapa_id;
                            }
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
                                        <input type="hidden" value="1" name="quad1" />
                                    </div>
                                    <div class="modal-body">
                                        <div class="content-fluid">
                                            <div class="row">                                        
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-2" id="inputGroup-itemDRD">Item DRD</span>
                                                        <input type="text" name="itemDRD" value="<?= $iDRDM ?? ''; ?>" class="col-10 form-control" aria-describedby="inputGroup-itemDRD" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <label class="input-group-text col-2" for="inputGroup-docPend">Documento</label>
                                                        <select name="docPend" class="form-select col-10" id="inputGroup-docPend" required>                                                    
                                                            <option disabled <?= isset($docPendIdM) && $docPendIdM != null ? '' : 'selected'; ?>>Selecione...</option>
                                                            <?php
                                                            $sql = Connect::getInstance()->prepare("SELECT * FROM tipo_documento ORDER BY documento ASC");                                                    
                                                            if($sql->execute())
                                                            {
                                                                while($proc = $sql->fetch())
                                                                {
                                                                    $idDoc = $proc->id;                                                                                
                                                                    $docPend = $proc->documento;
                                                                    $tc = $proc->tc;
                                                                    if($tc == 1)
                                                                    {             
                                                                        if(isset($docPendIdM) && $docPendIdM == $idDoc){
                                                                            echo '<option value="' . $idDoc . '" selected>' . $docPend . '</option>';
                                                                        } else {  
                                                                            echo '<option value="' . $idDoc . '">' . $docPend . '</option>';
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            ?>                                                     
                                                        </select>                                                            
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-numDocP">Nº Documento</span>
                                                        <input type="text" name="numDocP" value="<?= $numDocPendM ?? ''; ?>" class="col-8 form-control" aria-describedby="inputGroup-numDocP" />
                                                    </div>
                                                </div> 
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-dataDocP">Data Documento</span>
                                                        <input type="date" name="dataDocP" value="<?= $dataDocPendM ?? ''; ?>" class="col-8 form-control" aria-describedby="inputGroup-dataDocP" />
                                                    </div>                                                  
                                                </div>                                                                                       
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-valorDoc">Valor</span>
                                                        <input type="text" name="valorDoc" value="<?= 'R$ ' . number_format($valorDocM, 2, ",", ".") ?>" class="col-8 form-control" aria-describedby="inputGroup-numDocP" />
                                                    </div>
                                                </div> 
                                                <div class="col">
                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text col-4" id="inputGroup-valorGlosa">Glosa</span>
                                                        <input type="text" name="valorGlosa" value="<?= 'R$ ' . number_format($valorGlosaM, 2, ",", ".") ?>" class="col-8 form-control" aria-describedby="inputGroup-dataDocP" />
                                                    </div>                                                  
                                                </div>                                                                                       
                                            </div>
                                            <div class="row">                                        
                                                <div class="input-group input-group-sm mb-2">
                                                    <span class="input-group-text col-2" id="inputGroup-fornecedor">Fornecedor</span>
                                                    <input type="text" name="fornecedor" value="<?= $fornecedorM ?? ''; ?>" class="col-10 form-control" aria-describedby="inputGroup-favorecido" />
                                                </div>
                                            </div>
                                            <div class="row">                                        
                                                <div class="input-group input-group-sm mb-2">
                                                    <label class="input-group-text col-2" for="inputGroup-pendencia">Pendência</label>
                                                    <select name="pendencia" class="form-select col-10" id="inputGroup-pendencia">
                                                        <option disabled <?= isset($pendIdM) && $pendIdM != null ? '' : 'selected'; ?>>Selecione...</option>
                                                        <?php
                                                        $sql = Connect::getInstance()->prepare("SELECT * FROM tipo_pendencia");                                                    
                                                        if($sql->execute())
                                                        {
                                                            while($proc = $sql->fetch())
                                                            {
                                                                $idTipoPend = $proc->id;
                                                                $tipoPend = $proc->pendencia;                                                                                                                   
                                                                if(isset($pendIdM) && $pendIdM == $idTipoPend)
                                                                {
                                                                echo '<option value="' . $idTipoPend . '" selected>' . $tipoPend . '</option>';
                                                                } 
                                                                else {
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
                                                <input type="radio" name="etapaPend" class="form-check-input" value="1" id="rExecucao" <?= isset($etapaIdM) && $etapaIdM == 1 ? "checked" : "" ?> />
                                                <label class="form-check-label" for="rExecucao">Execução</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input type="radio" name="etapaPend" class="form-check-input" value="2" id="rFinanceira" <?= isset($etapaIdM) && $etapaIdM == 2 ? "checked" : "" ?> />
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
                    <!-- aqui acaba o 1º quad -->
                     <!-- 2º QUADRIMESTRE -->
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][2]; ?>" id="nav-1q" role="tabpanel" aria-labelledby="nav-2q-tab" tabindex="0">                
                    </div>
                    <!-- aqui acaba o 2º quad -->
                    <!-- 3º QUADRIMESTRE -->
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][3]; ?>" id="nav-1q" role="tabpanel" aria-labelledby="nav-3q-tab" tabindex="0">                
                    </div>
                    <!-- aqui acaba o 3º quad -->
                    <!-- HISTÓRICO DE E-MAILS -->
                    <div class="tab-pane fade <?php echo $_SESSION['navShow'][4]; ?>" id="nav-pendencia" role="tabpanel" aria-labelledby="nav-pendencia-tab" tabindex="0">
                    </div>
                </div>
            </div>            
            <!-- Fim do Conteúdo  -->
        </div>
    </div>

     <!-- Modal -->
     <div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="menuModalLabel">Deseja voltar ao menu?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!--<div class="modal-body">
                    Deseja realmente sair?
                </div>-->
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="location.href='home.php'">SIM</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">NÃO</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Sair -->
    <div class="modal fade" id="logoffModal" tabindex="-1" aria-labelledby="logoffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="logoffModalLabel">Deseja realmente sair?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!--<div class="modal-body">
                    Deseja realmente sair?
                </div>-->
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="location.href='?logoff=true'">SIM</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">NÃO</button>
                </div>
            </div>
        </div>
    </div>
    
    
    <footer style="position: fixed; left: 0; bottom: 0; width: 100%; text-align: center;">
        <font color="#575756"><small>© Copyright - Secretaria de Educação - São Bernardo do Campo | 2024. Todos os Direitos Reservados.</small></font>
    </footer>
    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>    
</body>
</html>