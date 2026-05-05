<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

// Trava de segurança: Se não houver cota na sessão, mata o processo e fecha a aba
if (empty($_SESSION['cota']) || !isset($_SESSION['cota'][0])) {
    echo "<script>
            alert('Atenção: Nenhuma cota foi gerada pelo sistema. Redirecionando...');
            window.close(); // Tenta fechar a aba
            window.location.href = 'gerarcota.php'; // Se o navegador bloquear o fechamento, manda de volta
          </script>";
    exit();
}

use Source\Database\Connect;
use Source\Models\Instituicao;
use Source\Models\ItensCota;

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
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
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
                $programa = "PDDE Equidade";
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
        <p style="text-indent: 3em;">Juntamos os seguintes documentos relativos à prestação de contas do Exercício de 2025:</p>
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

            $mesesNome = [
                '01' => 'janeiro', '02' => 'fevereiro', '03' => 'março', '04' => 'abril',
                '05' => 'maio', '06' => 'junho', '07' => 'julho', '08' => 'agosto',
                '09' => 'setembro', '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'
            ];
            $mes = $mesesNome[$mes];
        
            ?>
        </ul>
        <br>
        <p><b>
            À<br />
            SE-331.2<br />
            Srª. Encarregada,        
        </b>        
        </p>

        <p style="text-indent: 3em;">Procedemos à análise da Prestação de Contas da <b><?= $instituicao ?></b>, dos recursos relativos ao <b><u><?= $programa ?></u></b> do exercício de 2025.</p>
        <p style="text-indent: 3em;">Encaminhamos o presente para que se proceda à Análise Financeira.</p>

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
    
    <button onclick="window.close()" class="btn btn-danger rounded-circle shadow no-print" style="position: fixed; bottom: 100px; right: 30px; width: 60px; height: 60px;" title="Fechar aba">
        <i class="lni lni-close" style="font-size: 1.5rem;"></i>
    </button>
    <button onclick="window.print()" class="btn btn-primary rounded-circle shadow no-print" style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px;">
        <i class="lni lni-printer" style="font-size: 1.5rem;"></i>
    </button>

    <style>
    /* Tudo que tiver a classe .no-print VAI SUMIR na hora que o PDF for gerado */
    @media print {
        .no-print {
            display: none !important;
        }
        
        /* Remove cabeçalhos/rodapés com URL/Data do navegador e força o formato A4 */
        @page {
            size: A4;
            margin: 1.5cm; /* Define uma margem limpa e uniforme */
        }
        
        /* Garante que o corpo do texto fique preto para impressoras P&B economizarem tinta colorida (se houver tons de cinza) */
        body {
            color: #000 !important;
        }
    }
    </style>

    <script>
    // Abre a tela de impressão automaticamente assim que a página carrega
    window.onload = function() {
        window.print();
    };
    </script>   

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
</body>
</html>
<?php
ob_flush();
?>