<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Models\User;
use Source\Models\Logs;

$userModel = new User();
$logModel = new Logs();

$timezone = new DateTimeZone("America/Sao_Paulo");

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
    <title>Logs do Sistema</title>
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'menu.php'; ?>
        <div class="main p-3">
            <div class="container-fluid">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h1 class="title-page mb-0">Logs do Sistema</h1>
                        <p class="text-muted">Histórico de acessos e ações dos usuários</p>
                    </div>
                    <div class="mt-3 mt-md-0 w-100" style="max-width: 300px;">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="lni lni-search-alt"></i></span>
                            <input type="text" id="buscaLogs" class="form-control border-start-0" placeholder="Buscar nos logs...">
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                            <table class="table table-hover table-striped mb-0" id="tabelaLogs">
                                <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th scope="col" class="text-center py-3">Id</th>
                                        <th scope="col" class="text-center py-3">Matricula/Usuário</th>
                                        <th scope="col" class="py-3">Ação</th>
                                        <th scope="col" class="py-3">Data/Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $logs = $logModel->all();                        
                                    if ($logs) {
                                        foreach($logs as $log) {
                                            $logId = $log->id;
                                            $matUsuario = $log->usuario;
                                            $logAcao = $log->acao;
                                            
                                            $dt = new DateTime($log->hora, new DateTimeZone('UTC'));
                                            $dt->setTimezone($timezone);
                                            $horaAcao = $dt->format('d/m/Y - H:i:s');
                                            
                                            echo '<tr>';
                                            echo '<td class="text-center align-middle">' . $logId . '</td>';                            
                                            echo '<td class="text-center align-middle fw-bold">'. $matUsuario . '</td>';
                                            echo '<td class="align-middle text-muted">' . $logAcao . '</td>';
                                            echo '<td class="align-middle text-nowrap">' . $horaAcao . '</td>';                            
                                            echo '</tr>';                               
                                        }
                                    } else {
                                        // Estado vazio muito mais amigável
                                        echo '<tr><td colspan="4" class="text-center text-muted py-5">';
                                        echo '<i class="lni lni-empty-file fs-1 d-block mb-3"></i> Nenhum log registrado no momento.';
                                        echo '</td></tr>';
                                    }                                          
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="text-end mt-2">
                    <small class="text-muted">Mostrando resultados recentes.</small>
                </div>
            </div>
            <!-- Fim do Conteúdo  -->              
        </div>        
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>
    
    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    // Filtro instantâneo na tabela
    document.addEventListener("DOMContentLoaded", function() {
        const inputBusca = document.getElementById('buscaLogs');
        const tbody = document.querySelector('#tabelaLogs tbody');
        let cacheLinhas = [];
        let timeoutDeBusca = null;

        // 1. Cria o cache apenas uma vez quando a página carrega
        const linhas = tbody.querySelectorAll('tr');
        linhas.forEach(function(linha) {
            if (linha.cells.length > 1) { // Ignora a linha de "nenhum log"
                cacheLinhas.push({
                    elemento: linha,
                    texto: linha.innerText.toLowerCase()
                });
            }
        });

        // 2. Aplica o filtro com "Debounce" (espera parar de digitar)
        inputBusca.addEventListener('input', function() {
            clearTimeout(timeoutDeBusca); // Cancela a busca anterior se ainda estiver digitando
            
            let filtro = this.value.toLowerCase();
            
            // Espera 300ms após a última tecla para processar
            timeoutDeBusca = setTimeout(function() {
                // Oculta a tabela durante o filtro para o navegador não ter que redesenhar a tela a cada linha (muito mais rápido)
                tbody.style.display = 'none'; 
                
                cacheLinhas.forEach(function(item) {
                    if (item.texto.includes(filtro)) {
                        item.elemento.style.display = '';
                    } else {
                        item.elemento.style.display = 'none';
                    }
                });
                
                // Mostra a tabela de novo
                tbody.style.display = ''; 
            }, 300);
        });
    });
    </script>
</body>
</html>
<?php
ob_flush();
?>