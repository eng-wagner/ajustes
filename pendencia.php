<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Pendencia;
use Source\User;


$userModel = new User();
$pendenciaModel = new Pendencia();

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

$currentUser = $_SESSION['user_id'];

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

    if (isset($_REQUEST["logoff"]) && $_REQUEST["logoff"] == true) {
        $_SESSION['flag'] = false;
        session_unset();
        header("Location:index.php?status=logoff");
    }
    $firstName = substr($userName,0,strpos($userName," "));

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
                    Consulta de Pendências
                </h1>
            </div>
            <!-- Início do Conteúdo -->

            <div class="container-fluid">
                <div class="row p-0">
                    <h6 class="text-center col-11">Filtros</h6>
                </div>
                <div class="row mb-1">
                    <div class="col-1">
                        <h6>Programas</h6>
                    </div>
                    <div class="col-5">
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
                        if (isset($_REQUEST['Reg'])) {
                            $btnRegChk = ['', '', ''];
                            $btnRegChk[$_REQUEST['Reg']] = 'checked';
                        }

                        ?>
                        <input type="radio" class="btn-check" name="btnProg" id="btnTodos" autocomplete="off" onclick="location.href='?Prg=0&Reg=<?= $_REQUEST['Reg'] ?>&user=<?= $_REQUEST['user'] ?>'" <?= $btnProgChk[0] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-secondary" for="btnTodos">Todos</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnBasico" autocomplete="off" onclick="location.href='?Prg=1&Reg=<?= $_REQUEST['Reg'] ?>&user=<?= $_REQUEST['user'] ?>'" <?= $btnProgChk[1] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-primary" for="btnBasico">PDDE Básico</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnQual" autocomplete="off" onclick="location.href='?Prg=2&Reg=<?= $_REQUEST['Reg'] ?>&user=<?= $_REQUEST['user'] ?>'" <?= $btnProgChk[2] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-info" for="btnQual">PDDE Qualidade</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnEstr" autocomplete="off" onclick="location.href='?Prg=3&Reg=<?= $_REQUEST['Reg'] ?>&user=<?= $_REQUEST['user'] ?>'" <?= $btnProgChk[3] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-success" for="btnEstr">PDDE Equidade</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnEdInt" autocomplete="off" onclick="location.href='?Prg=4&Reg=<?= $_REQUEST['Reg'] ?>&user=<?= $_REQUEST['user'] ?>'" <?= $btnProgChk[4] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-warning" for="btnEdInt">PDDE Ed. Integral</label>

                        <input type="radio" class="btn-check" name="btnProg" id="btnPDE" autocomplete="off" onclick="location.href='?Prg=5&Reg=<?= $_REQUEST['Reg'] ?>&user=<?= $_REQUEST['user'] ?>'" <?= $btnProgChk[5] ?? '' ?>>
                        <label class="btn btn-sm btn-outline-dark" for="btnPDE">PDDE PDE-Escola</label>
                    </div>
                    <div class="col-3">
                        <div class="input-group input-group-sm">
                            <label class="input-group-text col-3" for="inputGroup-user"><b>Usuário</b></label>
                            <form method="get" action=''>
                                <input type="hidden" name="Prg" value="<?= $_REQUEST['Prg'] ?>" />
                                <input type="hidden" name="Reg" value="<?= $_REQUEST['Reg'] ?>">
                                <select name="user" class="form-select col-7" id="inputGroup-user" onchange='this.form.submit()'>
                                    <option value="99" selected>Todos</option>
                                    <?php
                                    $users = $userModel->usuariosComPendencias();
                                    if ($users) {
                                        foreach($users as $user):
                                            $idUsuario = $user->usuario_id;
                                            $nomeUsuario = $user->nome;
                                            $primeiroNome = substr($nomeUsuario, 0, strpos($nomeUsuario, " "));
                                            if (isset($_REQUEST['user']) && $_REQUEST['user'] == $idUsuario) {
                                                echo '<option value="' . $idUsuario . '" selected>' . $primeiroNome . '</option>';
                                            } else {
                                                echo '<option value="' . $idUsuario . '">' . $primeiroNome . '</option>';
                                            }
                                        endforeach;
                                    }
                                    ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-1">
                        <h6>Regularizado</h6>
                    </div>
                    <div class="col-3">
                        <input type="radio" class="btn-check" name="btnStatus" id="btnAll" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&Reg=2&user=<?= $_REQUEST['user'] ?>'" <?= $btnRegChk[2] ?>>
                        <label class="btn btn-sm btn-outline-secondary" for="btnAll">Todos</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS1" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&Reg=1&user=<?= $_REQUEST['user'] ?>'" <?= $btnRegChk[1] ?>>
                        <label class="btn btn-sm btn-outline-success" for="btnS1">SIM</label>

                        <input type="radio" class="btn-check" name="btnStatus" id="btnS2" autocomplete="off" onclick="location.href='?Prg=<?= $_REQUEST['Prg'] ?>&Reg=0&user=<?= $_REQUEST['user'] ?>'" <?= $btnRegChk[0] ?>>
                        <label class="btn btn-sm btn-outline-danger" for="btnS2">NÃO</label>
                    </div>
                    <div class="col-8">
                        <button type="button" class="btn btn-success" onclick="location.href='pendencia_excel.php'">Exportar</button>
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
                            <th class="col w-auto fw-semibold">Programa</th>
                            <th class="col w-auto fw-semibold">Item do DRD</th>
                            <th class="col w-auto fw-semibold">Favorecido</th>
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

                        //$total = 0;                                            

                        if (isset($_REQUEST['Prg']) && $_REQUEST['Prg'] != null) {
                            if ($_REQUEST['Prg'] == 0) {
                                $pendencias = $pendenciaModel->findPendAtivas();
                            } else {
                                $pendencias = $pendenciaModel->findPendAtivasByProg($programa);                                
                            }
                            if ($pendencias) {
                                foreach ($pendencias as $pend) {
                                    $idPend = $pend->id;
                                    $usuario = $pend->iduser;
                                    $responsavel = $pend->nome;
                                    $dataPend = $pend->dataPend;
                                    $itemDPend = $pend->itemDRD;
                                    $docPend = $pend->documento;
                                    $favorecido = $pend->favorecido;
                                    $pendencia = $pend->pendencia;
                                    $providencias = $pend->providencias;
                                    $resolvido = $pend->resolvido;
                                    $dResolvido = $pend->dataResolvido;
                                    $tpProg = $pend->tipo;
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
                                                <?php
                                                switch ($tpProg) {
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
                                                <td scope="row" class="<?= $bgColor ?? ''; ?>"><?= $tpProg ?? ''; ?></td>

                                                <td class="text-center"><?= $itemDPend ?? ''; ?></td>
                                                <td class=""><?= $favorecido ?? ''; ?></td>
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
                                                <?php
                                                switch ($tpProg) {
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
                                                <td scope="row" class="<?= $bgColor ?? ''; ?>"><?= $tpProg ?? ''; ?></td>

                                                <td class="text-center"><?= $itemDPend ?? ''; ?></td>
                                                <td class=""><?= $favorecido ?? ''; ?></td>
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
                                                <?php
                                                switch ($tpProg) {
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
                                                <td scope="row" class="<?= $bgColor ?? ''; ?>"><?= $tpProg ?? ''; ?></td>

                                                <td class="text-center"><?= $itemDPend ?? ''; ?></td>
                                                <td class=""><?= $favorecido ?? ''; ?></td>
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
                                                <?php
                                                switch ($tpProg) {
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
                                                <td scope="row" class="<?= $bgColor ?? ''; ?>"><?= $tpProg ?? ''; ?></td>

                                                <td class="text-center"><?= $itemDPend ?? ''; ?></td>
                                                <td class=""><?= $favorecido ?? ''; ?></td>
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
                        }
                        ?>
                        <span class="border m-2 p-1 border-dark">Registros: <?= $nItem ?></span>
                        
                    </tbody>
                </table>
            </div>

            <!-- Fim do Conteúdo -->
        </div>
    </div>

    <?php include 'modalSair.php'; ?>
    <?php include 'toasts.php'; ?>
    <?php include 'footer.php'; ?>

    <script src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
<?php
ob_flush();
?>