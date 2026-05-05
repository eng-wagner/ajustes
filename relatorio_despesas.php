<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";
require_once __DIR__ . "/source/Helpers/Helpers.php";

use Source\Models\Despesa;
use Source\Models\User;
use Source\Models\Processo;

$despesaModel = new Despesa();
$processoModel = new Processo();
$userModel = new User();

// Verifica se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
} else {
    $loggedUser = $userModel->findById($_SESSION['user_id']);
    if ($loggedUser) {
        $userName = $loggedUser->nome;
        $perfil = $loggedUser->perfil;
    }
}

$filtroFornecedor = (isset($_REQUEST['Forn']) && $_REQUEST['Forn'] != '0') ? $_REQUEST['Forn'] : null;
$filtroPrograma   = (isset($_REQUEST['Prg']) && $_REQUEST['Prg'] != '0') ? $_REQUEST['Prg'] : null;
$filtroCategoria  = (isset($_REQUEST['Cat']) && $_REQUEST['Cat'] != '0') ? $_REQUEST['Cat'] : null;

$fornecedores = $despesaModel->findAllFornecedores();
$dados = $despesaModel->buscarDespesasRelatorio($filtroFornecedor, $filtroPrograma, $filtroCategoria);
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
    <title>Relatório de Despesas</title>
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
        <div class="main p-3">
            <div class="text-center">
                <h1>Relatório de Despesas</h1>
            </div>
            <!-- Início do Conteúdo -->
            <hr>
            <div class="container-fluid">                
                <form method="get" class="row g-3 align-items-center mb-3">
                    <div class="col-auto">
                        <label class="form-label fw-bold" for="selectForn">Filtrar por Fornecedor:</label>
                        <select class="form-select form-select-sm" name="Forn" id="selectForn" onchange="this.form.submit()">
                            <option value="0">Todos os Fornecedores</option>
                            <?php
                            if(!empty($fornecedores)) {
                                foreach($fornecedores as $forn) : ?>                                    
                                    <option value="<?=  $forn->id ?>" <?= ($filtroFornecedor == $forn->id ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($forn->razao_social) ?>
                                    </option>
                                <?php endforeach;
                            } ?>
                        </select>
                    </div>

                    <div class="col-auto">
                        <label class="form-label fw-bold text-muted small mb-0">Programa:</label>
                        <select class="form-select form-select-sm" name="Prg" onchange="this.form.submit()">
                            <option value="0">Todos</option>
                            <option value="PDDE Básico" <?= ($filtroPrograma == "PDDE Básico" ? 'selected' : '') ?>>PDDE Básico</option>
                            <option value="PDDE Qualidade" <?= ($filtroPrograma == "PDDE Qualidade" ? 'selected' : '') ?>>PDDE Qualidade</option>
                            <option value="PDDE Equidade" <?= ($filtroPrograma == "PDDE Equidade" ? 'selected' : '') ?>>PDDE Equidade</option>
                            <option value="PDDE Educação Integral" <?= ($filtroPrograma == "PDDE Educação Integral" ? 'selected' : '') ?>>PDDE Ed. Integral</option>
                            <option value="PDDE PDE Escola" <?= ($filtroPrograma == "PDDE PDE Escola" ? 'selected' : '') ?>>PDDE PDE-Escola</option>
                        </select>
                    </div>

                    <div class="col-auto">
                        <label class="form-label fw-bold text-muted small mb-0">Categoria:</label>
                        <select class="form-select form-select-sm" name="Cat" onchange="this.form.submit()">
                            <option value="0">Todas</option>
                            <option value="C" <?= ($filtroCategoria == 'C' ? 'selected' : '') ?>>Custeio</option>
                            <option value="K" <?= ($filtroCategoria == 'K' ? 'selected' : '') ?>>Capital</option>
                        </select>
                    </div>

                    <div class="col-auto align-self-end mt-4">
                        <a href="relatorio_despesas.php?Forn=0&Prg=0&Cat=0" class="btn btn-sm btn-outline-secondary" title="Limpar Filtros"><i class="bi bi-eraser"></i> Limpar</a>
                    </div>
                    <div class="col-auto align-self-end mt-4">
                        <a href="relatorio_despesas_excel.php?Forn=<?= $filtroFornecedor ?? '0' ?>&Prg=<?= $filtroPrograma ?? '0' ?>&Cat=<?= $filtroCategoria ?? '0' ?>" class="btn btn-sm btn-outline-success" onclick="toastGerandoExcel()">
                            <i class="lni lni-download"></i> Excel
                        </a>
                    </div>
                    <div class="col-auto align-self-end ms-auto mt-4">
                        <span class="badge bg-secondary fs-6">Registros: <strong><?= $totalRegistros ?></strong></span>
                    </div>
                </form>                   
            <hr>
            <div class="container-fluid">
                <table class="table table-sm table-hover table-bordered m-auto">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th class="fw-semibold">Nº Processo</th>
                            <th class="fw-semibold">Programa</th>
                            <th class="fw-semibold">Categoria</th>
                            <th class="fw-semibold">Fornecedor</th>
                            <th class="fw-semibold">Aquisição</th>
                            <th class="fw-semibold">Nº Doc</th>
                            <th class="fw-semibold">Data</th>
                            <th class="fw-semibold">Valor (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(!empty($dados)) {
                            // Variável para somar o total da página
                            $somaTotal = 0;

                            foreach($dados as $desp) {                                
                                $numProcesso = $processoModel->formatarProcesso($processoModel->findById($desp->proc_id) ?? 'N/D');
                                $programa = $desp->programa_nome ?? 'N/D';
                                $categoria = ($desp->categoria == 'C') ? 'Custeio' : (($desp->categoria == 'K') ? 'Capital' : 'N/D');
                                $fornecedor = $desp->razao_social ?? 'N/D';
                                $aquisicao = $desp->descricao ?? '';
                                $numDoc = $desp->documento ?? '';
                                $data = $desp->data_desp ? date('d/m/Y', strtotime($desp->data_desp)) : '';
                                $valor = $desp->valor ?? 0;
                                
                                $somaTotal += $valor;
                                ?>
                                <tr class="fw-lighter align-middle">
                                    <td class="text-center"><?= htmlspecialchars($numProcesso) ?></td>                                        
                                    <td class="text-center"><?= htmlspecialchars($programa) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($categoria) ?></td>
                                    <td><?= htmlspecialchars($fornecedor) ?></td>
                                    <td><?= htmlspecialchars($aquisicao) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($numDoc) ?></td>
                                    <td class="text-center"><?= $data ?></td>
                                    <td class="text-end"><?= number_format($valor, 2, ',', '.') ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr class="fw-bold table-light">
                                <td colspan="7" class="text-end">TOTAL:</td>
                                <td class="text-end text-success">R$ <?= number_format($somaTotal, 2, ',', '.') ?></td>
                            </tr>
                            <?php
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-3'>Nenhuma despesa encontrada para este filtro.</td></tr>";
                        }                        
                        ?>
                    </tbody>
                </table>
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
function toastGerandoExcel() {
    // Esse é exatamente o mesmo SweetAlert que está no seu toasts.php!
    Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        icon: 'success', // Deixei verde igual ao seu sucesso!
        title: 'Gerando arquivo Excel...',
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
}
</script>
</body>
</html>
<?php ob_flush(); ?>