<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";
require_once __DIR__ . "/source/Helpers/Helpers.php";

use Source\Models\Logs;
use Source\Models\Contabilidade;
use Source\Models\User;
use Source\Models\Instituicao;

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

$contabModel = new Contabilidade();
$logModel = new Logs();
$instituicaoModel = new Instituicao();
$timezone = new DateTimeZone("America/Sao_Paulo");

// Trata requisições globais de navegação
if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
    $_SESSION['flag'] = false;
    session_unset();
    header("Location:index.php?status=logoff");
    exit();
}

$firstName = substr($userName,0,strpos($userName," "));

// AÇÕES DE BANCO DE DADOS (Create e Update)
if(isset($_REQUEST['input']) && $_REQUEST['input'] == true) {   
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($postData['instituicao'])) {
            if($instituicaoModel->save($postData)) {               
                $logModel->save([
                    'usuario' => $_SESSION['matricula'],
                    'acao' => "Cadastrou um nova instituição (" . $postData['instituicao'] . ")"
                ]);
                redirecionar("instituicoes.php","sucesso", "Instituição cadastrada com sucesso!");                
                exit(); 
            }
        } else {
            redirecionar("instituicoes.php","erro", "Erro ao inserir novo registro!");
        }
    }                    
}

if(isset($_REQUEST['update']) && $_REQUEST['update'] == true ) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($postData['idInst'])) {
            if($instituicaoModel->save($postData)) {                
                $logModel->save([
                    'usuario' => $_SESSION['matricula'],
                    'acao' => "Atualizou os dados da instituição de id " . $postData['idInst']
                ]);
                redirecionar("instituicoes.php","sucesso", "Instituição atualizada com sucesso!");
                exit();     
                
            }
        } else {
            redirecionar("instituicoes.php","erro", "Erro ao atualizar registro!");
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
    <title>Cadastro de Instituições</title>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu.php'; ?>
        
        <div class="main p-4">
            <div class="container-fluid">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h1 class="title-page mb-0" style="font-family: 'Rubik Doodle Shadow', system-ui; font-size: 48px; color: #0e2238;">Instituições</h1>
                        <p class="text-muted">Cadastro de escolas e unidades parceiras</p>
                    </div>
                    
                    <?php if (!isset($_GET['new']) && !isset($_GET['edit'])): ?>
                    <div class="mt-3 mt-md-0 d-flex gap-2">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="lni lni-search-alt"></i></span>
                            <input type="text" id="buscaInstituicao" class="form-control border-start-0" placeholder="Buscar por nome, INEP ou CNPJ...">
                        </div>
                        <a href="?new=true" class="btn btn-primary text-nowrap shadow-sm">
                            <i class="lni lni-plus"></i> Nova
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="mb-4">

                <?php if(isset($_GET['new']) && $_GET['new'] == true) { ?>
                    <div class="card border-0 shadow-sm mx-auto" style="max-width: 900px;">
                        <div class="card-body p-4 p-md-5">
                            <h4 class="mb-4 text-center text-primary"><i class="lni lni-apartment me-2"></i>Cadastrar Nova Instituição</h4>
                            
                            <form method="post" action="?input=true" name="meuForm">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-9">
                                        <div class="form-floating">
                                            <input type="text" name="instituicao" class="form-control" id="fInst" placeholder="Nome da Instituição" required />
                                            <label for="fInst">Nome da Instituição</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="text" name="inep" class="form-control" id="fInep" placeholder="INEP" />
                                            <label for="fInep">INEP</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" name="cnpj" class="form-control" id="fCnpj" placeholder="CNPJ" minlength="14" maxlength="14" required />
                                            <label for="fCnpj">CNPJ (Apenas números)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" name="telefone" class="form-control" id="fTel" placeholder="Telefone" />
                                            <label for="fTel">Telefone</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="email" name="email" class="form-control" id="fEmail" placeholder="E-mail" />
                                            <label for="fEmail">E-mail</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-7">
                                        <div class="form-floating">
                                            <input type="text" name="endereco" class="form-control" id="fEnd" placeholder="Endereço" required />
                                            <label for="fEnd">Endereço Completo</label>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-floating">
                                            <select name="contId" class="form-select" id="fCont" required>
                                                <option value="" disabled selected>Selecione...</option>
                                                <?php
                                                $conts = $contabModel->all();
                                                if($conts) {
                                                    foreach($conts as $cont):                                        
                                                        echo '<option value="' . $cont->id . '">' . $cont->c_nome . '</option>';                                                                               
                                                    endforeach;
                                                }
                                                ?>                                                
                                            </select>    
                                            <label for="fCont">Contabilidade</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-5">
                                    <button type="button" class="btn btn-outline-secondary px-4" onclick="location.href='instituicoes.php'">Cancelar</button>
                                    <button type="submit" class="btn btn-success px-5 shadow-sm">Cadastrar Instituição</button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php 
                } else if(isset($_GET['edit']) && $_GET['edit'] == true) {        
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
                    <div class="card border-0 shadow-sm mx-auto" style="max-width: 900px;">
                        <div class="card-body p-4 p-md-5">
                            <h4 class="mb-4 text-center text-primary"><i class="lni lni-pencil-alt me-2"></i>Editar Instituição</h4>
                            
                            <form method="post" action="?update=true" name="meuForm">
                                <input type="hidden" name="idInst" value="<?= $idInst ?>" />
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-9">
                                        <div class="form-floating">
                                            <input type="text" name="instituicao" value="<?= htmlspecialchars($nomeInst) ?>" class="form-control" id="fInstEdit" required/>
                                            <label for="fInstEdit">Nome da Instituição</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="text" name="inep" value="<?= htmlspecialchars($inepInst) ?>" class="form-control" id="fInepEdit" required/>
                                            <label for="fInepEdit">INEP</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" name="cnpj" value="<?= htmlspecialchars($cnpjInst) ?>" class="form-control" id="fCnpjEdit" minlength="14" maxlength="14" required/>
                                            <label for="fCnpjEdit">CNPJ (Apenas números)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" name="telefone" value="<?= htmlspecialchars($telefoneInst) ?>" class="form-control" id="fTelEdit" required/>
                                            <label for="fTelEdit">Telefone</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="email" name="email" value="<?= htmlspecialchars($emailInst) ?>" class="form-control" id="fEmailEdit" required/>
                                            <label for="fEmailEdit">E-mail</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-7">
                                        <div class="form-floating">
                                            <input type="text" name="endereco" value="<?= htmlspecialchars($enderecoInst) ?>" class="form-control" id="fEndEdit" required/>
                                            <label for="fEndEdit">Endereço Completo</label>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-floating">
                                            <select name="contId" class="form-select" id="fContEdit" required>
                                                <option value="" disabled>Selecione...</option>
                                                <?php
                                                $conts = $contabModel->all();                                        
                                                if($conts) {
                                                    foreach($conts as $cont):                                                    
                                                        $selected = ($cont->id == $idContInst) ? 'selected' : '';
                                                        echo '<option value="' . $cont->id . '" ' . $selected . '>' . $cont->c_nome . '</option>';                                                        
                                                    endforeach;
                                                }
                                                ?>                                                
                                            </select>    
                                            <label for="fContEdit">Contabilidade</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-5">
                                    <button type="button" class="btn btn-outline-secondary px-4" onclick="location.href='instituicoes.php'">Voltar</button>
                                    <button type="submit" class="btn btn-success px-5 shadow-sm">Salvar Alterações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php } } else { 
                // TELA 3: LISTAGEM E BUSCA INSTANTÂNEA
                ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" id="tabelaInstituicoes">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th class="ps-4 py-3">INEP</th>
                                            <th class="py-3">Instituição</th>
                                            <th class="py-3 text-center">CNPJ</th>                               
                                            <th class="py-3">Contatos</th>
                                            <th class="text-center py-3">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php                            
                                        $instituicoes = $instituicaoModel->all();                                
                                        if($instituicoes) {
                                            foreach($instituicoes as $inst):
                                                $instCnpj = preg_replace("/[^0-9]/", "", $inst->cnpj); // Remove formatações acidentais
                                                $cnpjFormatado = strlen($instCnpj) == 14 ? substr($instCnpj,0,2) . "." . substr($instCnpj,2,3) . "." . substr($instCnpj,5,3) . "/" . substr($instCnpj,8,4) . "-" . substr($instCnpj,12,2) : $inst->cnpj;
                                        ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $inst->inep ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($inst->instituicao) ?></div>
                                                <small class="text-muted"><i class="lni lni-map-marker me-1"></i><?= htmlspecialchars($inst->endereco) ?></small>
                                            </td>
                                            <td class="text-center font-monospace text-muted text-nowrap"><?= $cnpjFormatado ?></td>
                                            <td>
                                                <div style="font-size: 0.85rem;">
                                                    <div><i class="lni lni-envelope me-1 text-secondary"></i><?= htmlspecialchars($inst->email) ?></div>
                                                    <div><i class="lni lni-phone me-1 text-secondary"></i><?= htmlspecialchars($inst->telefone) ?></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a href="?edit=true&idInst=<?= $inst->id ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar Instituição">
                                                    <i class="lni lni-pencil"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php 
                                            endforeach;                             
                                        } else {
                                            echo '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="lni lni-empty-file fs-2 d-block mb-2"></i>Nenhuma instituição cadastrada.</td></tr>';
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
        const inputBusca = document.getElementById('buscaInstituicao');
        if(!inputBusca) return; // Se não estiver na tela principal, ignora

        const tbody = document.querySelector('#tabelaInstituicoes tbody');
        let cacheLinhas = [];
        let timeoutDeBusca = null;

        const linhas = tbody.querySelectorAll('tr');
        linhas.forEach(function(linha) {
            // Ignora a linha de "Nenhuma instituição" caso exista
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
    });
    </script>
</body>
</html>
<?php ob_flush(); ?>