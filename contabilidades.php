<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";
require_once __DIR__ . "/source/Helpers/Helpers.php";


use Source\Models\Logs;
use Source\Models\Contabilidade;
use Source\Models\User;

$userModel = new User();

// 1. TRAVA DE SEGURANÇA REFORÇADA: Só ADM entra!
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
} else {
    $loggedUser = $userModel->findById($_SESSION['user_id']);
    if (!$loggedUser || $loggedUser->perfil !== 'adm') {
        header("Location: hub.php?erro=acesso_negado");
        exit();
    }
    $userName = $loggedUser->nome;
    $perfil = $loggedUser->perfil;
}

$userModel = new User();
$loggedUser = $userModel->findById($_SESSION['user_id']);

if ($loggedUser) {
    $userName = $loggedUser->nome;
    $perfil = $loggedUser->perfil;
} else {
    session_destroy();
    header("Location: index.php?status=sessao_invalida");
    exit();
}


// --- TRATAMENTO DE ROTAS E REDIRECIONAMENTOS ---
if (isset($_GET["logoff"]) && $_GET["logoff"] == true) {
    $_SESSION['flag'] = false;
    session_unset();
    header("Location: index.php?status=logoff");
    exit();
}

$timezone = new DateTimeZone("America/Sao_Paulo");
$contabModel = new Contabilidade();
$logModel = new Logs();

$firstName = substr($userName, 0, strpos($userName, " "));

