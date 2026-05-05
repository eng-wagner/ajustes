<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

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


$instituicaoModel = new Instituicao();
$itensModel = new ItensCota();

$currentUser = $_SESSION['user_id'];

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
    <title>Gerar Cotas</title>
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

    if (isset($_REQUEST['gerar']) && $_REQUEST['gerar'] == true) {
        unset($_SESSION['cota']);        
        $_SESSION['cota'] = array($_POST);

        if(isset($_POST['tipoCota']) && $_POST['tipoCota'] ==  1)
        {        
            header("Location: cota.php");
            exit();
        }
        else if(isset($_POST['tipoCota']) && $_POST['tipoCota'] ==  2)
        {
            header("Location: cotafin.php");
            exit();
        }
        //print_r($cota);
        //var_dump($cota);
    }
    ?>

    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Gerar Cota
                </h1>
            </div>
            <!-- Início do Conteúdo -->

            <div class="container-fluid">
                <form method="post" action="?gerar=true" target="_blank" id="formCota" onsubmit="return validarFormulario(event)" class="form-group mx-auto">
                    <div class="row">
                        <div class="col-6">
                            <div class="input-group input-group-sm mb-2">
                                <div class="form-floating mb-3">
                                    <select name="instituicao" class="form-select" id="inputGroup-inst" required>
                                        <option selected>Selecione a instituição...</option>
                                        <?php
                                        $instituicoes = $instituicaoModel->all();                                    
                                        if ($instituicoes) {
                                            foreach($instituicoes as $inst) {
                                                $id = $inst->id;
                                                $instituicao = $inst->instituicao;
                                                echo '<option value="' . $id . '">' . $instituicao . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <label for="inputGroup-inst">Instituição</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="input-group input-group-sm mb-2">
                                <div class="form-floating mb-3">
                                    <select name="programa" class="form-select" id="inputGroup-acao" required>
                                        <option selected disabled>Selecione...</option>
                                        <option value="1">PDDE Básico</option>
                                        <option value="2">PDDE Qualidade</option>
                                        <option value="3">PDDE Equidade</option>
                                        <option value="4">PDDE Educação Integral</option>
                                        <option value="5">PDDE PDE Escola</option>
                                    </select>
                                    <label for="inputGroup-acao">Ação</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-3">
                            <div class="input-group input-group-sm mb-2">
                                <div class="form-floating mb-3">
                                    <select name="tipoCota" class="form-select" id="inputGroup-tipo" required>
                                        <option selected disabled>Selecione...</option>
                                        <option value="1">Juntada e Execução</option>
                                        <option value="2">Análise Financeira</option>
                                    </select>
                                    <label for="inputGroup-tipo">Tipo da Cota</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="area-documentos">                
                        <hr>                        
                        <div class="card shadow-sm border-light mb-4">
                            <div class="card-header bg-white pb-0 border-0">
                                <h6 class="text-muted fw-bold mb-0">Selecione os documentos que vão compor a cota</h6>
                            </div>
                            <div class="card-body">
                                <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;" class="border rounded p-3 bg-light">
                                    <?php
                                    $itens = $itensModel->all();                        
                                    if ($itens) : 
                                        foreach($itens as $docs) :
                                            $idDoc = $docs->id;
                                            $documentosCota = $docs->documentos;
                                            $chName = $docs->chName;
                                            $docAtivo = $docs->ativo;

                                            if ($docAtivo == 1) :?> 
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="<?= $chName  ?>" value="<?= $idDoc ?>" id="check_<?= $chName ?>">
                                                    <label class="form-check-label ms-2" for="check_<?= $chName ?>"><?= $documentosCota ?></label>
                                                </div>
                                            <?php endif;
                                        endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>                    
                    <input type="submit" value="Gerar Cota" class="btn btn-primary" />
                </form>
                <br /><br />
                

            </div>

            <!-- Fim do Conteúdo -->
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>

    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        document.getElementById('inputGroup-tipo').addEventListener('change', function() {
            const areaDocs = document.getElementById('area-documentos');
            // Se for 2 (Análise Financeira), esconde os documentos. Senão, mostra.
            if(this.value === '2') {
                areaDocs.style.display = 'none';
            } else {
                areaDocs.style.display = 'block';
            }
        });

        function validarFormulario(event) {
            const tipoAcao = document.getElementById('inputGroup-tipo').value;
            // Só valida os checkboxes se NÃO for Análise Financeira (que esconde os docs)
            if (tipoAcao !== '2') {
                const checkboxes = document.querySelectorAll('#area-documentos .form-check-input');
                const marcados = Array.from(checkboxes).some(cb => cb.checked);
                
                if (!marcados) {
                    event.preventDefault(); // Impede o envio do form
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atenção!',
                        text: 'Selecione pelo menos um documento para gerar a cota.'
                    });
                    return false;
                }
            }
            return true; // Se estiver tudo certo, deixa o formulário enviar
        }
    </script>
</body>

</html>
<?php
ob_flush();
?>