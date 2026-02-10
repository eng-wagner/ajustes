<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\User;
use Source\Models\Logs;

$userModel = new User();
$logModel = new Logs();

$timezone = new DateTimeZone("America/Sao_Paulo");

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
    <title>Gerenciamento</title>
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
    ?>           

    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                Logs de Ação
                </h1>
            </div>
            <!-- Início do Conteúdo  -->
            <div class="row">
                <table class="table table-sm table-striped table-hover m-auto">
                    <thead>
                        <tr class="text-center align-middle">
                            <th class="col w-auto fw-semibold">Id</th>
                            <th class="col w-auto fw-semibold">Matricula</th>                        
                            <th class="col w-auto fw-semibold">Ação</th>
                            <th class="col w-auto fw-semibold">Data Hora</th>                                        
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $logs = $logModel->all();
                        if($logs)
                        {
                            foreach($logs as $log){
                                $logId = $log->id;
                                $matUsuario = $log->usuario;
                                $logAcao = $log->acao;
                                $dataHora = $log->hora;
            
                                $horaAcao = new DateTime($dataHora,$timezone);
                                $horaAcao = $horaAcao->format('d/m/Y H:i:s');
                                
                                echo '<tr class="fw-lighter align-middle">';
                                echo '<td scope="row" class="text-center">' . $logId . '</td>';                            
                                echo '<td class="text-center">'. $matUsuario . '</td>';
                                echo '<td class="text-center">' . $logAcao . '</td>';
                                echo '<td class="">' . $horaAcao . '</td>';                            
                                echo '</tr>';                               
            
                            }
                        }        
                        else
                        {
                            echo "<script>alert('Não foram encontrados resultados');</script>";
                            echo '<h4 class="text-center">A busca não retornou resultados, busque pelo nome de alguma escola.</h4>';
                        }                                          
                        ?>
                    </tbody>
                </table>
            </div>
            <!-- Fim do Conteúdo  -->              
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