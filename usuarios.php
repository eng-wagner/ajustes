<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\User;
use Source\Models\Local;
use Source\Models\Logs;

$userModel = new User();

// Verifica se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Cria uma instância do nosso modelo de Usuário.
// A conexão com o banco já é feita dentro da classe.

$localModel = new Local();
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
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    <title>Usuários</title>
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
        header("Location:index.php");
    }

    if ($_SESSION['perfil'] =! 'adm') {
        header("Location:dashboard.php");
    }
        
    $loggedUser = $userModel->findById($_SESSION['user_id']);
        
    if ($loggedUser) {
        $userName = $loggedUser->nome;
        $perfil = $loggedUser->perfil;
    }
    else 
    {
        header("Location: index.php?status=sessao_invalida");
        exit();
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
                    Usuários
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
                        if (!empty($postData['nome']) && !empty($postData['matricula'])) {
                            if($userModel->save($postData))
                            {
                                $acao = "Cadastrou um novo usuário (" . $postData['nome'] . ")";
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);                                    
                            };
                            header("Location: usuarios.php?status=saved");
                            exit(); 
                        } 
                        else 
                        {
                            echo "<script>alert('Erro ao cadastrar registro!');</script>";
                        }
                    }
                }

                if (isset($_REQUEST['update']) && $_REQUEST['update'] == true) 
                {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                    {
                        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        if (!empty($postData['nome'])) 
                        {
                            if($userModel->save($postData))
                            {
                                $acao = "Atualizou o usuário de id " . $postData['idUser'];
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                            };
                            header("Location: usuarios.php?status=saved");
                            exit(); 
                        } 
                        else 
                        {
                            echo "<script>alert('Erro ao atualizar registro!');</script>";
                        }
                    }
                }   

                if (isset($_GET['desativar']) && $_GET['desativar'] == true) 
                {
                    $desativar = $userModel->deactivate($_GET['idUser']);                    
                    if ($desativar) 
                    {
                        $acao = "Desativou o usuário de id " . $_GET['idUser'];
                        $log = $logModel->save([
                            'usuario' => $_SESSION['matricula'],
                            'acao' => $acao
                        ]);                       
                        header('Location:usuarios.php?status=saved');
                        exit();
                    } 
                    else 
                    {
                        echo "<script>alert('Erro ao atualizar registro!');</script>";
                    }
                }

                if (isset($_GET['reativar']) && $_GET['reativar'] == true) 
                {
                    $ativar = $userModel->activate($_GET['idUser']);
                    if ($ativar)
                    {
                        $acao = "Reativou o usuário de id " . $_GET['idUser'];
                        $log = $logModel->save([
                            'usuario' => $_SESSION['matricula'],
                            'acao' => $acao
                        ]);                       
                        header('Location:usuarios.php?status=saved');
                        exit();
                    }
                    else 
                    {
                        echo "<script>alert('Erro ao atualizar registro!');</script>";
                    }
                }

                if (isset($_GET['renewPass']) && $_GET['renewPass'] == true) 
                {
                    $renovar = $userModel->renewPass($_GET['idUser']);
                    if ($renovar)
                    {
                        $acao = "Resetou a senha o usuário de id " . $_GET['idUser'];
                        $log = $logModel->save([
                            'usuario' => $_SESSION['matricula'],
                            'acao' => $acao
                        ]);                       
                        header('Location:usuarios.php?status=saved');
                        exit();
                    }
                    else 
                    {
                        echo "<script>alert('Erro ao atualizar registro!');</script>";
                    }
                }

                if (isset($_GET['new']) && $_GET['new'] == true) 
                {
                ?>
                    <div class="container">
                        <br />
                        <form method="post" action="?input=true" name="meuForm" class="form-group mx-auto">
                            <div class="row">
                                <h4 class="text-center">Novo Usuário</h4>
                                <br /><br />
                                <div class="col offset-3">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-matricula">Matricula</span>
                                        <input type="text" name="matricula" class="w-50 col-9 form-control" aria-describedby="inputGroup-matricula" minlength="5" maxlength="5" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-dv">Dígito</span>
                                        <input type="text" name="dv" class="w-50 col-9 form-control" aria-describedby="inputGroup-dv" minlength="1" maxlength="1" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-nome">Nome</span>
                                        <input type="text" name="nome" class="w-50 col-9 form-control" aria-describedby="inputGroup-nome" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-funcao">Função</span>
                                        <input type="text" name="funcao" class="w-50 col-9 form-control" aria-describedby="inputGroup-funcao" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-local">Local de Exercício</span>
                                        <select name="localId" class="form-select col-9" id="inputGroup-local" required>
                                            <option value="" disabled="disabled" selected>Selecione...</option>
                                            <?php
                                            $locais = $localModel->all();
                                            foreach ($locais as $local):
                                                $idLocal = $local->id;
                                                $sigla = $local->sigla;
                                                $nomeLocal = $local->nome_local;
                                                echo '<option value="' . $idLocal . '">' . $sigla . " - " . $nomeLocal . '</option>';
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-perfil">Perfil</span>
                                        <input type="text" name="perfil" class="w-50 col-9 form-control" aria-describedby="inputGroup-perfil" minlength="3" maxlength="3" required />
                                    </div>

                                    <p class="text-end">
                                        <input type="submit" class="btn btn-success" value="Cadastrar" />
                                        <button type="button" class="btn btn-danger" onclick="location.href='usuarios.php'">Voltar</button>
                                    </p>
                                </div>
                                <div class="col-3"></div>
                            </div>
                        </form>
                    </div>
                    <?php
                } elseif (isset($_GET['edit']) && $_GET['edit'] == true) {                    
                    $usuario = $userModel->findById($_GET['idUser']);
                    if($usuario)
                        {
                        $usuarioId = $usuario->id;
                        $usuarioMat = $usuario->matricula;
                        $usuarioDv = $usuario->dv;
                        $usuarioNom = $usuario->nome;
                        $usuarioLocal = $usuario->id_local;
                        $usuarioFuncao = $usuario->funcao;
                        $usuarioAtivo = $usuario->ativo;
                        $usuarioPerfil = $usuario->perfil;
                    }                                        
                    ?>

                    <div class="container">
                        <br />
                        <form method="post" action="?update=true" name="meuForm" class="form-group mx-auto">
                            <input type="hidden" name="idUser" value="<?= $usuarioId ?>" />
                            <div class="row">
                                <br /><br />
                                <div class="col offset-3">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-nome">Nome</span>
                                        <input type="text" name="nome" value="<?= $usuarioNom; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-nome" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-funcao">Função</span>
                                        <input type="text" name="funcao" value="<?= $usuarioFuncao; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-funcao" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-local">Local de Exercício</span>
                                        <select name="localId" class="form-select col-9" id="inputGroup-local" required>
                                            <option value="" disabled="disabled">Selecione...</option>
                                            <?php
                                            $locais = $localModel->all();
                                            foreach($locais as $local):                                               
                                                $idLocal = $local->id;
                                                $sigla = $local->sigla;
                                                $nomeLocal = $local->nome_local;
                                                if ($idLocal == $usuarioLocal) {
                                                    echo '<option value="' . $idLocal . '" selected>' . $sigla . " - " . $nomeLocal . '</option>';
                                                } else {
                                                    echo '<option value="' . $idLocal . '">' . $sigla . " - " . $nomeLocal . '</option>';
                                                }
                                            endforeach;                                                
                                            ?>
                                        </select>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-inep">Perfil</span>
                                        <input type="text" name="perfil" value="<?= $usuarioPerfil; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-inep" maxlength="3" required />
                                    </div>

                                    <p class="text-end">
                                        <input type="submit" class="btn btn-success" value="Atualizar" />
                                        <button type="button" class="btn btn-warning" onclick="location.href='?renewPass=true&idUser=<?= $usuarioId ?>'">Renovar Senha</button>
                                        <?php
                                        if ($usuarioAtivo == 1) {
                                        ?>
                                            <button type="button" class="btn btn-secondary" onclick="location.href='?desativar=true&idUser=<?= $usuarioId ?>'">Desativar</button>
                                        <?php
                                        } else {
                                        ?>
                                            <button type="button" class="btn btn-primary" onclick="location.href='?reativar=true&idUser=<?= $usuarioId ?>'">Reativar</button>
                                        <?php
                                        }
                                        ?>
                                        <button type="button" class="btn btn-danger" onclick="location.href='usuarios.php'">Voltar</button>
                                    </p>
                                </div>
                                <div class="col-3"></div>
                            </div>
                        </form>
                    </div>
                <?php
                }                    
                else {
                    ?>

                    <div class="row">
                        <table class="table table-sm table-striped table-hover m-auto">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th class="col w-auto fw-semibold">Id</th>
                                    <th class="col w-auto fw-semibold">Matricula</th>
                                    <th class="col w-auto fw-semibold">Dígito</th>
                                    <th class="col w-auto fw-semibold">Nome</th>
                                    <th class="col w-auto fw-semibold">Local</th>
                                    <th class="col w-auto fw-semibold">Função</th>
                                    <th class="col w-auto fw-semibold">Perfil</th>
                                    <th class="col w-auto fw-semibold">Ativo?</th>
                                    <th class="col w-auto fw-semibold">Editar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $users = $userModel->all();
                                if ($users) {
                                    foreach ($users as $usuario):                                 
                                        $userId = $usuario->id;
                                        $userMat = $usuario->matricula;
                                        $userDv = $usuario->dv;
                                        $userNom = $usuario->nome;
                                        $userLocal = $usuario->id_local;
                                        $userFuncao = $usuario->funcao;
                                        $userAtivo = $usuario->ativo;
                                        $userPerfil = $usuario->perfil;

                                        if ($userAtivo == true) {
                                            $funcAtivo = "SIM";
                                            $backAtivo = "";
                                        } else {
                                            $funcAtivo = "NÃO";
                                            $backAtivo = " table-danger";
                                        }
                                        echo '<tr class="fw-lighter align-middle' . $backAtivo . '">';
                                        echo '<td scope="row" class="text-center">' . $userId . '</td>';
                                        echo '<td class="text-center">' . $userMat . '</td>';
                                        echo '<td class="text-center">' . $userDv . '</td>';
                                        echo '<td class="">' . $userNom . '</td>';
                                        
                                        $local = $localModel->findById($userLocal);                                        
                                        if ($local) {
                                            $sigla = $local->sigla;
                                            echo '<td class="">' . $sigla . '</td>';
                                        }
                                        echo '<td class="">' . $userFuncao . '</td>';
                                        echo '<td class="">' . $userPerfil . '</td>';
                                        echo '<td class="">' . $funcAtivo . '</td>';
                                        echo '<td class="text-center">';
                                        echo '<a href="?edit=true&idUser=' . $userId . '">Editar Dados</a><br />';
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