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
    <title>Consulta de Pendências</title>
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

    $sql = Connect::getInstance()->prepare("SELECT nome, perfil FROM usuarios WHERE id = :idUser");
    $sql->bindParam('idUser', $_SESSION['user_id']);
    if ($sql->execute()) {
        if ($proc = $sql->fetch()) {
            $userName = $proc->nome;
            $perfil = $proc->perfil;
        }
    }

    $firstName = substr($userName, 0, strpos($userName, " "));

    if (isset($_REQUEST['pddeAE']) && $_REQUEST['pddeAE'] == true) {
        $_SESSION['nav'] = array("active", "", "", "", "");
        $_SESSION['navShow'] = array("show active", "", "", "", "");
        $_SESSION['sel'] = array("true", "false", "false", "false", "false");
        header("Location:pddePC.php");
    }

    if (isset($_REQUEST['pddeAF']) && $_REQUEST['pddeAF'] == true) {
        $_SESSION['navF'] = array("active", "", "", "", "", "");
        $_SESSION['navShowF'] = array("show active", "", "", "", "", "");
        $_SESSION['selF'] = array("true", "false", "false", "false", "false", "false");
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
                    Consulta de Pendências
                </h1>
            </div>
            <!-- Início do Conteúdo -->

            <div class="container-fluid">
                <div class="row p-0">
                    <h6 class="text-center col-11">Filtros</h6>
                </div>
                <div class="row mb-1">
                    <div class="col-4">
                        <?php
                        if (isset($_REQUEST['Reg'])) {
                                $btnRegChk = ['', '', ''];
                                $btnRegChk[$_REQUEST['Reg']] = 'checked';
                            }
                            ?>
                        <div class="input-group input-group-sm">
                            <label class="input-group-text col-2" for="inputGroup-user"><b>Usuário</b></label>
                            <form method="get" action=''>
                                <input type="hidden" name="Reg" value="<?= $_REQUEST['Reg'] ?>">
                                <select name="user" class="form-select col-10" id="inputGroup-user" onchange='this.form.submit()'>
                                    <option value="99" selected>Todos</option>
                                    <?php
                                    $sql = Connect::getInstance()->prepare("SELECT DISTINCT p.usuario_id, u.nome FROM pendencias_tc24 p JOIN usuarios u ON p.usuario_id = u.id");
                                    if ($sql->execute()) {
                                        while ($user = $sql->fetch()) {
                                            $idUsuario = $user->usuario_id;
                                            $nomeUsuario = $user->nome;
                                            $primeiroNome = substr($nomeUsuario, 0, strpos($nomeUsuario, " "));
                                            if (isset($_REQUEST['user']) && $_REQUEST['user'] == $idUsuario) {
                                                echo '<option value="' . $idUsuario . '" selected>' . $primeiroNome . '</option>';
                                            } else {
                                                echo '<option value="' . $idUsuario . '">' . $primeiroNome . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="col-6">                        
                        <h6>Regularizado</h6>
                        <input type="radio" class="btn-check" name="btnStatus" id="btnAll" autocomplete="off" onclick="location.href='?Reg=2&user=<?= $_REQUEST['user'] ?>'" <?= $btnRegChk[2] ?>>
                        <label class="btn btn-sm btn-outline-secondary" for="btnAll">Todos</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS1" autocomplete="off" onclick="location.href='?Reg=1&user=<?= $_REQUEST['user'] ?>'" <?= $btnRegChk[1] ?>>
                        <label class="btn btn-sm btn-outline-success" for="btnS1">SIM</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS2" autocomplete="off" onclick="location.href='?Reg=0&user=<?= $_REQUEST['user'] ?>'" <?= $btnRegChk[0] ?>>
                        <label class="btn btn-sm btn-outline-danger" for="btnS2">NÃO</label>                        
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-success" onclick="location.href='pendenciaTc_excel.php'">Exportar</button>
                    </div>
                </div>
            </div>
            <hr>

            <div class="container-fluid">
                <table class="table table-sm table-hover m-auto">
                    <thead>
                        <tr class="text-center align-middle">
                            <th class="col w-auto fw-semibold">Data</th>
                            <th class="col w-auto fw-semibold">Responsável</th>
                            <th class="col w-auto fw-semibold">Instituição</th>
                            <th class="col w-auto fw-semibold">Item do DRD</th>
                            <th class="col w-auto fw-semibold">Fornecedor</th>
                            <th class="col w-auto fw-semibold">Documento</th>
                            <th class="col w-auto fw-semibold">Pendência</th>
                            <th class="col w-auto fw-semibold">Providências</th>
                            <th class="col w-auto fw-semibold">Regularizado</th>
                            <th class="col w-auto fw-semibold">Data Regularização</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $nItem = 0;
                        $stmt = "SELECT s.id, u.id AS iduser, u.nome, s.dataPend, s.itemDRD, t.documento, s.fornecedor, d.pendencia, s.providencias, s.resolvido, s.dataResolvido, i.instituicao FROM pendencias_tc24 s JOIN processos p ON s.proc_id = p.id JOIN instituicoes i ON p.instituicao_id = i.id JOIN usuarios u ON s.usuario_id = u.id JOIN tipo_documento t ON s.docPend_id = t.id JOIN tipo_pendencia d ON s.pend_id = d.id WHERE s.ativado = 1";
                        $sql = Connect::getInstance()->prepare($stmt);
                        if ($sql->execute()) {
                            while ($pend = $sql->fetch()) {
                                $idPend = $pend->id;
                                $usuario = $pend->iduser;
                                $responsavel = $pend->nome;
                                $dataPend = $pend->dataPend;
                                $itemDPend = $pend->itemDRD;
                                $docPend = $pend->documento;
                                $fornecedor = $pend->fornecedor;
                                $pendencia = $pend->pendencia;
                                $providencias = $pend->providencias;
                                $resolvido = $pend->resolvido;
                                $dResolvido = $pend->dataResolvido;
                                $instituicao = $pend->instituicao;

                                if (isset($dataPend) && $dataPend != null) {
                                    $dataPendencia = new DateTime($dataPend, $timezone);
                                    $dataPendencia = $dataPendencia->format('d/m/Y');
                                }

                                if (isset($dResolvido) && $dResolvido != null) {
                                    $dataResolvido = new DateTime($dResolvido, $timezone);
                                    $dataResolvido = $dataResolvido->format('d/m/Y');
                                } else {
                                    $dataResolvido = "";
                                }

                                $firstName = substr($responsavel, 0, strpos($responsavel, " "));

                                if (isset($_REQUEST['Reg']) && $_REQUEST['Reg'] != null) {
                                    if ($_REQUEST['Reg'] == $resolvido && $_REQUEST['user'] == $usuario) {
                    ?>
                                        <tr class="fw-lighter align-middle">
                                            <td class="text-center"><?= $dataPendencia ?? ''; ?></td>
                                            <td class="text-center"><?= $firstName ?? ''; ?></td>
                                            <td class=""><?= $instituicao ?? ''; ?></td>
                                            <td class="text-center"><?= $itemDPend ?? ''; ?></td>
                                            <td class=""><?= $fornecedor ?? ''; ?></td>
                                            <td class=""><?= $docPend ?? ''; ?></td>
                                            <td class=""><?= $pendencia ?? ''; ?></td>
                                            <td class=""><?= $providencias ?? ''; ?></td>
                                            <td class="text-center"><?= ($resolvido == 1) ? 'SIM' : 'NÃO' ?></td>
                                            <td class="text-center"><?= $dataResolvido ?? ''; ?></td>
                                        </tr>
                                    <?php
                                        $nItem = $nItem + 1;
                                    } elseif ($_REQUEST['Reg'] == 2 && $_REQUEST['user'] == $usuario) {
                                    ?>
                                        <tr class="fw-lighter align-middle">
                                            <td class="text-center"><?= $dataPendencia ?? ''; ?></td>
                                            <td class="text-center"><?= $firstName ?? ''; ?></td>
                                            <td class=""><?= $instituicao ?? ''; ?></td>
                                            <td class="text-center"><?= $itemDPend ?? ''; ?></td>
                                            <td class=""><?= $fornecedor ?? ''; ?></td>
                                            <td class=""><?= $docPend ?? ''; ?></td>
                                            <td class=""><?= $pendencia ?? ''; ?></td>
                                            <td class=""><?= $providencias ?? ''; ?></td>
                                            <td class="text-center"><?= ($resolvido == 1) ? 'SIM' : 'NÃO' ?></td>
                                            <td class="text-center"><?= $dataResolvido ?? ''; ?></td>
                                        </tr>
                                    <?php
                                        $nItem = $nItem + 1;
                                    } elseif ($_REQUEST['Reg'] == $resolvido && $_REQUEST['user'] == 99) {
                                    ?>
                                        <tr class="fw-lighter align-middle">
                                            <td class="text-center"><?= $dataPendencia ?? ''; ?></td>
                                            <td class="text-center"><?= $firstName ?? ''; ?></td>
                                            <td class=""><?= $instituicao ?? ''; ?></td>
                                            <td class="text-center"><?= $itemDPend ?? ''; ?></td>
                                            <td class=""><?= $fornecedor ?? ''; ?></td>
                                            <td class=""><?= $docPend ?? ''; ?></td>
                                            <td class=""><?= $pendencia ?? ''; ?></td>
                                            <td class=""><?= $providencias ?? ''; ?></td>
                                            <td class="text-center"><?= ($resolvido == 1) ? 'SIM' : 'NÃO' ?></td>
                                            <td class="text-center"><?= $dataResolvido ?? ''; ?></td>
                                        </tr>
                                    <?php
                                        $nItem = $nItem + 1;
                                    } elseif ($_REQUEST['Reg'] == 2 && $_REQUEST['user'] == 99) {
                                    ?>
                                        <tr class="fw-lighter align-middle">
                                            <td class="text-center"><?= $dataPendencia ?? ''; ?></td>
                                            <td class="text-center"><?= $firstName ?? ''; ?></td>
                                            <td class=""><?= $instituicao ?? ''; ?></td>
                                            <td class="text-center"><?= $itemDPend ?? ''; ?></td>
                                            <td class=""><?= $fornecedor ?? ''; ?></td>
                                            <td class=""><?= $docPend ?? ''; ?></td>
                                            <td class=""><?= $pendencia ?? ''; ?></td>
                                            <td class=""><?= $providencias ?? ''; ?></td>
                                            <td class="text-center"><?= ($resolvido == 1) ? 'SIM' : 'NÃO' ?></td>
                                            <td class="text-center"><?= $dataResolvido ?? ''; ?></td>
                                        </tr>
                        <?php
                                            $nItem = $nItem + 1;
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
        <font color="#575756"><small>© Copyright - Secretaria de Educação - São Bernardo do Campo | 2025. Todos os Direitos Reservados.</small></font>
    </footer>

    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
<?php
ob_flush();
?>