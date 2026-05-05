<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\User;

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
        $firstName = substr($userName, 0, strpos($userName, " "));
    } else {
        session_destroy();
        header("Location: index.php?status=sessao_invalida");
        exit();
    }
}

// Rotas de saída
if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
    $_SESSION['flag'] = false;
    session_unset();
    header("Location:index.php?status=logoff");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">   
    
    <title>Início - Sistema</title>
    <style>
        /* Ajuste de fundo para a Home sem menu lateral */
        body { background-color: #f8f9fa; }

        h1 {
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }

        /* Animações dos Cards */
        .module-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            background-color: #ffffff;
        }
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
            border-color: #0d6efd;
        }
        .module-card i { transition: transform 0.3s ease; }
        .module-card:hover i { transform: scale(1.2); }
        .card-link-wrapper { text-decoration: none !important; color: inherit !important; }

        /* Estilo do menu de relatórios no Modal */
        .relatorio-item:hover {
            background-color: #f1f8ff;
            border-color: #0d6efd;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-white shadow-sm mb-4 py-3">
        <div class="container">
            <span class="navbar-brand fw-bold text-primary">
                <i class="lni lni-grid-alt me-2"></i> Bem Vindo, <?= htmlspecialchars($firstName) ?>!
            </span>
            <div class="ms-auto">
                <a href="#" data-bs-toggle="modal" data-bs-target="#logoffModal" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                    <i class="lni lni-exit"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="text-center my-4">            
            <p class="text-secondary fs-5">Selecione o módulo que deseja acessar</p>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 justify-content-center">
            
            <div class="col">
                <a href="buscar.php" class="card-link-wrapper">
                    <div class="card h-100 shadow-sm module-card text-center p-4 border-primary bg-light">
                        <div class="card-body">
                            <i class="lni lni-search fs-1 text-primary mb-3"></i>
                            <h5 class="card-title fw-bold text-primary">Buscar Processos</h5>
                            <p class="card-text text-muted small">Pesquise processos e veja o dashboard de acompanhamento.</p>
                        </div>
                    </div>
                </a>
            </div>

            <?php if ($perfil != "ofc"): ?>
            <div class="col">
                <a href="pddePC.php" class="card-link-wrapper">
                    <div class="card h-100 shadow-sm module-card text-center p-4">
                        <div class="card-body">
                            <i class="lni lni-files fs-1 text-warning mb-3"></i>
                            <h5 class="card-title fw-bold">Prestação de Contas PDDE</h5>
                            <p class="card-text text-muted small">Acesse e gerencie as prestações de contas do PDDE.</p>
                        </div>
                    </div>
                </a>
            </div>                    
            <div class="col">
                <a href="pddeFinanc.php" class="card-link-wrapper">
                    <div class="card h-100 shadow-sm module-card text-center p-4">
                        <div class="card-body">
                            <i class="lni lni-investment fs-1 text-success mb-3"></i>
                            <h5 class="card-title fw-bold">Análise Financeira</h5>
                            <p class="card-text text-muted small">Módulo exclusivo de análise financeira do PDDE.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($perfil != "ofp"): ?>
            <div class="col">
                <a href="termoPC.php" class="card-link-wrapper">
                    <div class="card h-100 shadow-sm module-card text-center p-4">
                        <div class="card-body">
                            <i class="lni lni-files fs-1 text-warning mb-3"></i>
                            <h5 class="card-title fw-bold">Prestação de Contas TC</h5>
                            <p class="card-text text-muted small">Acesse e gerencie as prestações de contas do TC.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <div class="col">
                <a href="gerarcota.php" class="card-link-wrapper">
                    <div class="card h-100 shadow-sm module-card text-center p-4">
                        <div class="card-body">
                            <i class="lni lni-pencil-alt fs-1 text-info mb-3"></i>
                            <h5 class="card-title fw-bold">Gerar Cota</h5>
                            <p class="card-text text-muted small">Emissão e geração de cotas para as instituições.</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="#" data-bs-toggle="modal" data-bs-target="#modalRelatorios" class="card-link-wrapper">
                    <div class="card h-100 shadow-sm module-card text-center p-4">
                        <div class="card-body">
                            <i class="lni lni-layout fs-1 text-secondary mb-3"></i>
                            <h5 class="card-title fw-bold">Relatórios</h5>
                            <p class="card-text text-muted small">Acesse relatórios e acompanhamento de pendências.</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <?php if ($perfil == "adm" || $perfil == "ges"): ?>
            <div class="col">
                <a href="dashboard_ajustes.php" class="card-link-wrapper">
                    <div class="card h-100 shadow-sm module-card text-center p-4">
                        <div class="card-body">
                            <i class="lni lni-layers fs-1 text-primary mb-3"></i>
                            <h5 class="card-title fw-bold">Gestão de Ajustes e Parcerias</h5>
                            <p class="card-text text-muted small">Visão geral e acompanhamento de instrumentos.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($perfil == "adm"): ?>
            <div class="col">
                <a href="gerenciamento.php" class="card-link-wrapper">
                    <div class="card h-100 shadow-sm module-card text-center p-4">
                        <div class="card-body">
                            <i class="lni lni-cog fs-1 text-danger mb-3"></i>
                            <h5 class="card-title fw-bold">Gerenciar Sistema</h5>
                            <p class="card-text text-muted small">Painel de controle e configurações do administrador.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

        </div>
    </div>      

    <div class="modal fade" id="modalRelatorios" tabindex="-1" aria-labelledby="modalRelatoriosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalRelatoriosLabel">
                        <i class="lni lni-layout text-primary me-2"></i>Relatórios
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="list-group list-group-flush gap-2">
                        
                        <?php if ($perfil != "ofc"): ?>
                        <a href="relatorio.php?Prg=0&St=0" class="list-group-item list-group-item-action rounded border relatorio-item d-flex align-items-center p-3">
                            <i class="lni lni-bar-chart fs-3 text-primary me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Prestação de Contas</h6>
                                <small class="text-muted">Relatório geral consolidado.</small>
                            </div>
                        </a>
                        
                        <a href="pendencia.php?Prg=0&Reg=2&user=99" class="list-group-item list-group-item-action rounded border relatorio-item d-flex align-items-center p-3">
                            <i class="lni lni-warning fs-3 text-warning me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Pendências PDDE</h6>
                                <small class="text-muted">Acompanhamento e listagem das pendências do PDDE.</small>
                            </div>
                        </a>                        

                        <a href="relatorio_despesas.php?Forn=0&Prg=0&Cat=0" class="list-group-item list-group-item-action rounded border relatorio-item d-flex align-items-center p-3">
                            <i class="lni lni-bar-chart fs-3 text-primary me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Relatório de Despesas</h6>
                                <small class="text-muted">Listagem detalhada das despesas.</small>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if ($perfil != "ofp"): ?>
                        <a href="pendenciaTc.php?Reg=2&user=99" class="list-group-item list-group-item-action rounded border relatorio-item d-flex align-items-center p-3">
                            <i class="lni lni-warning fs-3 text-danger me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Pendências TC</h6>
                                <small class="text-muted">Acompanhamento e listagem das pendências do Termo de Colaboração.</small>
                            </div>
                        </a>
                        <?php endif; ?>

                        </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="./js/script.js"></script>
</body>
</html>