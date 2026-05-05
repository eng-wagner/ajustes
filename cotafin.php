<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";

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
            <li>Análise Financeira da Prestação de Contas 2025 - <?= $programa ?> - <?= $instituicao ?>;</li>
            <li>Demonstrativo Consolidado da Execução Físico-Financeira referente ao <?= $programa ?> – 2025;
            <li>Consulta à situação da Prestação de Contas da UEx.</li>

        <?php 
                       
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