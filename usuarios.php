<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";
require_once __DIR__ . "/source/Helpers/Helpers.php";

use Source\Models\User;
use Source\Models\Local;
use Source\Models\Logs;

$userModel = new User();

if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
} else {
    $loggedUser = $userModel->findById($_SESSION['user_id']);
    if ($loggedUser) {
        $userName = $loggedUser->nome;
        $perfil = $loggedUser->perfil;
        
        // NOVA TRAVA DE SEGURANÇA: Chuta quem não é admin
        if ($perfil !== 'adm') {
            header("Location: hub.php?erro=acesso_negado");
            exit();
        }
    }
}
$currentUser = $_SESSION['user_id'];
$firstName = explode(' ', $userName)[0];

// Cria uma instância do nosso modelo de Usuário.
// A conexão com o banco já é feita dentro da classe.

$localModel = new Local();
$logModel = new Logs();

$timezone = new DateTimeZone("America/Sao_Paulo");



if (isset($_REQUEST['input']) && $_REQUEST['input'] == true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($postData['nome']) && !empty($postData['matricula'])) {
            if($userModel->save($postData)) {                
                $log = $logModel->save([
                    'usuario' => $_SESSION['matricula'],
                    'acao' => "Cadastrou um novo usuário (" . $postData['nome'] . ")"
                ]);                                    
            };
            redirecionar("usuarios.php", "sucesso", "Usuário cadastrado com sucesso!");
            exit();
        } else {
            redirecionar("usuarios.php", "erro", "Erro ao cadastrar usuário!");
            exit();            
        }
    }
}

if (isset($_REQUEST['update']) && $_REQUEST['update'] == true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($postData['nome'])) {
            if($userModel->save($postData)) {                
                $log = $logModel->save([
                    'usuario' => $_SESSION['matricula'],
                    'acao' => "Atualizou o usuário de id " . $postData['idUser']
                ]);
            };
            redirecionar("usuarios.php", "sucesso", "Usuário atualizado com sucesso!");
            exit(); 
        } else {
            redirecionar("usuarios.php", "erro", "Erro ao atualizar usuário!");
            exit();
        }
    }
}   

if (isset($_GET['desativar']) && $_GET['desativar'] == true) {
    $desativar = $userModel->deactivate($_GET['idUser']);                    
    if ($desativar) {        
        $log = $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Desativou o usuário de id " . $_GET['idUser']
        ]);                       
        redirecionar("usuarios.php", "sucesso", "Usuário desativado com sucesso!");
        exit();
    } else {
        redirecionar("usuarios.php", "erro", "Erro ao desativar usuário!");
        exit();
    }
}

if (isset($_GET['reativar']) && $_GET['reativar'] == true) {
    $ativar = $userModel->activate($_GET['idUser']);
    if ($ativar) {        
        $log = $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Reativou o usuário de id " . $_GET['idUser']
        ]);                       
        redirecionar("usuarios.php", "sucesso", "Usuário reativado com sucesso!");
        exit();
    } else {
        redirecionar("usuarios.php", "erro", "Erro ao reativar usuário!");
        exit();
    }
}

