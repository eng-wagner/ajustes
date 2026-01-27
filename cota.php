<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Instituicao;
use Source\ItensCota;

$instituicaoModel = new Instituicao();
$itensModel = new ItensCota();

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
        $idInst = $arrayCota[0]['instituicao'];
        $inst = $instituicaoModel->findById($idInst);        

        if($inst){
            $instituicao = $inst->instituicao;
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
                $programa = "PDDE Estrutura";
                break;
            case 4:
                $programa = "PDDE Educação Integral";
                break;
            case 5:
                $programa = "PDDE PDE Escola";
                break;
        }
    ?>

    <title><?= "Cota " . $programa . " - " . $instituicao ?></title>
    
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
        <br>
        <p><b>Serviço:</b></p>
        <p style="text-indent: 3em;">Juntamos os seguintes documentos relativos à prestação de contas do Exercício de 2024:</p>
        <ul style="text-indent: 1em; list-style-position: inside;">
        <?php 
                
        foreach($arrayCota[0] as $item => $valor)
        {             
            $doc = $itensModel->findByCh($item);
            if($doc)
            {
                echo "<li>" . $doc->documentos . ";</li>";
            }            
        }      
                
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
        <p><b>
            À<br />
            SE-331.2<br />
            Srª. Encarregada,        
        </b>        
        </p>

        <p style="text-indent: 3em;">Procedemos à análise da Prestação de Contas da <b><?= $instituicao ?></b>, dos recursos relativos ao <b><u><?= $programa ?></u></b> do exercício de 2024.</p>
        <p style="text-indent: 3em;">Encaminhamos o presente para que se proceda à Análise Financeira.</p>

        <p class="text-center">SE-331.2, <?= $dia . ' de ' . $mes . ' de ' . $ano ?>.</p>
        <br><br>
        <p class="text-center"><?= $_SESSION['nome'] ?><br>
        Mat <?= $_SESSION['matricula'] ?></p>

    </div>    

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
</body>
</html>
<?php
ob_flush();
?>