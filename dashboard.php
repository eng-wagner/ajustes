<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\User;
use Source\Instituicao;
use Source\Processo;

// Criar instâncias do modelo.
// A conexão com o banco já é feita dentro da classe.
$userModel = new User();
$instituicaoModel = new Instituicao();
$processoModel = new Processo();

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
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">   
    <script src="https://www.gstatic.com/charts/loader.js"></script> 
    <title>Menu</title>
    <style>
        h1 {
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }

        .welcome {
            font-size: 11px;
        }
    </style>
    <?php include 'dash.php'; ?>    
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
    ?>    
    
    <div class="wrapper">
        <?php include 'menuAberto.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Dashboard
                </h1>
            </div>
            <!-- Início do Conteúdo -->
            <div class="row">
                <div id="statusPrestacao" style="max-width:700px; height:400px" class="col d-flex justify-content-center"></div>
                <div id="columnchart_values" style="width: 900px; height:400px;" class="col d-flex justify-content-center"></div>
            </div>
            <!--
            <div>
            Total <?= $total ?><br>
            Aguardando Entrega <?= $aguardando ?><br>
            Entregue <?= $entregue ?><br>
            Análise da Execução <?= $aEx ?><br>
            Análise Financeira <?= $aFin ?><br>
            Pendências na Análise <?= $pend ?><br>
            Análise Financeira Concluída <?= $aFinConc ?><br>
            Concluído <?= $conclude ?><br>
            </div>
            -->
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