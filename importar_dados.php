<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";
use Source\Models\User;

$userModel = new User();

// Verificação de Sessão e Admin
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}

$loggedUser = $userModel->findById($_SESSION['user_id']);
if (!$loggedUser || $loggedUser->perfil !== 'adm') {
    header("Location: hub.php?erro=acesso_negado");
    exit();
}

$userName = $loggedUser->nome;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="./css/style.css">
    <title>Importação de Dados | Admin</title>
    <style>
        .import-card {
            border: none;
            border-radius: 15px;
            background: #fff;
            padding: 30px;
        }
        .nav-pills .nav-link {
            color: #0e2238;
            font-weight: 500;
            border-radius: 10px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        .nav-pills .nav-link.active {
            background-color: #0e2238;
        }
        .instruction-box {
            background-color: #f8f9fa;
            border-left: 4px solid #0e2238;
            padding: 15px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu.php'; ?>

        <div class="main p-4">
            <div class="container">
                <header class="mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold" style="color: #0e2238;">Importação em Massa</h2>
                        <p class="text-muted">Atualize o banco de dados via arquivos CSV</p>
                    </div>
                    <a href="gerenciamento.php" class="btn btn-outline-secondary">
                        <i class="lni lni-arrow-left"></i> Voltar
                    </a>
                </header>

                <div class="card import-card shadow-sm">
                    <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-processos" data-bs-toggle="pill" data-bs-target="#content-processos" type="button">
                                <i class="lni lni-files me-1"></i> Processos
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-ajustes" data-bs-toggle="pill" data-bs-target="#content-ajustes" type="button">
                                <i class="lni lni-pencil-alt me-1"></i> Ajustes
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-empenhos" data-bs-toggle="pill" data-bs-target="#content-empenhos" type="button">
                                <i class="lni lni-coin me-1"></i> Empenhos
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-pagamentos" data-bs-toggle="pill" data-bs-target="#content-pagamentos" type="button">
                                <i class="lni lni-dollar me-1"></i> Pagamentos
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-4">
                        
                        <div class="tab-pane fade show active" id="content-processos">
                            <div class="instruction-box mb-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><i class="lni lni-information me-1"></i> Formato do CSV para Processos:</strong><br>
                                    Colunas: <code>numero ; ano ; digito ; assunto ; tipo ; detalhamento ; instituicao_id</code><br>
                                    <span class="text-danger small">* O sistema cancelará a importação se encontrar um número de processo já existente.</span>
                                </div>
                                <div>
                                    <a href="./modelos/modelo_processos.csv" download="modelo_processos.csv" class="btn btn-outline-primary btn-sm">
                                        <i class="lni lni-download me-1"></i> Baixar Modelo CSV
                                    </a>
                                </div>
                            </div>
                            <form action="processa_importacao.php?tipo=processos" method="POST" enctype="multipart/form-data">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-9">
                                        <label class="form-label fw-bold">Selecione o arquivo CSV</label>
                                        <input type="file" name="arquivo_csv" class="form-control form-control-lg" accept=".csv" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-success btn-lg w-100">
                                            <i class="lni lni-cloud-upload me-2"></i> Importar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="content-ajustes">
                            <div class="instruction-box mb-4 text-center">
                                <p class="mb-0">Módulo de importação de <strong>Ajustes</strong> em desenvolvimento.</p>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="content-empenhos">
                            <div class="instruction-box mb-4 text-center">
                                <p class="mb-0">Módulo de importação de <strong>Empenhos</strong> em desenvolvimento.</p>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="content-pagamentos">
                            <div class="instruction-box mb-4 text-center">
                                <p class="mb-0">Módulo de importação de <strong>Pagamentos</strong> em desenvolvimento.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Script simples para mostrar alerta de sucesso ou erro vindo da URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('sucesso')) {
            Swal.fire('Sucesso!', 'Os dados foram importados corretamente.', 'success');
        } else if (urlParams.has('erro')) {
            Swal.fire('Erro!', urlParams.get('msg') || 'Erro na importação.', 'error');
        }
    </script>
</body>
</html>