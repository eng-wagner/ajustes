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
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    <?php 
        $arrayCota = array($_SESSION['cota'][0]);

        $stmt = Connect::getInstance()->prepare("SELECT instituicao FROM instituicoes WHERE id = :idInst");
        $stmt->bindParam('idInst', $arrayCota[0]['instituicao']);
        if($stmt->execute())
        {
            if($inst = $stmt->fetch()){
                $instituicao = $inst->instituicao;
            }
        }

        switch($arrayCota[0]['programa'])
        {
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
  
  $sql = Connect::getInstance()->prepare("SELECT numero, ano, digito FROM processos WHERE instituicao_id = :idInst AND tipo = :programa");
        $sql->bindParam('idInst', $arrayCota[0]['instituicao']);
        $sql->bindParam('programa', $programa);
        if($sql->execute())
        {
            if($processo = $sql->fetch()){
                $numProc = $processo->numero;
                $anoProc = $processo->ano;
                $digProc = $processo->digito;
            }
        }
    ?>

    <title><?= "Cota de Juntada Análise Financeira - " . $instituicao . " - " . $programa ?></title>
    
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>
</head>
<body>
    <div class="container-lg">    
        <img src="img/folhadeinformacao.png" width="100%" alt="folha de informação" />
        <br><br>
        <p class="text-center">(Anexo ao SB.<?= $numProc . "/" . $anoProc . "-" . $digProc ?>)</p>
        <p><b>Serviço:</b></p>
        <p style="text-indent: 3em;">Juntamos os seguintes documentos relativos à prestação de contas do Exercício de 2025:</p>
        <ul style="text-indent: 1em; list-style-position: inside;">
            <li>Análise Financeira da Prestação de Contas 2024 - <?= $programa ?> - <?= $instituicao ?>;</li>
            <li>Demonstrativo Consolidado da Execução Físico-Financeira referente ao <?= $programa ?> – 2025;
            <li>Consulta à situação da Prestação de Contas da UEx.</li>

        <?php 
                       
        $hoje = new DateTime();
        $hoje->setTimezone($timezone);
        
        $dia = $hoje->format('d'); 
        $mes = $hoje->format('m');
        $ano = $hoje->format('Y');

        switch($mes)
        {
            case 1:
                $mes = "janeiro";
                break;
            case 2:
                $mes = "fevereiro";
                break;
            case 3:
                $mes = "março";
                break;
            case 4:
                $mes = "abril";
                break;
            case 5:
                $mes = "maio";
                break;
            case 6:
                $mes = "junho";
                break;
            case 7:
                $mes = "julho";
                break;
            case 8:
                $mes = "agosto";
                break;
            case 9:
                $mes = "setembro";
                break;
            case 10:
                $mes = "outubro";
                break;
            case 11:
                $mes = "novembro";
                break;
            case 12:
                $mes = "dezembro";
                break;
        }
        
            ?>
        </ul>
        <br>

        <?php
        $sql = Connect::getInstance()->prepare("SELECT u.funcao, l.sigla FROM usuarios u JOIN localexercicio l ON u.id_local = l.id WHERE u.id = :userId");
        $sql->bindParam("userId", $_SESSION['user_id']);
        $sql->execute();
        if($usuario = $sql->fetch()){
            $uFuncao = $usuario->funcao;
            $uSigla = $usuario->sigla;
        }
        $uFuncao = mb_strtolower($uFuncao,"utf-8");
        $uFuncao = ucwords($uFuncao);
        
        ?>
        <p class="text-center"><?= $uSigla .", " . $dia . ' de ' . $mes . ' de ' . $ano ?>.</p>
        <br><br>
        
        <p class="text-center"><?= $_SESSION['nome'] ?><br>
        <?= $uFuncao ?><br>
        Mat <?= $_SESSION['matricula'] ?></p>

    </div>    

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
</body>
</html>
<?php
ob_flush();
?>