if (isset($_GET['renewPass']) && $_GET['renewPass'] == true) {
    $renovar = $userModel->renewPass($_GET['idUser']);
    if ($renovar) {        
        $log = $logModel->save([
            'usuario' => $_SESSION['matricula'],
            'acao' => "Resetou a senha do usuário de id " . $_GET['idUser']
        ]);                       
        redirecionar("usuarios.php", "sucesso", "Senha renovada com sucesso!");
        exit();
    } else {
        redirecionar("usuarios.php", "erro", "Erro ao renovar senha!");
        exit();
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
    <title>Usuários</title>
    <style>
        h1 {
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>
</head>

<body>               
    <div class="wrapper">
        <?php include 'menu.php'; ?>
        
        <div class="main p-4">

            <div class="container-fluid">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h1 class="title-page mb-0" style="font-family: 'Rubik Doodle Shadow', system-ui; font-size: 48px; color: #0e2238;">Usuários</h1>
                        <p class="text-muted">Gerenciamento de acessos do sistema</p>
                    </div>

                    <?php if (!isset($_GET['new']) && !isset($_GET['edit'])): ?>
                    <div class="mt-3 mt-md-0 d-flex gap-2">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="lni lni-search-alt"></i></span>
                            <input type="text" id="buscaUsuarios" class="form-control border-start-0" placeholder="Buscar usuário...">
                        </div>
                        <a href="?new=true" class="btn btn-primary text-nowrap shadow-sm">
                            <i class="lni lni-plus"></i> Novo
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="mb-4">
                
                <?php if (isset($_GET['new']) && $_GET['new'] == true) { ?>
                    <div class="card border-0 shadow-sm mx-auto" style="max-width: 800px;">
                        <div class="card border-0 shadow-sm mx-auto" style="max-width: 800px;">
                            <div class="card-body p-4 p-md-5">
                                <h4 class="mb-4 text-center text-primary"><i class="lni lni-user me-2"></i>Cadastrar Novo Usuário</h4>

                                <form method="post" action="?input=true" name="meuForm">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="nome" class="form-control" id="fNome" placeholder="Nome" required />
                                        <label for="fNome">Nome Completo</label>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="text" name="matricula" class="form-control" id="fMat" placeholder="Matrícula" minlength="5" maxlength="5" required />
                                                <label for="fMat">Matrícula (5 dígitos)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-floating">
                                                <input type="text" name="dv" class="form-control" id="fDv" placeholder="Dígito" minlength="1" maxlength="1" required />
                                                <label for="fDv">Dígito</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" name="funcao" class="form-control" id="fFuncao" placeholder="Função" required />
                                                <label for="fFuncao">Função</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-8">
                                            <div class="form-floating">
                                                <select name="localId" class="form-select" id="fLocal" required>
                                                    <option value="" disabled selected>Selecione...</option>
                                                    <?php
                                                    $locais = $localModel->all();
                                                    foreach ($locais as $local):
                                                        echo '<option value="' . $local->id . '">' . $local->sigla . " - " . $local->nome_local . '</option>';
                                                    endforeach;
                                                    ?>
                                                </select>
                                                <label for="fLocal">Local de Exercício</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="text" name="perfil" class="form-control" id="fPerfil" placeholder="Perfil" minlength="3" maxlength="3" required />
                                                <label for="fPerfil">Perfil (Ex: adm, ofp)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-5">
                                        <button type="button" class="btn btn-outline-secondary px-4" onclick="location.href='usuarios.php'">Cancelar</button>
                                        <button type="submit" class="btn btn-success px-5 shadow-sm">Cadastrar</button>
                                    </div>
                                </form>
                            </div>
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

                        <div class="card border-0 shadow-sm mx-auto" style="max-width: 800px;">
                        <div class="card-body p-4 p-md-5">
                            <h4 class="mb-4 text-center text-primary"><i class="lni lni-pencil-alt me-2"></i>Editar Usuário</h4>
                            <div class="text-center mb-4">
                                <span class="badge bg-light text-dark border p-2 fs-6">Matrícula: <strong><?= $usuarioMat . "-" . $usuarioDv ?></strong></span>
                            </div>

                            <form method="post" action="?update=true" name="meuForm">
                                <input type="hidden" name="idUser" value="<?= $usuarioId ?>" />
                                
                                <div class="form-floating mb-3">
                                    <input type="text" name="nome" value="<?= $usuarioNom; ?>" class="form-control" id="fNomeEdit" placeholder="Nome" required />
                                    <label for="fNomeEdit">Nome Completo</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <input type="text" name="funcao" value="<?= $usuarioFuncao; ?>" class="form-control" id="fFuncaoEdit" placeholder="Função" required />
                                    <label for="fFuncaoEdit">Função</label>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-8">
                                        <div class="form-floating">
                                            <select name="localId" class="form-select" id="fLocalEdit" required>
                                                <option value="" disabled>Selecione...</option>
                                                <?php
                                                $locais = $localModel->all();
                                                foreach($locais as $local):                                       
                                                    $selected = ($local->id == $usuarioLocal) ? 'selected' : '';
                                                    echo '<option value="' . $local->id . '" ' . $selected . '>' . $local->sigla . " - " . $local->nome_local . '</option>';
                                                endforeach;                                                
                                                ?>
                                            </select>
                                            <label for="fLocalEdit">Local de Exercício</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" name="perfil" value="<?= $usuarioPerfil; ?>" class="form-control" id="fPerfilEdit" placeholder="Perfil" maxlength="3" required />
                                            <label for="fPerfilEdit">Perfil (Ex: adm, ofp)</label>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">
                                
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <button type="button" class="btn btn-warning shadow-sm me-2" onclick="location.href='?renewPass=true&idUser=<?= $usuarioId ?>'">
                                            <i class="lni lni-key"></i> Renovar Senha
                                        </button>
                                        
                                        <?php if ($usuarioAtivo == 1) { ?>
                                            <button type="button" class="btn btn-outline-danger shadow-sm" onclick="location.href='?desativar=true&idUser=<?= $usuarioId ?>'">
                                                <i class="lni lni-ban"></i> Desativar
                                            </button>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-outline-success shadow-sm" onclick="location.href='?reativar=true&idUser=<?= $usuarioId ?>'">
                                                <i class="lni lni-checkmark-circle"></i> Reativar
                                            </button>
                                        <?php } ?>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary" onclick="location.href='usuarios.php'">Voltar</button>
                                        <button type="submit" class="btn btn-success px-4 shadow-sm">Atualizar</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                
                <?php } else { ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" id="tabelaUsuarios">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th class="ps-4 py-3">Nome / Matrícula</th>
                                            <th class="py-3">Local</th>
                                            <th class="py-3">Função</th>
                                            <th class="py-3">Perfil</th>
                                            <th class="py-3">Status</th>                                            
                                            <th class="text-center py-3">Ações</th>
                                        </tr>
                                    </thead>                        
                                    <tbody>
                                        <?php
                                        $users = $userModel->all();                                            
                                        foreach ($users as $user):
                                            $statusBadge = ($user->ativo == 1) ? 'bg-success' : 'bg-danger';
                                            $statusText = ($user->ativo == 1) ? 'Ativo' : 'Inativo';
                                            $perfilIcon = ($user->perfil == 'adm') ? 'lni-shield' : 'lni-user';                                                                                                                                                                          
                                            $sigla = $localModel->findById($user->id_local)->sigla;
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= $user->nome ?></div>
                                                <small class="text-muted">Mat: <?= $user->matricula . "-" . $user->dv ?></small>
                                            </td>
                                            <td class="text-nowrap"><?= $sigla ?></td>
                                            <td><?= $user->funcao ?></td>
                                            <td>
                                                <span class="text-secondary text-nowrap"><i class="lni <?= $perfilIcon ?> me-1"></i> <?= strtoupper($user->perfil) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $statusBadge ?> rounded-pill px-3 py-2"><?= $statusText ?></span>
                                            </td>                                            
                                            <td class="text-center">
                                                <a href="?edit=true&idUser=<?= $user->id ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar Usuário">
                                                    <i class="lni lni-pencil"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
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
    document.addEventListener("DOMContentLoaded", function() {
        const inputBusca = document.getElementById('buscaUsuarios');
        if(!inputBusca) return; // Se não estiver na tela principal, ignora

        const tbody = document.querySelector('#tabelaUsuarios tbody');
        let cacheLinhas = [];
        let timeoutDeBusca = null;

        const linhas = tbody.querySelectorAll('tr');
        linhas.forEach(function(linha) {
            cacheLinhas.push({
                elemento: linha,
                texto: linha.innerText.toLowerCase()
            });
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
    });
    </script>
</body>

</html>
<?php
ob_flush();
?>