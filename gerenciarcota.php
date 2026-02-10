<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\Logs;
use Source\Models\User;
use Source\Models\Instituicao;
use Source\Models\ItensCota;

$userModel = new User();

// Verifica se o usuário está logado
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
$itensModel = new ItensCota();

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
    <title>Gerenciar Itens da Cota</title>
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
                    Gerenciar Itens da Cota
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
                            if($itensModel->save($postData))
                            {
                                $acao = "Cadastrou um novo documento (" . $postData['docNome'] . ")";
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                header("Location: gerenciarcota.php?status=saved");
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
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
                    {
                        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        /*
                        // ===================================================
                        //            INÍCIO DO BLOCO DE DEBUG
                        // ===================================================

                        echo "<h3>Modo de Depuração Ativado</h3>";
                        echo "Recebi os dados do formulário via POST. Tentando salvar...<br>";
                        
                        // Chamamos o método save e guardamos o resultado (true ou false)
                        $resultadoDoSave = $itensModel->save($postData);

                        echo "O resultado do método save() foi: ";
                        var_dump($resultadoDoSave); // Isso vai imprimir bool(true) ou bool(false)

                        if ($resultadoDoSave) {
                            echo "<p style='color:green;'><b>SUCESSO:</b> O método save() retornou true. O redirecionamento deveria acontecer.</p>";
                            //header("Location: ?status=saved");
                        } else {
                            echo "<p style='color:red;'><b>FALHA:</b> O método save() retornou false. O bloco com o header() foi ignorado.</p>";
                            echo "<p><b>Próximo passo:</b> Verifique o método save() na sua classe de modelo (ex: ItensCota.php). Provavelmente há um erro na query SQL (INSERT ou UPDATE).</p>";
                        }

                        die("<br>-- Fim da Depuração --"); // Paramos o script aqui para analisar o resultado.

                        // ===================================================
                        //             FIM DO BLOCO DE DEBUG
                        // ===================================================
                        */
                        if (!empty($postData['idDoc'])) 
                        {
                            if($itensModel->save($postData))
                            {
                                $acao = "Atualizou o documentos de id " . $postData['idDoc'];
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                header("Location: gerenciarcota.php?status=saved");
                                exit(); 
                            }
                        }
                        else 
                        {
                            echo "<script>alert('Erro ao atualizar registro!');</script>";
                        }
                    }
                }

                if (isset($_GET['desativar']) && $_GET['desativar'] == true) {
                    $desativar = $itensModel->deactivate($_GET['idDoc']);                    
                    if ($desativar) 
                    {
                        $acao = "Desativou o documento de id " . $_GET['idDoc'];
                        $log = $logModel->save([
                            'usuario' => $_SESSION['matricula'],
                            'acao' => $acao
                        ]);                       
                        header('Location:gerenciarcota.php?status=saved');
                        exit();
                    } 
                    else 
                    {
                        echo "<script>alert('Erro ao atualizar registro!');</script>";
                    }                    
                }

                if (isset($_GET['reativar']) && $_GET['reativar'] == true) {
                    $reativar = $itensModel->activate($_GET['idDoc']);                    
                    if ($reativar) 
                    {
                        $acao = "Reativou o documento de id " . $_GET['idDoc'];                        
                        $log = $logModel->save([
                            'usuario' => $_SESSION['matricula'],
                            'acao' => $acao
                        ]);
                        header('Location:gerenciarcota.php?status=saved');
                        exit();
                    } else 
                    {
                        echo "<script>alert('Erro ao atualizar registro!');</script>";
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
                                        <span class="input-group-text col-3" id="inputGroup-nome">Item da Cota</span>
                                        <input type="text" name="docNome" class="w-50 col-9 form-control" aria-describedby="inputGroup-nome" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-funcao">Ordem</span>
                                        <input type="text" name="docCh" class="w-50 col-9 form-control" aria-describedby="inputGroup-funcao" required />
                                    </div>                                    
                                    <p class="text-end">
                                        <input type="submit" class="btn btn-success" value="Cadastrar" />
                                        <button type="button" class="btn btn-danger" onclick="location.href='gerenciarcota.php'">Voltar</button>
                                    </p>
                                </div>
                                <div class="col-3"></div>
                            </div>
                        </form>
                    </div>
                    <?php
                } elseif (isset($_GET['edit']) && $_GET['edit'] == true) {
                    $doc = $itensModel->findById($_GET['idDoc']);                    
                    if ($doc)
                    {                        
                        $docId = $doc->id;
                        $docNome = $doc->documentos;
                        $docOrdem = $doc->chName;
                        $docAtivo = $doc->ativo;
                    ?>
                        <div class="container">
                            <br />
                            <form method="post" action="?update=true" name="meuForm" class="form-group mx-auto">
                                <input type="hidden" name="idDoc" value="<?= $docId ?>" />
                                <div class="row">
                                    <br /><br />
                                    <div class="col offset-3">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-documento">Item da Cota</span>
                                            <input type="text" name="docNome" value="<?= $docNome; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-documento" required />
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-ordem">Ordem</span>
                                            <input type="text" name="docCh" value="<?= $docOrdem; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-ordem" required />
                                        </div>

                                        <p class="text-end">
                                            <input type="submit" class="btn btn-success" value="Atualizar" />                                                
                                            <?php
                                            if ($docAtivo == 1) {
                                            ?>
                                                <button type="button" class="btn btn-secondary" onclick="location.href='?desativar=true&idDoc=<?= $docId ?>'">Desativar</button>
                                            <?php
                                            } else {
                                            ?>
                                                <button type="button" class="btn btn-primary" onclick="location.href='?reativar=true&idDoc=<?= $docId ?>'">Reativar</button>
                                            <?php
                                            }
                                            ?>
                                            <button type="button" class="btn btn-danger" onclick="location.href='gerenciarcota.php'">Voltar</button>
                                        </p>
                                    </div>
                                    <div class="col-3"></div>
                                </div>
                            </form>
                        </div>
                    <?php
                        
                    }
                } else {
                    ?>

                    <div class="row">
                        <table class="table table-sm table-striped table-hover m-auto">
                            <thead>
                                <tr class="text-center align-middle">
                                    <th class="col w-auto fw-semibold">Id</th>
                                    <th class="col w-auto fw-semibold">Item da Cota</th>
                                    <th class="col w-auto fw-semibold">Ordem</th>
                                    <th class="col w-auto fw-semibold">Ativo</th>
                                    <th class="col w-auto fw-semibold">Editar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $docs = $itensModel->all();
                                if($docs)
                                {
                                    foreach($docs as $doc):
                                        $idDoc = $doc->id;
                                        $documentosCota = $doc->documentos;
                                        $chName = $doc->chName;
                                        $docAtivo = $doc->ativo;
                                        
                                        if ($docAtivo == true) {
                                            $documentoAtivo = "SIM";
                                            $backAtivo = "";
                                        } else {
                                            $documentoAtivo = "NÃO";
                                            $backAtivo = " table-danger";
                                        }
        
                                        echo '<tr class="fw-lighter align-middle '. $backAtivo . '">';
                                        echo '<td scope="row" class="text-center">' . $idDoc . '</td>';
                                        echo '<td class="">' . $documentosCota . '</td>';
                                        echo '<td class="text-center">' . $chName . '</td>';
                                        echo '<td class="">' . $documentoAtivo . '</td>';
                                        echo '<td class="text-center">';
                                        echo '<a href="?edit=true&idDoc=' . $idDoc . '">Editar Dados</a><br />';
                                        echo '</td>';
                                        echo '</tr>';
                                    endforeach;
                                }                                                       
                                else {
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