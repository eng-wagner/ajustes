<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\Logs;
use Source\Models\User;
use Source\Models\Instituicao;
use Source\Models\Processo;

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

$logModel = new Logs();
$instituicaoModel = new Instituicao();
$processoModel = new Processo();
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
    <title>Localizar Processos</title>
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

    //$processos = [];
    $searchTerm = filter_input(INPUT_GET, 'search', FILTER_DEFAULT);                       
    
    if(!empty($searchTerm))
    {
        // 1. Busca por instituições que contenham o termo no nome
        $processos = $processoModel->findProcByInstName($searchTerm);
    }
    else 
    {
        $processos = $processoModel->allProcs();        
    }
    ?>
    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Localizar Processos
                </h1>
            </div>
            <!-- Início do Conteúdo  -->

            <div class="container-fluid">
                <form method="get" action="buscar.php" name="meuForm" class="form-group mx-auto">
                    <center>                
                        <div class="w-50 input-group input-group-sm mb-3">
                            <span class="input-group-text col-3" id="inputGroup-nomeEnt">Nome da Instituição</span>
                            <input type="text" name="search" value="<?= htmlspecialchars($searchTerm ?? '') ?>" class="w-50 col-9 form-control" aria-describedby="inputGroup-nomeEnt"/>
                        </div>
                        <div class="w-25">
                            <button class="btn btn-primary" type="submit">
                                <i class="lni lni-search-alt"></i> Buscar
                            </button>                            
                        </div>
                    </center>
                </form>

                <hr>

                <div class="mt-4">                    
                    <?php 
                    if (!empty($processos)):?>
                        <div class="row">
                            <table class="table table-sm table-striped table-hover m-auto">
                                <thead>
                                    <tr class="text-center align-middle">
                                        <th class="col w-auto fw-semibold">Instituição</th>
                                        <th class="col w-auto fw-semibold">CNPJ</th>                        
                                        <th class="col w-auto fw-semibold">Nº Processo Digital</th>
                                        <th class="col w-auto fw-semibold">Assunto</th>
                                        <th class="col w-auto fw-semibold">Tipo</th>                        
                                        <th class="col w-auto fw-semibold">Ação</th>                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($processos as $proc):
                                        $instId = $proc->instituicao_id;
                                        $instNome = $proc->instituicao;
                                        $instCnpj = $proc->cnpj;
                                        $cnpj = substr($instCnpj,0,2) . "." . substr($instCnpj,2,3) . "." . substr($instCnpj,5,3) . "/" . substr($instCnpj,8,4) . "-" . substr($instCnpj,12,2);
                                        
                                        $orgao = $proc->orgao;
                                        $numero = $proc->numero;
                                        $ano = $proc->ano;
                                        $digito = $proc->digito;
                                        $assunto = $proc->assunto;
                                        $tipo = $proc->tipo;
                                        $detalhamento = $proc->detalhamento;
                                        $idProc = $proc->idProc;                                                            
                                            
                                        echo '<tr class="fw-lighter align-middle">';
                                        echo '<td scope="row" class="">' . $instNome . '</td>';                                            
                                        echo '<td class="text-center">'. $cnpj . '</td>';
                                        echo '<td class="text-center">' . $orgao . '.' . $numero . '/' . $ano . '-' . $digito . '</td>';
                                        echo '<td class="">' . $assunto . '</td>';
                                        echo '<td class="">' . $tipo . ' ' . $detalhamento . '</td>';
                                        echo '<td class="text-center">';
                                        if($tipo == "Termo de Colaboração")
                                        {
                                            echo '<a href="?tc=true&idProc=' . $idProc .'">Análise da Execução</a><br />';
                                        }
                                        else
                                        {
                                            echo '<a href="?pc=true&idProc=' . $idProc .'">Análise da Execução</a><br />';
                                            echo '<a href="?af=true&idProc=' . $idProc .'">Análise Financeira</a>';
                                        }
                                        echo '</td>';
                                        echo '</tr>';
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php
                    else: ?>
                        <div class="alert alert-warning">Nenhuma instituição encontrada com o termo "<?= htmlspecialchars($searchTerm) ?>".</div>
                    <?php 
                    endif; 
                    ?>
                </div>
            </div>
        </div>
                
        <?php
        if(isset($_REQUEST['pc']) && $_REQUEST['pc'] == true )
        {                
            $_SESSION['idProc'] = $_REQUEST['idProc'];
            $_SESSION['nav'] = array("active","","","","");
            $_SESSION['navShow'] = array("show active","","","","");
            $_SESSION['sel'] = array("true","false","false","false","false");
            header('Location:pddePC.php');
        }
        
        if(isset($_REQUEST['af']) && $_REQUEST['af'] == true )
        {                
            $_SESSION['idProc'] = $_REQUEST['idProc'];
            $_SESSION['navF'] = array("active","","","","","");
            $_SESSION['navShowF'] = array("show active","","","","","");
            $_SESSION['selF'] = array("true","false","false","false","false","false");
            header('Location:pddeFinanc.php');
        }

        if(isset($_REQUEST['tc']) && $_REQUEST['tc'] == true )
        {                
            $_SESSION['idProc'] = $_REQUEST['idProc'];
            $_SESSION['nav'] = array("active","","","","");
            $_SESSION['navShow'] = array("show active","","","","");
            $_SESSION['sel'] = array("true","false","false","false","false");
            header('Location:termoPC.php');                       
        }

                
        if(isset($_REQUEST['novoProcesso']) && $_REQUEST['novoProcesso'] == true ){
            
            $orgaoProc = "SB";

            $sql = $pdo->prepare("INSERT INTO processos(orgao, numero, ano, digito, assunto, tipo, instituicao_id) VALUES (?,?,?,?,?,?,?)");
            $sql->bindParam(1,$orgaoProc);
            $sql->bindParam(2,$_POST['numProc']);
            $sql->bindParam(3,$_POST['anoProc']);
            $sql->bindParam(4,$_POST['digProc']);
            $sql->bindParam(5,$_POST['assuntoProc']);
            $sql->bindParam(6,$_POST['tipoProc']);            
            $sql->bindParam(7,$_POST['instProc']);
            if($sql->execute()){                
                header('Location:buscar.php');
            }
            else
            {
                echo '<script>alert("Erro ao inserir novo processo. Contate o administrador.")</script>';
            }
        }
        ?>
        <br />

        <!-- Fim do Conteúdo  -->        
    </div>
    
    <!-- Modal Novo Processo -->
    <div class="modal fade" id="processoModal" tabindex="-1" aria-labelledby="processoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="?novoProcesso=true" method="post" name="pendencia">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="processoModalLabel">Novo Processo</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="content-fluid">
                            <div class="row">                                        
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-assuntoProc">Assunto</label>
                                        <select name="assuntoProc" class="form-select col-8" id="inputGroup-assuntoProc" required>
                                            <option value="" disabled="disabled" selected>Selecione...</option>
                                            <option value="Prestação de Contas">Prestação de Contas</option>
                                            <option value="Parceria">Parceria</option>
                                            <option value="Pagamento">Pagamento</option>
                                        </select>                                                            
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group input-group-sm mb-2">
                                        <label class="input-group-text col-4" for="inputGroup-tipoProc">Tipo</label>
                                        <select name="tipoProc" class="form-select col-8" id="inputGroup-tipoProc" required>
                                            <option value="" disabled="disabled" selected>Selecione...</option>
                                            <option value="PDDE Básico">PDDE Básico</option>
                                            <option value="PDDE Qualidade">PDDE Qualidade</option>
                                            <option value="PDDE Estrutura">PDDE Estrutura</option>
                                            <option value="Termo de Colaboração">Termo de Colaboração</option>
                                        </select>                                                            
                                    </div>
                                </div>
                            </div>
                            <!--                        
                            <div class="row">                                        
                                <div class="form-floating mb-2">
                                    <textarea name="detalhamentoProc" class="form-control" placeholder="" id="detailProc" style="height: 120px"></textarea>
                                    <label for="detailProc">Detalhamento</label>
                                </div>
                            </div>
                            -->
                            <div class="row">
                                <div class="col-2">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-6" id="inputGroup-orgaoProc">Órgão</span>
                                        <input type="text" name="orgaoProc" class="col-6 form-control" value="SB" aria-describedby="inputGroup-orgaoProc" disabled/>
                                    </div>                                                  
                                </div>
                                <div class="col-4">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-numProc">Número</span>
                                        <input type="text" name="numProc" class="col-8 form-control" aria-describedby="inputGroup-numProc" minlength="6" maxlength="6" required />
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-anoProc">Ano</span>
                                        <input type="text" name="anoProc" class="col-8 form-control" aria-describedby="inputGroup-anoProc" minlength="4" maxlength="4" required/>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text col-4" id="inputGroup-digProc">Dígito</span>
                                        <input type="text" name="digProc" class="col-8 form-control" aria-describedby="inputGroup-digProc" minlength="2" maxlength="2" required/>
                                    </div>
                                </div>                                        
                            </div>
                            <div class="row">                                        
                                <div class="input-group input-group-sm mb-2">
                                    <label class="input-group-text col-2" for="inputGroup-instProc">Instituição</label>
                                    <select name="instProc" class="form-select col-10" id="inputGroup-instProc" required>
                                        <option value="" disabled="disabled" selected>Selecione...</option>
                                        <?php
                                        $sql = $pdo->prepare("SELECT id, instituicao FROM instituicoes");                                                    
                                        if($sql->execute())
                                        {
                                            while($proc = $sql->fetch())
                                            {
                                                $idInst = $proc->id;                                                                                
                                                $instProc = $proc->instituicao;        
                                                echo '<option value="' . $idInst . '">' . $instProc . '</option>';
                                            }
                                        }
                                        ?>                                                                                                  
                                    </select>                                                            
                                </div>
                            </div>                                            
                        </div>                                                    
                    </div>
                    <div class="modal-footer">
                        <input type="submit" class="btn btn-success" value="Cadastrar"/>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>                                
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Fim Modal Novo Processo -->

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