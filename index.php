<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Logs;
use Source\User;

$userModel = new User();
$logModel = new Logs();

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
    <title>Gestão de Ajustes</title>
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>    
</head>
<body>
    <div class="container"> 
        <h1 class="text-center">Sistema de Gestão de Ajustes</h1>
        <p></p>
    </div>
    <br /><br /><br /><br />
    <div class="text-center">      
        <div class="container">
            <?php
            if(isset($_REQUEST["validate"]) && $_REQUEST["validate"] == true)
            {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                {
                    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                    if(!empty($postData['matricula']))
                    {
                        if($user = $userModel->loginIn($postData))
                        {
                            $_SESSION['user_id'] = $user->id;
                            $_SESSION['matricula'] = $user->matricula;
                            $_SESSION['nome'] = $user->nome;
                            $_SESSION['perfil'] = $user->perfil;                                                      
                            if($_SESSION['perfil'] == "adm")
                            {
                                $_SESSION['adm'] = true;
                            }
                            else
                            {
                                $_SESSION['adm'] = false;
                            }
                            
                            if($user->senha == md5("pmsbc123"))
                            {
                                header("Location:alterarsenha.php");
                            }
                            else
                            {                            
                                $acao = "Realizou login";
                                $log = $logModel->save([
                                'usuario' => $_SESSION['matricula'],
                                'acao' => $acao
                                ]);
                                $_SESSION['flag'] = true;
                                header("Location:dashboard.php");
                            }                       
                        }
                        else
                        {
                            session_unset();
                            echo "<script>alert('Matricula não encontrada no banco de dados. Verifique se está digitando corretamente e caso esteja, comunique a Secretaria de Educação');</script>";
                            $loginInvalid = "is-invalid";                        
                        }
                    }
                    else
                    {
                        echo "<script>alert('Digite a Matrícula e a Senha');</script>";
                        $loginInvalid = "is-invalid";
                    }
                }
            }
            else
            {   
                session_unset();
            }           
        ?>                
            <form method="post" action="?validate=true" name="loginForm" class="form-group mx-auto">
                <center>
                    <div class="w-25">                        
                        <div class="form-floating mb-3">
                            <input type="text" name="matricula" class="form-control <?php echo $loginInvalid; ?>" id="floatingMatricula" />
                            <label for="floatingMatricula">Matrícula</label>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="form-floating mb-3">
                            <input type="password" name="senha" class="form-control <?php echo $loginInvalid; ?>" id="floatingPassword" placeholder="Password" />
                            <label for="floatingPassword">Senha</label>
                        </div>
                    </div>
                    <div class="w-25 d-grid">
                        <input type="submit" class="btn btn-primary btn-lg" value="Entrar" /><br />
                        <button type="button" class="btn btn-outline-warning btn-lg" onclick="location.href='esqueciminhasenha.php'">Esqueci minha senha</button>
                    </div>
                </center>
            </form>            
        </div>          
    </div>

    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>

    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>