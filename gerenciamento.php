<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";
use Source\Database\Connect;


?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

    if($_SESSION['flag'] == false){
        header("Location:index.php");
    }

    if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
        $_SESSION['flag'] = false;
        session_unset();
        header("Location:index.php");
    }

    if($_SESSION['perfil'] =! 'adm'){
        header("Location:dashboard.php");
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
                Gerenciamento
                </h1>
            </div>
            
            <hr>
            <p class="text-center">
                <a href="actionlogs.php" class="link-body-emphasis link-underline-dark link-offset-3">Acessar Logs</a><br /><br />
                <a href="usuarios.php" class="link-body-emphasis link-underline-dark link-offset-3">Cadastro de Usuários</a><br /><br />
                <a href="instituicoes.php" class="link-body-emphasis link-underline-dark link-offset-3">Cadastro de Instituições</a><br /><br />
                <a href="contabilidades.php" class="link-body-emphasis link-underline-dark link-offset-3">Cadastro de Contabilidades</a><br /><br />
                <a href="gerenciarcota.php" class="link-body-emphasis link-underline-dark link-offset-3">Gerenciar Itens da Cota</a><br /><br />
                <a href="gerenciarDocsPend.php" class="link-body-emphasis link-underline-dark link-offset-3">Gerenciar Documentos de Pendência</a><br /><br />                
            </p>            
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
<?php
ob_flush();
?>