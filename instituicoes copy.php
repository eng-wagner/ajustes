<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\Logs;
use Source\Models\Contabilidade;
use Source\Models\User;
use Source\Models\Instituicao;

// Criar instâncias do modelo.
// A conexão com o banco já é feita dentro da classe.
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

$contabModel = new Contabilidade();
$logModel = new Logs();
$instituicaoModel = new Instituicao();

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
    <title>Cadastro de Instituições</title>
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
                Cadastro de Instituições
                </h1>
            </div>
            <!-- Início do Conteúdo  -->
            <div class="container-lg">
                <hr>        
                <?php
                
                if(isset($_REQUEST['input']) && $_REQUEST['input'] == true)
                {   
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        if (!empty($postData['instituicao'])) {
                            if($instituicaoModel->save($postData))
                            {
                                $acao = "Cadastrou um nova instituição (" . $postData['instituicao'] . ")";
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                header("Location: instituicoes.php?status=saved");
                                exit(); 
                            }
                        } 
                        else 
                        {
                            echo "<script>alert('Erro ao inserir novo registro!');</script>";
                        }
                    }                    
                }
                
                if(isset($_REQUEST['update']) && $_REQUEST['update'] == true )
                {
                    //var_dump($_POST);
                    //die();
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                        if (!empty($postData['idInst'])) {
                            if($instituicaoModel->save($postData))
                            {
                                $acao = "Atualizou os dados da instituição de id " . $postData['idInst'];
                                $log = $logModel->save([
                                    'usuario' => $_SESSION['matricula'],
                                    'acao' => $acao
                                ]);
                                header("Location: instituicoes.php?status=saved");
                                exit(); 
                            }
                        } 
                        else 
                        {
                            echo "<script>alert('Erro ao atualizar registro!');</script>";
                        }
                    }                    
                }

                if(isset($_GET['new']) && $_GET['new'] == true)
                {        
                    ?>
                    <div class="container">
                        <br />
                        <form method="post" action="?input=true" name="meuForm" class="form-group mx-auto">
                            <div class="row">                
                                <h4 class="text-center">Nova Instituição</h4>
                                <br /><br />
                                <div class="col offset-3">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-instituicao">Instituição</span>
                                        <input type="text" name="instituicao" class="w-50 col-9 form-control" aria-describedby="inputGroup-instituicao" required />
                                    </div>                                            
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-cnpj">CNPJ</span>
                                        <input type="text" name="cnpj" class="w-50 col-9 form-control" aria-describedby="inputGroup-cnpj" minlength="14" maxlength="14" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-end">Endereço</span>
                                        <input type="text" name="endereco" class="w-50 col-9 form-control" aria-describedby="inputGroup-end" required />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-email">E-mail</span>
                                        <input type="text" name="email" class="w-50 col-9 form-control" aria-describedby="inputGroup-email" />
                                    </div>                        
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-telefone">Telefone</span>
                                        <input type="text" name="telefone" class="w-50 col-9 form-control" aria-describedby="inputGroup-telefone" />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-inep">INEP</span>
                                        <input type="text" name="inep" class="w-50 col-9 form-control" aria-describedby="inputGroup-inep" />
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-3" id="inputGroup-contId">Contabilidade</span>
                                        <select name="contId" class="form-select col-9" id="inputGroup-contId" required>
                                            <option value="" disabled="disabled" selected>Selecione...</option>
                                            <?php
                                            $conts = $contabModel->all();
                                            if($conts)
                                            {
                                                foreach($conts as $cont):                                                
                                                    $idCont = $cont->id;                                                                                
                                                    $nomeCont = $cont->c_nome;        
                                                    echo '<option value="' . $idCont . '">' . $nomeCont . '</option>';                                                                                                         
                                                endforeach;
                                            }
                                            ?>                                                                                                  
                                        </select>    
                                    </div> 
                                    <p class="text-end">                            
                                        <input type="submit" class="btn btn-success" value="Cadastrar" />
                                        <button type="button" class="btn btn-danger" onclick="location.href='instituicoes.php'">Voltar</button>
                                    </p>                       
                                </div>
                                <div class="col-3"></div>
                            </div>
                        </form>
                    </div>
                    <?php
                }                
                
                else if(isset($_GET['edit']) && $_GET['edit'] == true)
                {        
                    $inst = $instituicaoModel->findById($_GET['idInst']);   
                    if($inst){
                        $idInst = $inst->id;
                        $nomeInst = $inst->instituicao;
                        $cnpjInst = $inst->cnpj;
                        $emailInst = $inst->email;
                        $enderecoInst = $inst->endereco;
                        $inepInst = $inst->inep;
                        $telefoneInst = $inst->telefone;                    
                        $idContInst = $inst->cont_id;   

                        ?>

                        <div class="container">
                            <br />
                            <form method="post" action="?update=true" name="meuForm" class="form-group mx-auto">
                                <input type="hidden" name="idInst" value="<?= $idInst ?>" />
                                <div class="row">                
                                    <h4 class="text-center">Atualizar Instituição</h4>
                                    <br /><br />
                                    <div class="col offset-3">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-instituicao">Instituição</span>
                                            <input type="text" name="instituicao" value="<?php echo $nomeInst; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-instituicao" required/>
                                        </div>                                            
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-cnpj">CNPJ</span>
                                            <input type="text" name="cnpj" value="<?php echo $cnpjInst; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-cnpj" minlength="14" maxlength="14" required/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-end">Endereço</span>
                                            <input type="text" name="endereco" value="<?php echo $enderecoInst; ?>" value="Rua Tiradentes, 3180 - Montanhão" class="w-50 col-9 form-control" aria-describedby="inputGroup-end" required/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-email">E-mail</span>
                                            <input type="text" name="email" value="<?php echo $emailInst; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-email" required/>
                                        </div>                        
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-telefone">Telefone</span>
                                            <input type="text" name="telefone" value="<?php echo $telefoneInst; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-telefone" required/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-inep">INEP</span>
                                            <input type="text" name="inep" value="<?php echo $inepInst; ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-inep" required/>
                                        </div>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text col-3" id="inputGroup-contId">Contabilidade</span>
                                            <select name="contId" class="form-select col-9" id="inputGroup-contId" required>
                                                <option value="" disabled="disabled">Selecione...</option>
                                                <?php
                                                $conts = $contabModel->all();                                                
                                                if($conts)
                                                {
                                                    foreach($conts as $cont):                                                    
                                                        $idCont = $cont->id;                                                                                
                                                        $nomeCont = $cont->c_nome;        
                                                        if($idCont == $idContInst){
                                                            echo '<option value="' . $idCont . '" selected>' . $nomeCont . '</option>';
                                                        } else {
                                                            echo '<option value="' . $idCont . '">' . $nomeCont . '</option>';
                                                        }                                                        
                                                    endforeach;
                                                }
                                                ?>                                                                                                  
                                            </select>    
                                        </div> 
                                        <p class="text-end">                            
                                            <input type="submit" class="btn btn-success" value="Atualizar" />
                                            <button type="button" class="btn btn-danger" onclick="location.href='instituicoes.php'">Voltar</button>
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
                    <form method="get" action="instituicoes.php" name="meuForm" class="form-group mx-auto">
                        <center>                
                            <div class="w-50 input-group input-group-sm mb-3">
                                <span class="input-group-text col-3" id="inputGroup-nomeEnt">Nome da Instituição</span>
                                <input type="text" name="search" value="<?= htmlspecialchars($searchTerm ?? '') ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-nomeEnt"/>
                            </div>
                            <div class="w-25">
                                <input type="submit" class="form-control btn btn-primary" value="Buscar" />
                            </div>
                        </center>
                    </form>
                <?php

                }

                if(isset($_GET['search']))
                {
                ?>
                <div class="row">
                    <table class="table table-sm table-striped table-hover m-auto">
                        <thead>
                            <tr class="text-center align-middle">
                                <th class="col w-auto fw-semibold">ID</th>
                                <th class="col w-auto fw-semibold">Instituição</th>
                                <th class="col w-auto fw-semibold">CNPJ</th>                                
                                <th class="col w-auto fw-semibold">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php                            
                            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                                $instituicoes = [];
                                $searchTerm = filter_input(INPUT_GET, 'search', FILTER_DEFAULT);                        
                                
                                if(!empty($searchTerm)){
                                    $instituicoes = $instituicaoModel->findByName($searchTerm);
                                } else {
                                    $instituicoes = $instituicaoModel->all();
                                }                                

                                if($instituicoes)
                                {
                                    foreach($instituicoes as $inst):
                                        $instId = $inst->id;
                                        $instNome = $inst->instituicao;
                                        $instCnpj = $inst->cnpj;
                                        $cnpj = substr($instCnpj,0,2) . "." . substr($instCnpj,2,3) . "." . substr($instCnpj,5,3) . "/" . substr($instCnpj,8,4) . "-" . substr($instCnpj,12,2);
                                                                                                            
                                        echo '<tr class="fw-lighter align-middle">';
                                        echo '<td scope="row" class="text-center">'. $instId . '</td>';
                                        echo '<td class="">' . $instNome . '</td>';
                                        echo '<td class="text-center">'. $cnpj . '</td>';                                    
                                        echo '<td class="text-center">';
                                        echo '<a href="?edit=true&idInst=' . $instId .'">Editar Dados</a><br />';                                    
                                        echo '</td>';
                                        echo '</tr>';
                                    endforeach;                             
                                }
                                else
                                {
                                    echo "<script>alert('A busca não retornou resultados!');</script>";
                                    echo '<h4 class="text-center">A busca não retornou resultados, busque pelo nome de alguma escola.</h4>';
                                }
                            }
                        }
                        ?>                            
                        </tbody>                    
                    </table>
                </div>
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