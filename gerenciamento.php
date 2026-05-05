<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";
use Source\Models\User;

$userModel = new User();

if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}

$loggedUser = $userModel->findById($_SESSION['user_id']);
if (!$loggedUser || $loggedUser->perfil !== 'adm') {
    // Se não for admin, chuta de volta para o hub
    header("Location: hub.php?erro=acesso_negado");
    exit();
}

$userName = $loggedUser->nome;
$firstName = explode(' ', $userName)[0];
$perfil = $loggedUser->perfil;

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    <title>Gerenciamento | Admin</title>
    <style>
        .card-admin {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-align: center;
            padding: 2rem;
            height: 100%;
            background: #fff;
        }
        .card-admin:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
            background-color: #f8f9fa;
        }
        .icon-box {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #0e2238;
        }
        .card-title {
            font-weight: 700;
            color: #333;
        }
        .card-text {
            color: #777;
            font-size: 0.9rem;
        }
        h1.title-page {
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 48px;
            color: #0e2238;
        }
    </style>
</head>
<body>       
    <div class="wrapper">
        <?php include 'menu.php'; ?>

        <div class="main p-4">
            <div class="container">
                <header class="mb-5 text-center">
                    <h1 class="title-page">Painel de Gerenciamento</h1>
                    <p class="text-muted">Gestão estratégica do sistema PDDE</p>
                </header>
            
            
            <div class="row g-4">

                <div class="col-md-6 col-lg-4">
                    <a href="actionlogs.php" class="text-decoration-none">
                        <div class="card card-admin shadow-sm">
                            <div class="icon-box">
                                <i class="lni lni-pulse"></i>
                            </div>
                            <h5 class="card-title">Acessar Logs</h5>
                            <p class="card-text">Monitorar o histórico de ações e acessos no sistema.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <a href="usuarios.php" class="text-decoration-none">
                        <div class="card card-admin shadow-sm">
                            <div class="icon-box">
                                <i class="lni lni-users"></i>
                            </div>
                            <h5 class="card-title">Usuários</h5>
                            <p class="card-text">Gerenciar acessos, perfis e permissões da equipe.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <a href="instituicoes.php" class="text-decoration-none">
                        <div class="card card-admin shadow-sm">
                            <div class="icon-box">
                                <i class="lni lni-apartment"></i>
                            </div>
                            <h5 class="card-title">Instituições</h5>
                            <p class="card-text">Cadastrar e editar escolas e unidades de ensino.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <a href="contabilidades.php" class="text-decoration-none">
                        <div class="card card-admin shadow-sm">
                            <div class="icon-box">
                                <i class="lni lni-calculator"></i>
                            </div>
                            <h5 class="card-title">Contabilidades</h5>
                            <p class="card-text">Gerenciar cadastros de escritórios de contabilidade.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <a href="gerenciarcota.php" class="text-decoration-none">
                        <div class="card card-admin shadow-sm">
                            <div class="icon-box">
                                <i class="lni lni-list"></i>
                            </div>
                            <h5 class="card-title">Itens da Cota</h5>
                            <p class="card-text">Configurar documentos que compõem a geração de cotas.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <a href="gerenciarDocsPend.php" class="text-decoration-none">
                        <div class="card card-admin shadow-sm">
                            <div class="icon-box text-warning">
                                <i class="lni lni-warning"></i>
                            </div>
                            <h5 class="card-title">Documentos Pendentes</h5>
                            <p class="card-text">Gerenciar parametrização de documentos de pendência.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="importar_dados.php" class="text-decoration-none">
                        <div class="card card-admin shadow-sm">
                            <div class="icon-box text-success">
                                <i class="lni lni-cloud-upload"></i>
                            </div>
                            <h5 class="card-title">Importação em Massa</h5>
                            <p class="card-text">Upload de planilhas CSV para popular processos, repasses, ajustes, empenhos, pagamentos, etc...</p>
                        </div>
                    </a>
                </div>

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
</body>
</html>
<?php
ob_flush();
?>