// --- PROCESSAMENTO DOS FORMULÁRIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);

    // Inserção
    if (isset($_GET['input']) && $_GET['input'] == true) {
        if (!empty($postData['nome'])) {
            if($contabModel->save($postData)) {                
                $logModel->save([
                    'usuario' => $_SESSION['matricula'],
                    'acao' => "Cadastrou um nova contabilidade (" . $postData['nome'] . ")"
                ]);
                redirecionar("contabilidades.php","sucesso", "Contabilidade cadastrada com sucesso!");                
                exit(); 
            } else {
                redirecionar("contabilidades.php","erro", "Erro ao inserir novo registro!");
            }
        }
    }

    // Atualização
    if (isset($_GET['update']) && $_GET['update'] == true) {
        if (!empty($postData['idCont'])) {
            if($contabModel->save($postData)) {                
                $logModel->save([
                    'usuario' => $_SESSION['matricula'],
                    'acao' => "Atualizou a contabilidade de id " . $postData['idCont']
                ]);
                redirecionar("contabilidades.php","sucesso", "Contabilidade atualizada com sucesso! ");
                exit(); 
            } else {
                redirecionar("contabilidades.php","erro", "Erro ao atualizar registro!");
            }
        }
    }
}
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
    <title>Cadastro de Contabilidades</title>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu.php'; ?>
        
        <div class="main p-4">
            <div class="container-fluid">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h1 class="title-page mb-0" style="font-family: 'Rubik Doodle Shadow', system-ui; font-size: 48px; color: #0e2238;">Contabilidades</h1>
                        <p class="text-muted">Cadastro de escritórios contábeis parceiros</p>
                    </div>
                    
                    <?php if (!isset($_GET['new']) && !isset($_GET['edit'])): ?>
                    <div class="mt-3 mt-md-0 d-flex gap-2">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="lni lni-search-alt"></i></span>
                            <input type="text" id="buscaContabilidade" class="form-control border-start-0" placeholder="Buscar por nome, e-mail ou telefone...">
                        </div>
                        <a href="?new=true" class="btn btn-primary text-nowrap shadow-sm d-flex align-items-center">
                            <i class="lni lni-plus me-1"></i> Nova
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="mb-4">

                <?php 
                // ==========================================
                // TELA DE CRIAÇÃO (NEW)
                // ==========================================
                if(isset($_GET['new']) && $_GET['new'] == true) { 
                ?>
                    <div class="card border-0 shadow-sm mx-auto" style="max-width: 900px;">
                        <div class="card-body p-4 p-md-5">
                            <h4 class="mb-4 text-center text-primary"><i class="lni lni-calculator me-2"></i>Cadastrar Nova Contabilidade</h4>
                            
                            <form method="post" action="?input=true" name="formNovaContabilidade">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <input type="text" name="nome" class="form-control" id="fNome" placeholder="Nome da Contabilidade" required />
                                            <label for="fNome">Nome da Contabilidade</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="telefone" class="form-control" id="fTel" placeholder="Telefone" required />
                                            <label for="fTel">Telefone</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" name="email" class="form-control" id="fEmail" placeholder="E-mail" required />
                                            <label for="fEmail">E-mail</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-5">
                                    <button type="button" class="btn btn-outline-secondary px-4" onclick="location.href='contabilidades.php'">Cancelar</button>
                                    <button type="submit" class="btn btn-success px-5 shadow-sm">Cadastrar Contabilidade</button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php 
                // ==========================================
                // TELA DE EDIÇÃO (EDIT)
                // ==========================================
                } else if(isset($_GET['edit']) && $_GET['edit'] == true) {        
                    $cont = $contabModel->findById($_GET['idCont']);   
                    if($cont){
                        $contId = $cont->id;
                        $contNome = htmlspecialchars($cont->c_nome, ENT_QUOTES, 'UTF-8');
                        $contFone = htmlspecialchars($cont->c_telefone, ENT_QUOTES, 'UTF-8');
                        $contEmail = htmlspecialchars($cont->c_email, ENT_QUOTES, 'UTF-8'); 
                ?>
                    <div class="card border-0 shadow-sm mx-auto" style="max-width: 900px;">
                        <div class="card-body p-4 p-md-5">
                            <h4 class="mb-4 text-center text-primary"><i class="lni lni-pencil-alt me-2"></i>Editar Contabilidade</h4>
                            
                            <form method="post" action="?update=true" name="formEditContabilidade">
                                <input type="hidden" name="idCont" value="<?= $contId ?>" />
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <input type="text" name="nome" value="<?= $contNome ?>" class="form-control" id="fNomeEdit" required/>
                                            <label for="fNomeEdit">Nome da Contabilidade</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="telefone" value="<?= $contFone ?>" class="form-control" id="fTelEdit" required/>
                                            <label for="fTelEdit">Telefone</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" name="email" value="<?= $contEmail ?>" class="form-control" id="fEmailEdit" required/>
                                            <label for="fEmailEdit">E-mail</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-5">
                                    <button type="button" class="btn btn-outline-secondary px-4" onclick="location.href='contabilidades.php'">Voltar</button>
                                    <button type="submit" class="btn btn-success px-5 shadow-sm">Salvar Alterações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php 
                    } 
                } else { 
                // ==========================================
                // TELA PRINCIPAL: LISTAGEM E BUSCA INSTANTÂNEA
                // ==========================================
                ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" id="tabelaContabilidades">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th class="ps-4 py-3" style="width: 80px;">ID</th>
                                            <th class="py-3">Contabilidade</th>
                                            <th class="py-3">Contatos</th>
                                            <th class="text-center py-3" style="width: 120px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php                            
                                        $contabilidades = $contabModel->all();                                
                                        if($contabilidades) {
                                            foreach($contabilidades as $cont):
                                                $cId = htmlspecialchars($cont->id, ENT_QUOTES, 'UTF-8');
                                                $cNome = htmlspecialchars($cont->c_nome, ENT_QUOTES, 'UTF-8');
                                                $cFone = htmlspecialchars($cont->c_telefone, ENT_QUOTES, 'UTF-8');
                                                $cEmail = htmlspecialchars($cont->c_email, ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $cId ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= $cNome ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.85rem;">
                                                    <div><i class="lni lni-envelope me-1 text-secondary"></i><?= $cEmail ?></div>
                                                    <div><i class="lni lni-phone me-1 text-secondary"></i><?= $cFone ?></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="?edit=true&idCont=<?= $cId ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar Contabilidade">
                                                    <i class="lni lni-pencil"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php 
                                            endforeach;                             
                                        } else {
                                            echo '<tr><td colspan="4" class="text-center py-5 text-muted"><i class="lni lni-empty-file fs-2 d-block mb-2"></i>Nenhuma contabilidade cadastrada.</td></tr>';
                                        }
                                        ?>                            
                                    </tbody>                    
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                                
            </div>             
        </div>        
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; // Adicionado caso você use toasts para status ?>
    <?php include 'footer.php'; ?>
    
    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // --- SCRIPT DE BUSCA INSTANTÂNEA NA TABELA ---
    document.addEventListener("DOMContentLoaded", function() {
        const inputBusca = document.getElementById('buscaContabilidade');
        if(!inputBusca) return; // Se não estiver na tela principal, ignora

        const tbody = document.querySelector('#tabelaContabilidades tbody');
        let cacheLinhas = [];
        let timeoutDeBusca = null;

        const linhas = tbody.querySelectorAll('tr');
        linhas.forEach(function(linha) {
            // Ignora a linha de "Nenhuma contabilidade" caso exista
            if (linha.cells.length > 1) {
                cacheLinhas.push({
                    elemento: linha,
                    texto: linha.innerText.toLowerCase()
                });
            }
        });

        inputBusca.addEventListener('input', function() {
            clearTimeout(timeoutDeBusca);
            let filtro = this.value.toLowerCase();
            
            timeoutDeBusca = setTimeout(function() {
                tbody.style.display = 'none'; 
                cacheLinhas.forEach(function(item) {
                    if (item.texto.includes(filtro)) {
                        item.elemento.style.display = '';
                    } else {
                        item.elemento.style.display = 'none';
                    }
                });
                tbody.style.display = ''; 
            }, 200); // 200ms debounce
        });

        // Aplicação de máscara no telefone, caso deseje
        $('#fTel, #fTelEdit').mask('(00) 00000-0000');
    });
    </script>
</body>
</html>
<?php ob_flush(); ?>