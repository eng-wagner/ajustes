<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Logs;
use Source\Contabilidade;
use Source\User;

// Criar instâncias do modelo.
// A conexão com o banco já é feita dentro da classe.
$userModel = new User();
$contabModel = new Contabilidade();
$logModel = new Logs();

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
    <title>Contabilidades</title>
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

    if ($_SESSION['perfil'] = ! 'adm') {
        header("Location:dashboard.php");
    }    

    $firstName = substr($userName, 0, strpos($userName, " "));

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
                    Contabilidades
                </h1>
            </div>
            <!-- Início do Conteúdo  -->
            <div class="container-lg">
                <hr>
                <?php

                if (isset($_REQUEST['input']) && $_REQUEST['input'] == true)
                {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        if (!empty($postData['nome'])) {
                            if($contabModel->save($postData))
                            {
                                $acao = "Cadastrou um nova contabilidade (" . $postData['nome'] . ")";
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                header("Location: contabilidades.php?status=saved");
                                exit(); 
                            }
                        } 
                        else 
                        {
                            echo "<script>alert('Erro ao inserir novo registro!');</script>";
                        }
                    }
                }
                    
                if (isset($_REQUEST['update']) && $_REQUEST['update'] == true) 
                {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        if (!empty($postData['idCont'])) {
                            if($contabModel->save($postData))
                            {
                                $acao = "Atualizou a contabilidade de id " . $postData['idCont'];
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                header("Location: contabilidades.php?status=saved");
                                exit(); 
                            }
                        } 
                        else 
                        {
                            echo "<script>alert('Erro ao atualizar registro!');</script>";
                        }
                    }                    
                }

                if (isset($_GET['new']) && $_GET['new'] == true) {
                ?>
                    <div class="container">
                        <br />
                        <form method="post" action="?input=true" name="meuForm" class="form-group mx-auto">
                            <div class="row">
                                <h4 class="text-center">Nova Contabilidade</h4>
                                <br /><br />
                                <div class="col offset-3">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-nome">Contabilidade</span>
                                        <input type="text" name="nome" class="w-50 col-9 form-control" aria-describedby="inputGroup-nome" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-funcao">Telefone</span>
                                        <input type="text" name="telefone" class="w-50 col-9 form-control" aria-describedby="inputGroup-funcao" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-perfil">E-mail</span>
                                        <input type="text" name="email" class="w-50 col-9 form-control" aria-describedby="inputGroup-perfil" required />
                                    </div>
                                    <p class="text-end">
                                        <input type="submit" class="btn btn-success" value="Cadastrar" />
                                        <button type="button" class="btn btn-danger" onclick="location.href='contabilidades.php'">Voltar</button>
                                    </p>
                                </div>
                                <div class="col-3"></div>
                            </div>
                        </form>
                    </div>
                    <?php
                } elseif (isset($_GET['edit']) && $_GET['edit'] == true) {
                    $cont = $contabModel->findById($_GET['idCont']);                    
                    //var_dump($cont);
                    //die();
                    if ($cont) 
                    {                        
                        $contId = $cont->id;
                        $contNome = $cont->c_nome;
                        $contFone = $cont->c_telefone;
                        $contEmail = $cont->c_email; 
                    }
                    ?>
                            <div class="container">
                                <br />
                                <form method="post" action="?update=true" name="meuForm" class="form-group mx-auto">
                                    <input type="hidden" name="idCont" value="<?= $contId ?>" />
                                    <div class="row">
                                        <br /><br />
                                        <div class="col offset-3">
                                            <div class="input-group input-group-sm mb-2">
                                                <span class="input-group-text col-3" id="inputGroup-nome">Contabilidade</span>
                                                <input type="text" name="nome" value="<?= $contNome; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-nome" required />
                                            </div>
                                            <div class="input-group input-group-sm mb-2">
                                                <span class="input-group-text col-3" id="inputGroup-fone">Telefone</span>
                                                <input type="text" name="telefone" value="<?= $contFone; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-fone" required />
                                            </div>                                            
                                            <div class="input-group input-group-sm mb-2">
                                                <span class="input-group-text col-3" id="inputGroup-email">E-mail</span>
                                                <input type="text" name="email" value="<?= $contEmail; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-email" required />
                                            </div>

                                            <p class="text-end">
                                                <input type="submit" class="btn btn-success" value="Atualizar" />                                                
                                                <button type="button" class="btn btn-danger" onclick="location.href='contabilidades.php'">Voltar</button>
                                            </p>
                                        </div>
                                        <div class="col-3"></div>
                                    </div>
                                </form>
                            </div>
                    <?php
                                            
                } else {
                    ?>

                    <div class="row">
                        <table class="table table-sm table-striped table-hover m-auto">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th class="col w-auto fw-semibold">Id</th>                                    
                                    <th class="col w-auto fw-semibold">Nome</th>
                                    <th class="col w-auto fw-semibold">Telefone</th>
                                    <th class="col w-auto fw-semibold">E-mail</th>
                                    <th class="col w-auto fw-semibold">Editar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $contabilidades = $contabModel->all();
                                if($contabilidades)
                                {
                                    foreach ($contabilidades as $cont):
                                        $cId = $cont->id;
                                        $cNome = $cont->c_nome;
                                        $cFone = $cont->c_telefone;
                                        $cEmail = $cont->c_email;
                                                                                
                                        echo '<tr class="fw-lighter align-middle">';
                                        echo '<td scope="row" class="text-center">' . $cId . '</td>';
                                        echo '<td class="">' . $cNome . '</td>';
                                        echo '<td class="text-center">' . $cFone . '</td>';
                                        echo '<td class="">' . $cEmail . '</td>';
                                        echo '<td class="text-center">';
                                        echo '<a href="?edit=true&idCont=' . $cId . '">Editar Dados</a><br />';
                                        echo '</td>';
                                        echo '</tr>';
                                    endforeach;
                                } else {
                                    echo "<script>alert('Não foram encontrados resultados');</script>";
                                    echo '<h4 class="text-center">A busca não retornou resultados, busque pelo nome de alguma escola.</h4>';
                                }                              
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php
                }
                ?>
                <br />

            </div>
            <!-- Fim do Conteúdo  -->
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'footer.php'; ?>

    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
<?php
ob_flush();
?>