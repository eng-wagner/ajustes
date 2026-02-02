<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\User;
use Source\Instituicao;
use Source\ItensCota;

$userModel = new User();
$instituicaoModel = new Instituicao();
$itensModel = new ItensCota();

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
    <title>Gerar Cotas</title>
    <style>
        h1 {
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

    if (isset($_REQUEST['pddeAE']) && $_REQUEST['pddeAE'] == true) {
        $_SESSION['nav'] = array("active", "", "", "", "");
        $_SESSION['navShow'] = array("show active", "", "", "", "");
        $_SESSION['sel'] = array("true", "false", "false", "false", "false");
        header("Location:pddePC.php");
    }

    if (isset($_REQUEST['pddeAF']) && $_REQUEST['pddeAF'] == true) {
        $_SESSION['navF'] = array("active", "", "", "", "", "");
        $_SESSION['navShowF'] = array("show active", "", "", "", "", "");
        $_SESSION['selF'] = array("true", "false", "false", "false", "false", "false");
        header("Location:pddeFinanc.php");
    }

    if(isset($_REQUEST['analiseTC']) && $_REQUEST['analiseTC'] == true){
        $_SESSION['nav'] = array("active","","","","");
        $_SESSION['navShow'] = array("show active","","","","");
        $_SESSION['sel'] = array("true","false","false","false","false");
        header("Location:termoPC.php");
    }

    if (isset($_REQUEST['gerar']) && $_REQUEST['gerar'] == true) {
        unset($_SESSION['cota']);
        $cota = array($_POST);
        $_SESSION['cota'] = $cota;        
        if(isset($_POST['tipoCota']) && $_POST['tipoCota'] ==  1)
        {        
            echo "<script>window.open('cota.php', '_blank');</script>";
        }
        else if(isset($_POST['tipoCota']) && $_POST['tipoCota'] ==  2)
        {
            echo "<script>window.open('cotafin.php', '_blank');</script>";
        }
        //print_r($cota);
        //var_dump($cota);
    }
    ?>

    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Gerar Cota
                </h1>
            </div>
            <!-- Início do Conteúdo -->

            <div class="container-fluid">
                <form method="post" action="?gerar=true" name="meuForm" class="form-group mx-auto">
                    <div class="row">
                        <div class="col-6">
                            <div class="input-group input-group-sm mb-2">
                                <label class="input-group-text col-2" for="inputGroup-inst">Instituição</label>
                                <select name="instituicao" class="form-select w-50 col-10" id="inputGroup-inst">
                                    <option selected>Selecione a instituição...</option>
                                    <?php
                                    $instituicoes = $instituicaoModel->all();                                    
                                    if ($instituicoes) {
                                        foreach($instituicoes as $inst) {
                                            $id = $inst->id;
                                            $instituicao = $inst->instituicao;
                                            echo '<option value="' . $id . '">' . $instituicao . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="input-group input-group-sm mb-2">
                                <label class="input-group-text col-4" for="inputGroup-acao">Ação</label>
                                <select name="programa" class="form-select w-50 col-8" id="inputGroup-acao" require>
                                    <option selected disabled>Selecione...</option>
                                    <option value="1">PDDE Básico</option>
                                    <option value="2">PDDE Qualidade</option>
                                    <option value="3">PDDE Equidade</option>
                                    <option value="4">PDDE Educação Integral</option>
                                    <option value="5">PDDE PDE Escola</option>
                                </select>
                            </div>
                        </div>
                        
                <div class="col-3">
                    <div class="input-group input-group-sm mb-2">
                        <label class="input-group-text col-4" for="inputGroup-tipo">Tipo da Cota</label>
                        <select name="tipoCota" class="form-select w-50 col-8" id="inputGroup-tipo" require>
                            <option selected disabled>Selecione...</option>
                            <option value="1">Juntada e Execução</option>
                            <option value="2">Análise Financeira</option>
                        </select>                                                            
                    </div>
                </div>
                
                        <hr>
                        <h5 class="text-center">Selecione os documentos que vão compor a cota</h5>
                        <br />
                        <?php
                        $itens = $itensModel->all();                        
                        if ($itens) {
                            foreach($itens as $docs) {
                                $idDoc = $docs->id;
                                $documentosCota = $docs->documentos;
                                $chName = $docs->chName;
                                $docAtivo = $docs->ativo;

                                if ($docAtivo == 1) {
                                    echo '<div class="form-check">';
                                    echo '<input class="form-check-input" type="checkbox" name="' . $chName . '" value="' . $idDoc . '" id="check_' . $chName . '">';
                                    echo '<label class="form-check-label" for="check_' . $chName . '">' . $documentosCota . '</label>';
                                    echo '</div>';
                                }
                            }
                        }
                        ?>
                        <br>
                        <br>
                        <input type="submit" value="Gerar Cota" class="btn btn-primary" />
                </form>
                <br /><br />
                <hr>
                <br />

            </div>

            <!-- Fim do Conteúdo -->
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>

    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
<?php
ob_flush();
?>