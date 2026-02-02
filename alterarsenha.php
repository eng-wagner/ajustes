<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Database\Connect;

$pdo = Connect::getInstance();

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
    <title>Alterar Senha</title>
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>    
</head>
<body>
    <div class="container"> 
        <h1 class="text-center">Alterar Senha</h1>
        <p></p>
    </div>
    <br /><br /><br /><br />
    <div class="text-center">      
        <div class="container">
            <?php            
            if(isset($_REQUEST["update"]) && $_REQUEST["update"] == true){
                $hashPass = md5($_POST['lastPass']);
                
                //$hash = md5($_POST['newPass']);
                //$hash = md5($_POST['newPassRp']);

                $stmt = $pdo->prepare("SELECT matricula, dv, senha FROM usuarios WHERE matricula = :matricula");
                $stmt->bindParam('matricula', $_POST['matric']);                   
                
                if($stmt->execute())
                {
                    if($user = $stmt->fetch())
                    {                            
                        $userLogin = $user->matricula;
                        $userDV = $user->dv;
                        $userPass = $user->senha;

                        $loginValidation = "is-valid";
                        $dvValidation = $userDV == $_POST['dv'] ? "is-valid" : "is-invalid";
                        $passValidation = $userPass == $hashPass ? "is-valid" : "is-invalid";

                        if($userLogin == $_POST['matric'] && $userDV == $_POST['dv'] && $userPass == $hashPass)
                        {
                            if(isset($_POST['newPass']) && $_POST['newPass'] != "0" && $_POST['newPass'] != null && $_POST['newPass'] == $_POST['newPassRp']){
                                $nPassValidation = "is-valid";                                
                                $hashNewPass = md5($_POST['newPass']);
                                
                                $sql = $pdo->prepare("UPDATE usuarios SET senha = :novaSenha WHERE matricula = :matricula");
                                $sql->bindParam(':novaSenha', $hashNewPass);  
                                $sql->bindParam('matricula', $_POST['matric']);  
                                $sql->execute();
                                
                                header('Location:pddePC.php');
                            }
                            else
                            {                                
                                $nPassValidation = "is-invalid";
                            }
                        }
                    }
                    else
                    {
                        $loginValidation = "is-invalid";                        
                        $dvValidation = "is-invalid";
                        $passValidation = "is-invalid";
                    }
                }
                else
                {                    
                    $loginValidation = "is-invalid";
                    $dvValidation = "is-invalid";
                    $passValidation = "is-invalid";                       
                }
            }
            else
            {                
                session_unset();
                ?>
                <a data-bs-toggle="modal" data-bs-target="#alterarSenhaModal" id="modalAlterar"></a>
                <script language="javascript" type="text/javascript">
                    window.onload = function()
                    {                                                
                        document.getElementById("modalAlterar").click();
                    }
                </script>
        
                <!-- Modal primeiroacesso -->
                <div class="modal fade" id="alterarSenhaModal" tabindex="-1" aria-labelledby="alterarSenhaModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5 text-center" id="alterarSenhaModalLabel">Sua senha precisa ser alterada</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            
                            <div class="modal-body">
                                <button type="button" class="btn btn-success" style="width:25%;" data-bs-dismiss="modal">OK</button> 
                            </div>                    
                            
                        </div>
                    </div>
                </div>  
                <?php
            }                                   
            
            //var_dump($_POST);
            
        ?>
                
            <form method="post" action="?update=true" name="loginForm" class="form-group mx-auto">
                <center>
                    <div class="w-25">                        
                        <div class="form-floating mb-3">
                            <input type="text" name="matric" class="form-control <?= $loginValidation; ?>" id="floatingMatricula" placeholder="Insira a Matrícula" maxlength="5" value="<?= $_POST['matric'] ?? ''; ?>"/>
                            <div class="invalid-feedback">
                                Matrícula não encontrada no banco de dados.
                            </div>
                            <label for="floatingMatricula" >Insira a Matricula</label>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="form-floating mb-3">
                            <input type="text" name="dv" class="form-control <?= $dvValidation; ?>" id="floatingDV" placeholder="Insira o Dígito" maxlength="1" value="<?= $_POST['dv'] ?? ''; ?>"/>
                            <div class="invalid-feedback">
                                O dígito não confere com a matrícula do usuário.
                            </div>
                            <label for="floatingDV">Insira o Dígito</label>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="form-floating mb-3">
                            <input type="password" name="lastPass" class="form-control <?= $passValidation; ?>" id="floatingPassword" placeholder="Senha Antiga" value="<?= $_POST['lastPass'] ?? ''; ?>" />
                            <div class="invalid-feedback">
                                Senha não confere.
                            </div>
                            <label for="floatingPassword">Senha Antiga</label>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="form-floating mb-3">
                            <input type="password" name="newPass" class="form-control <?= $nPassValidation; ?>" id="floatingNewPass" placeholder="Nova Senha" value="<?= $_POST['newPass'] ?? ''; ?>"/>                            
                            <label for="floatingNewPass">Nova Senha</label>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="form-floating mb-3">
                            <input type="password" name="newPassRp" class="form-control <?= $nPassValidation; ?>" id="floatingNewPassRp" placeholder="Repita a Nova Senha" value="<?= $_POST['newPassRp'] ?? ''; ?>"/>
                            <div class="invalid-feedback">
                                As senhas digitadas não conferem.
                            </div>
                            <label for="floatingNewPassRp">Repita a Nova Senha</label>
                            
                        </div>
                    </div>
                    <div class="w-25 d-grid">
                        <input type="submit" class="btn btn-success btn-lg" value="Alterar" /><br />
                        <button type="button" class="btn btn-danger btn-lg" onclick="location.href='index.php'">Voltar</button>
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