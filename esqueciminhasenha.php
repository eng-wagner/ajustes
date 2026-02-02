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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    <title>Esqueci Minha Senha</title>
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>    
</head>
<body>
    <div class="container">
        <h1 class="text-center">Esqueci Minha Senha</h1>
        <p></p>
    </div>
    <br /><br /><br /><br />
    <div class="text-center">      
        <div class="container">
            <?php            
            if(isset($_REQUEST["update"]) && $_REQUEST["update"] == true){

                $stmt = Connect::getInstance()->prepare("SELECT matricula, dv, admissao FROM usuarios WHERE matricula = :matricula");
                $stmt->bindParam('matricula', $_POST['matric']);                   
                
                if($stmt->execute())
                {
                    if($user = $stmt->fetch())
                    {                            
                        $userLogin = $user->matricula;
                        $userDV = $user->dv;
                        $userAdmissao = $user->admissao;

                        $loginValidation = "is-valid";
                        $dvValidation = $userDV == $_POST['dv'] ? "is-valid" : "is-invalid";
                        $daValidation = $userAdmissao == $_POST['dataAdmissao'] ? "is-valid" : "is-invalid";

                        if($userLogin == $_POST['matric'] && $userDV == $_POST['dv'] && $userAdmissao == $_POST['dataAdmissao'])
                        {
                            if(isset($_POST['newPass']) && $_POST['newPass'] != "0" && $_POST['newPass'] != null && $_POST['newPass'] == $_POST['newPassRp']){
                                $nPassValidation = "is-valid";                                
                                $hashNewPass = md5($_POST['newPass']);
                                
                                $sql = Connect::getInstance()->prepare("UPDATE usuarios SET senha = :novaSenha WHERE matricula = :matricula");
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
                        $daValidation = "is-invalid";
                    }
                }
                else
                {                    
                    $loginValidation = "is-invalid";
                    $dvValidation = "is-invalid";
                    $daValidation = "is-invalid";                       
                }
            }
            else
            {                
                session_unset();
            }                                   
        ?>
                
            <form method="post" action="?update=true" name="updateForm" class="form-group mx-auto">
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
                            <input type="date" name="dataAdmissao" class="form-control <?= $daValidation; ?>" id="floatingDA" placeholder="Data de Admissão" value="<?= $_POST['dataAdmissao'] ?? ''; ?>" />
                            <div class="invalid-feedback">
                                Data de Admissão não confere.
                            </div>
                            <label for="floatingDA">Data de Admissão</label>  
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
                        <button type="voltar" class="btn btn-danger btn-lg" onclick="location.href='index.php'">Voltar</button>
                    </div>
                </center>
            </form>            
        </div>           
    </div>



    <footer style="position: fixed; left: 0; bottom: 0; width: 100%; text-align: center;">
        <font color="#575756"><small>© Copyright - Secretaria de Educação - São Bernardo do Campo | 2024. Todos os Direitos Reservados.</small></font>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>