<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";

use Source\Database\Connect;

$timezone = new DateTimeZone("America/Sao_Paulo");
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

    if ($_SESSION['flag'] == false) {
        header("Location:index.php");
    }

    if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
        $_SESSION['flag'] = false;
        session_unset();
        header("Location:index.php");
    }

    $sql = Connect::getInstance()->prepare("SELECT nome, perfil FROM usuarios WHERE id = :idUser");
    $sql->bindParam('idUser', $_SESSION['user_id']);
    if ($sql->execute()) {
        if ($proc = $sql->fetch()) {
            $userName = $proc->nome;
            $perfil = $proc->perfil;
        }
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
                <div class="row p-0">
                    <h6 class="text-center col-11">Filtros</h6>
                </div>
                <div class="row mb-1">
                    <div class="col-1">
                        <h6>Programas</h6>
                    </div>
                    <div class="col-7">
                        <?php
                        if (isset($_REQUEST['Prg'])) {
                            $btnProgChk = ['', '', '', '', '', ''];
                            $btnProgChk[$_REQUEST['Prg']] = 'checked';

                            switch ($_REQUEST['Prg']) {
                                case 0:
                                    $programa = "Todos";
                                    break;
                                case 1:
                                    $programa = "PDDE Básico";
                                    break;
                                case 2:
                                    $programa = "PDDE Qualidade";
                                    break;
                                case 3:
                                    $programa = "PDDE Equidade";
                                    break;
                                case 4:
                                    $programa = "PDDE Educação Integral";
                                    break;
                                case 5:
                                    $programa = "PDDE PDE Escola";
                                    break;
                            }
                        }
                        if (isset($_REQUEST['St'])) {
                            $btnStsChk = ['', '', '', '', '', '', '', ''];
                            $btnStsChk[$_REQUEST['St']] = 'checked';
                        }

                        ?>
                        <input type="radio" class="btn-check" name="btnProg" id="btnTodos" autocomplete="off" onclick="location.href='?Prg=0&St=<?= $_REQUEST['St'] ?>'" <?= $btnProgChk[0]  ?>>
                        <label class="btn btn-sm btn-outline-secondary" for="btnTodos">Todos</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnBasico" autocomplete="off" onclick="location.href='?Prg=1&St=<?= $_REQUEST['St'] ?>'" <?= $btnProgChk[1] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-primary" for="btnBasico">PDDE Básico</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnQual" autocomplete="off" onclick="location.href='?Prg=2&St=<?= $_REQUEST['St'] ?>'" <?= $btnProgChk[2] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-info" for="btnQual">PDDE Qualidade</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnEstr" autocomplete="off" onclick="location.href='?Prg=3&St=<?= $_REQUEST['St'] ?>'" <?= $btnProgChk[3] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-success" for="btnEstr">PDDE Equidade</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnEdInt" autocomplete="off" onclick="location.href='?Prg=4&St=<?= $_REQUEST['St'] ?>'" <?= $btnProgChk[4] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-warning" for="btnEdInt">PDDE Ed. Integral</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnPDE" autocomplete="off" onclick="location.href='?Prg=5&St=<?= $_REQUEST['St'] ?>'" <?= $btnProgChk[5] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-dark" for="btnPDE">PDDE PDE-Escola</label>
                    </div>
                </div>

                <div class="row">
                    <div class="col-1">
                        <h6>Status</h6>
                    </div>
                    <div class="col-10">
                        <input type="radio" class="btn-check" name="btnStatus" id="btnAll" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&St=0'" <?= $btnStsChk[0] ?>>
                        <label class="btn btn-sm btn-outline-secondary" for="btnAll">Todos</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS1" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&St=1'" <?= $btnStsChk[1] ?>>
                        <label class="btn btn-sm btn-outline-warning" for="btnS1">Aguardando Entrega</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS2" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&St=2'" <?= $btnStsChk[2] ?>>
                        <label class="btn btn-sm btn-outline-success" for="btnS2">Entregue</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS3" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&St=3'" <?= $btnStsChk[3] ?>>
                        <label class="btn btn-sm btn-outline-info" for="btnS3">Análise da Execução</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS4" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&St=4'" <?= $btnStsChk[4] ?>>
                        <label class="btn btn-sm btn-outline-danger" for="btnS4">Pendências na Análise</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS5" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&St=5'" <?= $btnStsChk[5] ?>>
                        <label class="btn btn-sm btn-outline-primary" for="btnS5">Análise Financeira</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS6" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&St=6'" <?= $btnStsChk[6] ?>>
                        <label class="btn btn-sm btn-outline-success" for="btnS6">Análise Financeira Concluída</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS7" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&St=7'" <?= $btnStsChk[7] ?>>
                        <label class="btn btn-sm btn-outline-dark" for="btnS7">Concluído</label>
                    </div>
                </div>
            </div>
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
                        //$nItem = 0;
                        //$total = 0;                                            
                        //$despesasPendentes = 0; 
                        if (isset($_REQUEST['Prg']) && $_REQUEST['Prg'] != null) {
                            if ($_REQUEST['Prg'] == 0) {
                                $stmt = "SELECT p.id, p.orgao, p.numero, p.ano, p.digito, p.tipo, i.instituicao FROM processos p JOIN instituicoes i ON p.instituicao_id = i.id WHERE p.tipo LIKE '%PDDE%' ORDER BY i.instituicao ASC" ;
                                $sql = Connect::getInstance()->prepare($stmt);
                            } else {
                                $stmt = "SELECT p.id, p.orgao, p.numero, p.ano, p.digito, p.tipo, i.instituicao FROM processos p JOIN instituicoes i ON p.instituicao_id = i.id WHERE p.tipo = :programa";
                                $sql = Connect::getInstance()->prepare($stmt);
                                $sql->bindParam('programa', $programa);
                            }
                            //$sql = Connect::getInstance()->prepare($stmt);
                            //$sql->bindParam('programa',$programa);
                            if ($sql->execute()) {
                                while ($proc = $sql->fetch()) {
                                    $idProc = $proc->id;
                                    $orgaoProc = $proc->orgao;
                                    $numProc = $proc->numero;
                                    $anoProc = $proc->ano;
                                    $digitoProc = $proc->digito;
                                    $tipoProc = $proc->tipo;
                                    $instProc = $proc->instituicao;


                                    $idStatus = 1;
                                    $status = "";
                                    $entrega = "";
                                    $sMovimento = "";
                                    $analiseEx = "";
                                    $idUserEx = "";
                                    $usuarioEx = "";
                                    $encFinanceira = "";
                                    $analiseFin = "";
                                    $idUserFin = "";
                                    $usuarioFin = "";
                                    $sigpc = "";


                                    $stmt = Connect::getInstance()->prepare("SELECT * FROM analise_pdde_24 WHERE proc_id = :idProc");
                                    $stmt->bindParam('idProc', $idProc);
                                    if ($stmt->execute()) {
                                        if ($proc = $stmt->fetch()) {
                                            $idAnalise = $proc->id;
                                            $idStatus = $proc->status_id;
                                            $dtEntrega = $proc->data_ent;
                                            $idUserEx = $proc->usuario_ex_id;
                                            $dtAnaliseEx = $proc->data_analise_ex;
                                            $dtEncFin = $proc->data_enc_af;
                                            $sMovimento = $proc->s_movimento;
                                            $idUserFin = $proc->usuario_fin_id;
                                            $dtAnaliseFin = $proc->data_analise_fin;
                                            $dtSigpc = $proc->data_sigpc;

                                            if (isset($dtEntrega) && $dtEntrega != null) {
                                                $entrega = new DateTime($dtEntrega, $timezone);
                                                $entrega = $entrega->format('d/m/Y');
                                            }

                                            if (isset($dtAnaliseEx) && $dtAnaliseEx != null) {
                                                $analiseEx = new DateTime($dtAnaliseEx, $timezone);
                                                $analiseEx = $analiseEx->format('d/m/Y');
                                            }

                                            if (isset($dtEncFin) && $dtEncFin != null) {
                                                $encFinanceira = new DateTime($dtEncFin, $timezone);
                                                $encFinanceira = $encFinanceira->format('d/m/Y');
                                            }

                                            if (isset($dtAnaliseFin) && $dtAnaliseFin != null) {
                                                $analiseFin = new DateTime($dtAnaliseFin, $timezone);
                                                $analiseFin = $analiseFin->format('d/m/Y');
                                            }

                                            if (isset($dtSigpc) && $dtSigpc != null) {
                                                $sigpc = new DateTime($dtSigpc, $timezone);
                                                $sigpc = $sigpc->format('d/m/Y');
                                            }
                                        }

                                        if (isset($idStatus) && $idStatus != 0) {
                                            $stmt = Connect::getInstance()->prepare("SELECT status_pc FROM status_processo WHERE id = :idStatus");
                                            $stmt->bindParam('idStatus', $idStatus);
                                            if ($stmt->execute()) {
                                                if ($proc = $stmt->fetch()) {
                                                    $status = $proc->status_pc;
                                                }
                                            }
                                        } else {
                                            $status = "Aguardando Entrega";
                                        }

                                        if (isset($idUserEx) && $idUserEx != 0) {
                                            $stmt = Connect::getInstance()->prepare("SELECT nome FROM usuarios WHERE id = :idUserEx");
                                            $stmt->bindParam('idUserEx', $idUserEx);
                                            if ($stmt->execute()) {
                                                if ($userEx = $stmt->fetch()) {
                                                    $usuarioEx = $userEx->nome;
                                                }
                                            }
                                        }
                                        $firstNameEx = substr($usuarioEx, 0, strpos($usuarioEx, " "));

                                        if (isset($idUserFin) && $idUserFin != 0) {
                                            $stmt = Connect::getInstance()->prepare("SELECT nome FROM usuarios WHERE id = :idUserFin");
                                            $stmt->bindParam('idUserFin', $idUserFin);
                                            if ($stmt->execute()) {
                                                if ($userFin = $stmt->fetch()) {
                                                    $usuarioFin = $userFin->nome;
                                                }
                                            }
                                        }
                                        $firstNameFin = substr($usuarioFin, 0, strpos($usuarioFin, " "));

                        ?>
                                        <?php


                                        if (isset($_REQUEST['St']) && $_REQUEST['St'] != null) {
                                            if ($_REQUEST['St'] == $idStatus) {
                                        ?>
                                                <tr class="fw-lighter align-middle">
                                                    <td scope="row" class="text-center"><a href="?idProc=<?= $idProc ?>" class="link-body-emphasis link-offset-2 link-underline-opacity-25 link-underline-opacity-75-hover"><?= $orgaoProc . '.' . $numProc . '/' . $anoProc . '-' . $digitoProc; ?></a></td>
                                                    <?php
                                                    switch ($tipoProc) {
                                                        case "PDDE Básico":
                                                            $bgColor = "bg-primary-subtle text-primary-emphasis";
                                                            break;
                                                        case "PDDE Qualidade":
                                                            $bgColor = "bg-info-subtle text-info-emphasis";
                                                            break;
                                                        case "PDDE Equidade":
                                                            $bgColor = "bg-success-subtle text-success-emphasis";
                                                            break;
                                                        case "PDDE Educação Integral":
                                                            $bgColor = "bg-warning-subtle text-warning-emphasis";
                                                            break;
                                                        case "PDDE PDE Escola":
                                                            $bgColor = "bg-dark-subtle text-dark-emphasis";
                                                            break;
                                                    }
                                                    ?>
                                                    <td scope="row" class="<?= $bgColor ?? ''; ?>"><?= $tipoProc ?? ''; ?></td>
                                                    <td class=""><?= $instProc ?? ''; ?></td>
                                                    <td class=""><?= $status ?? ''; ?></td>
                                                    <td class="text-center"><?= $entrega ?? ''; ?></td>
                                                    <td class="text-center"><?= ($sMovimento == 1) ? 'Sem movimento' : '' ?></td>
                                                    <td class="text-center"><?= $analiseEx ?? ''; ?></td>
                                                    <td class="text-center"><?= $firstNameEx ?? ''; ?></td>
                                                    <td class="text-center"><?= $encFinanceira ?? ''; ?></td>
                                                    <td class="text-center"><?= $analiseFin ?? ''; ?></td>
                                                    <td class="text-center"><?= $firstNameFin ?? ''; ?></td>
                                                    <td class="text-center"><?= $sigpc ?? ''; ?></td>
                                                </tr>
                                            <?php
                                                $nItem = $nItem + 1;
                                            } elseif ($_REQUEST['St'] == 0) {
                                            ?>
                                                <tr class="fw-lighter align-middle">
                                                    <td scope="row" class="text-center"><a href="?idProc=<?= $idProc ?>" class="link-body-emphasis link-offset-2 link-underline-opacity-25 link-underline-opacity-75-hover"><?= $orgaoProc . '.' . $numProc . '/' . $anoProc . '-' . $digitoProc; ?></a></td>
                                                    <?php
                                                    switch ($tipoProc) {
                                                        case "PDDE Básico":
                                                            $bgColor = "bg-primary-subtle text-primary-emphasis";
                                                            break;
                                                        case "PDDE Qualidade":
                                                            $bgColor = "bg-info-subtle text-info-emphasis";
                                                            break;
                                                        case "PDDE Equidade":
                                                            $bgColor = "bg-success-subtle text-success-emphasis";
                                                            break;
                                                        case "PDDE Educação Integral":
                                                            $bgColor = "bg-warning-subtle text-warning-emphasis";
                                                            break;
                                                        case "PDDE PDE Escola":
                                                            $bgColor = "bg-dark-subtle text-dark-emphasis";
                                                            break;
                                                    }
                                                    ?>
                                                    <td scope="row" class="<?= $bgColor ?? ''; ?>"><?= $tipoProc ?? ''; ?></td>
                                                    <td class=""><?= $instProc ?? ''; ?></td>
                                                    <td class=""><?= $status ?? ''; ?></td>
                                                    <td class="text-center"><?= $entrega ?? ''; ?></td>
                                                    <td class="text-center"><?= ($sMovimento == 1) ? 'Sem movimento' : '' ?></td>
                                                    <td class="text-center"><?= $analiseEx ?? ''; ?></td>
                                                    <td class="text-center"><?= $firstNameEx ?? ''; ?></td>
                                                    <td class="text-center"><?= $encFinanceira ?? ''; ?></td>
                                                    <td class="text-center"><?= $analiseFin ?? ''; ?></td>
                                                    <td class="text-center"><?= $firstNameFin ?? ''; ?></td>
                                                    <td class="text-center"><?= $sigpc ?? ''; ?></td>
                                                </tr>
                        <?php
                                                $nItem = $nItem + 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        ?>
                        <span class="border m-2 p-1 border-dark">Registros: <?= $nItem ?></span>
                    </tbody>
                </table>
            </div>

            <!-- Fim do Conteúdo -->
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="menuModalLabel">Deseja voltar ao menu?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!--<div class="modal-body">
                    Deseja realmente sair?
                </div>-->
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="location.href='dashboard.php'">SIM</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">NÃO</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Sair -->
    <div class="modal fade" id="logoffModal" tabindex="-1" aria-labelledby="logoffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="logoffModalLabel">Deseja realmente sair?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!--<div class="modal-body">
                    Deseja realmente sair?
                </div>-->
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="location.href='?logoff=true'">SIM</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">NÃO</button>
                </div>
            </div>
        </div>
    </div>    

    <footer style="position: fixed; left: 0; bottom: 0; width: 100%; text-align: center;">
        <font color="#575756"><small>© Copyright - Secretaria de Educação - São Bernardo do Campo | 2024. Todos os Direitos Reservados.</small></font>
    </footer>

    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>    
</body>

</html>
<?php
ob_flush();
?>