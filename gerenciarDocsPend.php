<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\Documento;
use Source\Models\Logs;
use Source\Models\User;
use Source\Models\Instituicao;

// Criar instâncias do modelo.
// A conexão com o banco já é feita dentro da classe.
$userModel = new User();

if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}

$loggedUser = $userModel->findById($_SESSION['user_id']);
if ($loggedUser) {
    $userName = $loggedUser->nome;
    $perfil = $loggedUser->perfil;
} else {
    // Se o usuário logado não for encontrado, redireciona para a página de login
    session_destroy();
    header("Location: index.php?status=sessao_invalida");
    exit();
}

$logModel = new Logs();
$instituicaoModel = new Instituicao();
$documentoModel = new Documento();

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
    <title>Gerenciar Documentos</title>
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
                    Gerenciar Documentos
                </h1>
            </div>
            <!-- Início do Conteúdo  -->

            <div class="container-lg">

                <hr>
                <?php

                if (isset($_REQUEST['input']) && $_REQUEST['input'] == true) {      
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                    {
                        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        if (!empty($postData['docNome'])) 
                        {
                            if($documentoModel->save($postData))
                            {
                                $acao = "Cadastrou um novo documento (" . $postData['docNome'] . ")";
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                header("Location: gerenciarDocsPend.php?status=saved");
                                exit(); 
                            }
                        }
                        else 
                        {
                            echo "<script>alert('Erro ao atualizar registro!');</script>";
                        }
                    }
                }

                if (isset($_REQUEST['update']) && $_REQUEST['update'] == true) 
                {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                    {
                        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        if (!empty($postData['idDoc'])) 
                        {
                            if($documentoModel->save($postData))
                            {
                                $acao = "Atualizou o documento de id " . $postData['idDoc'];
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                header("Location: gerenciarDocsPend.php?status=saved");
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
                                <h4 class="text-center">Novo Documento</h4>
                                <br /><br />
                                <div class="col offset-3">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-documento">Documento</span>
                                        <input type="text" name="docNome" value="" class="w-50 col-9 form-control" aria-describedby="inputGroup-documento" required />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" name="checkTc" value="1" type="checkbox" role="switch" id="flexSwitchCheckDefault">
                                        <label class="form-check-label" for="flexSwitchCheckDefault">Termo de Colaboração</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" name="checkPdde" value="1" type="checkbox" role="switch" id="flexSwitchCheckChecked">
                                        <label class="form-check-label" for="flexSwitchCheckChecked">PDDE</label>
                                    </div>                                    
                                    <p class="text-end">
                                        <input type="submit" class="btn btn-success" value="Cadastrar" />
                                        <button type="button" class="btn btn-danger" onclick="location.href='gerenciarDocsPend.php'">Voltar</button>
                                    </p>
                                </div>
                                <div class="col-3"></div>
                            </div>
                        </form>
                    </div>
                    <?php
                } elseif (isset($_GET['edit']) && $_GET['edit'] == true) {
                    $doc = $documentoModel->findById($_GET['idDoc']);                                        
                    if ($doc)
                    {
                        $docId = $doc->id;
                        $docNome = $doc->documento;
                        $docTc = $doc->tc;
                        $docPdde = $doc->pdde;        
                        
                        if($docTc == 1) {$chTc = "checked";} else {$chTc = "";}
                        if($docPdde == 1) {$chPdde = "checked";} else {$chPdde = "";}
                        ?>
                        <div class="container">
                            <br />
                            <form method="post" action="?update=true" name="meuForm" class="form-group mx-auto">
                                <input type="hidden" name="idDoc" value="<?= $docId ?>" />
                                <div class="row">
                                    <br /><br />
                                    <div class="col offset-3">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-documento">Documento</span>
                                            <input type="text" name="docNome" value="<?= $docNome; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-documento" required />
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" name="checkTcU" value="1" type="checkbox" role="switch" id="checkTermo" <?= $chTc ?>>
                                            <label class="form-check-label" for="checkTermo">Termo de Colaboração</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" name="checkPddeU" type="checkbox" value="1" role="switch" id="checkPDDE" <?= $chPdde ?>>
                                            <label class="form-check-label" for="checkPDDE">PDDE</label>
                                        </div>
                                        

                                        <p class="text-end">
                                            <input type="submit" class="btn btn-success" value="Atualizar" />                                                
                                            <button type="button" class="btn btn-danger" onclick="location.href='gerenciarDocsPend.php'">Voltar</button>
                                        </p>
                                    </div>
                                    <div class="col-3"></div>
                                </div>
                            </form>
                        </div>
                    <?php
                    }
                }
                else {
                    ?>

                    <div class="row">
                        <table class="table table-sm table-striped table-hover m-auto">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th class="col w-auto fw-semibold">Id</th>                                    
                                    <th class="col w-auto fw-semibold">Documento</th>
                                    <th class="col w-auto fw-semibold">TC</th>
                                    <th class="col w-auto fw-semibold">PDDE</th>
                                    <th class="col w-auto fw-semibold">Editar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $docs = $documentoModel->all();                                
                                if ($docs) {
                                    foreach ($docs as $doc):
                                        $idDoc = $doc->id;
                                        $documentos = $doc->documento;
                                        $chTc = $doc->tc;
                                        $chPdde = $doc->pdde;                                        
                                        
                                        if($chTc == 0 && $chPdde == 0) {$backAtivo = "table-danger";} else {$backAtivo = "";}


                                        echo '<tr class="fw-lighter align-middle '. $backAtivo . '">';
                                        echo '<td scope="row" class="text-center">' . $idDoc . '</td>';
                                        echo '<td class="">' . $documentos . '</td>';
                                        echo '<td class="text-center">' . $chTc . '</td>';
                                        echo '<td class="text-center">' . $chPdde . '</td>';                                        
                                        echo '<td class="text-center">';
                                        echo '<a href="?edit=true&idDoc=' . $idDoc . '">Editar Dados</a><br />';
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
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>

    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
<?php
ob_flush();
?>