<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";

use Source\Models\RelatorioPDDE;
use Source\Models\User;

$relatorioModel = new RelatorioPDDE();
$userModel = new User();
$hoje = date('d/m/Y');

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

$filtroPrograma = (isset($_REQUEST['Prg']) && $_REQUEST['Prg'] != '0') ? $_REQUEST['Prg'] : null;
$filtroStatus   = (isset($_REQUEST['Sts']) && $_REQUEST['Sts'] != '0') ? $_REQUEST['Sts'] : null;

$dados = $relatorioModel->buscarDadosGerais($filtroPrograma, $filtroStatus);
$listaStatus = $relatorioModel->listarStatus();
$totalRegistros = count($dados);

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
    <title>Relatório</title>
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

    $firstName = substr($userName, 0, strpos($userName, " "));

    if (isset($_REQUEST['idProc']) && $_REQUEST['idProc'] > 0) {
        $_SESSION['idProc'] = $_REQUEST['idProc'];
        $_SESSION['nav'] = array("active", "", "", "", "");
        $_SESSION['navShow'] = array("show active", "", "", "", "");
        $_SESSION['sel'] = array("true", "false", "false", "false", "false");
        header('Location:pddePC.php');
    }

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
                    Relatório de Prestação de Contas
                </h1>
            </div>
            <!-- Início do Conteúdo -->
            <hr>
            <div class="container-fluid">                
                <form method="get" class="row g-3 align-items-center mb-3">
                    <div class="col-auto">
                        <label class="form-label fw-bold">Programa:</label>
                        <select class="form-select form-select-sm" name="Prg" onchange="this.form.submit()">
                            <option value="0">Todos os Programas</option>
                            <option value="PDDE Básico" <?= ($filtroPrograma == "PDDE Básico" ? 'selected' : '') ?>>PDDE Básico</option>
                            <option value="PDDE Qualidade" <?= ($filtroPrograma == "PDDE Qualidade" ? 'selected' : '') ?>>PDDE Qualidade</option>
                            <option value="PDDE Equidade" <?= ($filtroPrograma == "PDDE Equidade" ? 'selected' : '') ?>>PDDE Equidade</option>
                            <option value="PDDE Educação Integral" <?= ($filtroPrograma == "PDDE Educação Integral" ? 'selected' : '') ?>>PDDE Ed. Integral</option>
                            <option value="PDDE PDE Escola" <?= ($filtroPrograma == "PDDE PDE Escola" ? 'selected' : '') ?>>PDDE PDE-Escola</option>
                        </select>
                    </div>

                    <div class="col-auto">
                        <label class="form-label fw-bold">Status:</label>
                        <select class="form-select form-select-sm" name="Sts" onchange="this.form.submit()">
                            <option value="0">Todos os Status</option>
                            <option value="-1" <?= ($filtroStatus == '-1' ? 'selected' : '') ?>>Aguardando Entrega</option>
                            <?php foreach ($listaStatus as $statusItem) :
                                if ($statusItem->id == 1) continue; ?>
                                <option value="<?= $statusItem->id ?>" 
                                    <?= ($filtroStatus == $statusItem->id ? 'selected' : '') ?>>
                                    <?= $statusItem->status_pc ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-auto align-self-end">
                        <a href="relatorio.php?Prg=0&Sts=0" class="btn btn-sm btn-outline-secondary">Limpar Filtros</a>
                    </div>
                    <div class="col-auto align-self-end">
                        <a href="relatorio_excel.php" class="btn btn-sm btn-outline-success">Exportar Excel</a>
                    </div>
                    <div class="col-auto align-self-end ms-auto">
                        <span class="badge bg-secondary fs-6">
                            Registros: <strong><?= $totalRegistros ?></strong>
                        </span>
                    </div>
                </form>                   
            <hr>

            <div class="container-fluid">
                <table class="table table-sm table-hover m-auto">
                    <thead>
                        <tr class="text-center align-middle">
                            <th class="col w-auto fw-semibold">Nº Processo</th>
                            <th class="col w-auto fw-semibold">Programa</th>
                            <th class="col w-auto fw-semibold">Instituição</th>
                            <th class="col w-auto fw-semibold">Status</th>
                            <th class="col w-auto fw-semibold">Entrega</th>
                            <th class="col w-auto fw-semibold">Movimentação</th>
                            <th class="col w-auto fw-semibold">Análise Execução</th>
                            <th class="col w-auto fw-semibold">Responsável</th>
                            <th class="col w-auto fw-semibold">Enc. An. Financeira</th>
                            <th class="col w-auto fw-semibold">Análise Financeira</th>
                            <th class="col w-auto fw-semibold">Responsável</th>
                            <th class="col w-auto fw-semibold">SIGPC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $nItem = 0;                                                                            
                        if(!empty($dados)) {
                            foreach($dados as $proc) {
                                $nItem++;
                                $idProc = $proc->proc_id;                                    
                                $tipoProc = $proc->tipo;
                                $instProc = $proc->instituicao;
                                $status = $proc->status_nome ?? 'Aguardando Entrega';
                                $entrega = $proc->data_ent ? date('d/m/Y', strtotime($proc->data_ent)) : '';
                                $sMovimento = ($proc->s_movimento == 1) ? 'Sem movimento' : '';
                                $analiseEx = $proc->data_analise_ex ? date('d/m/Y', strtotime($proc->data_analise_ex)) : '';                                    
                                
                                $firstNameEx = $proc->usuario_ex_nome ? explode(' ', $proc->usuario_ex_nome)[0] : '';
                                $encFinanceira = $proc->data_enc_af ? date('d/m/Y', strtotime($proc->data_enc_af)) : '';
                                $analiseFin = $proc->data_analise_fin ? date('d/m/Y', strtotime($proc->data_analise_fin)) : '';                                    
                                $firstNameFin = $proc->usuario_fin_nome ? explode(' ', $proc->usuario_fin_nome)[0] : '';
                                $sigpc = $proc->data_sigpc ? date('d/m/Y', strtotime($proc->data_sigpc)) : '';
                                
                                $numProcesso = $relatorioModel->formatarProcesso($proc);                                 
                                                       
                                $bgColor = match($tipoProc) {
                                    "PDDE Básico" => "bg-primary-subtle text-primary-emphasis",
                                    "PDDE Qualidade" => "bg-info-subtle text-info-emphasis",
                                    "PDDE Equidade" => "bg-success-subtle text-success-emphasis",
                                    "PDDE Educação Integral" => "bg-warning-subtle text-warning-emphasis",
                                    "PDDE PDE Escola" => "bg-dark-subtle text-dark-emphasis",
                                    default => ""
                                };
                                                                                                                
                                ?>
                                <tr class="fw-lighter align-middle">
                                    <td scope="row" class="text-center">
                                        <a href="?idProc=<?= $idProc ?>" class="link-body-emphasis link-offset-2 link-underline-opacity-25 link-underline-opacity-75-hover">
                                            <?= $numProcesso; ?>
                                        </a>
                                    </td>                                        
                                    <td scope="row" class="<?= $bgColor ?>"><?= $tipoProc ?></td>
                                    <td><?= $instProc ?></td>
                                    <td><?= $status ?></td>
                                    <td class="text-center"><?= $entrega ?></td>
                                    <td class="text-center"><?= $sMovimento ?></td>
                                    <td class="text-center"><?= $analiseEx ?></td>
                                    <td class="text-center"><?= $firstNameEx ?></td>
                                    <td class="text-center"><?= $encFinanceira ?></td>
                                    <td class="text-center"><?= $analiseFin ?></td>
                                    <td class="text-center"><?= $firstNameFin ?></td>
                                    <td class="text-center"><?= $sigpc ?></td>
                                </tr>
                                
                                <?php
                            }
                        }                        
                                     
                        ?>
                        
                    </tbody>
                </table>
                <div class="mt-2 text-end">
                    <small>Exibindo <?= $totalRegistros ?> registros.</small>
                </div>
                
            </div>

            <!-- Fim do Conteúdo -->
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>
    

    <script src="./js/script.js"></script>
    <script src="./js/bootstrap/bootstrap.bundle.min.js"></script>    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
<?php
ob_flush();
?>