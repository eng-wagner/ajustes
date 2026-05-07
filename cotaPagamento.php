<?php
ob_start();
session_start();

require_once __DIR__ . "/source/autoload.php";

use Source\Database\Connect;
use Source\Models\Processo;


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['pagamentos_selecionados'])) {
    echo "<script>alert('Acesso inválido.'); window.close();</script>";
    exit();
}

$pdo = Connect::getInstance();
$processoModel = new Processo();

// Recebe os dados do formulário anterior
$pagamentosIds = $_POST['pagamentos_selecionados'];
$ajusteId      = $_POST['ajuste_id'];
$processoId    = $_POST['processo_id'];
$documentos = $_POST['documentos'] ?? [];
$textoTermo = $_POST['texto_termo'] ?? '';

// Busca os dados da Instituição
$stmtAjuste = $pdo->prepare("
    SELECT a.*, i.instituicao, c.banco, c.agencia, c.conta_corrente, c.fonte_recursos
    FROM ajustes a 
    JOIN instituicoes i ON a.instituicao_id = i.id 
    JOIN ajustes_contas c ON a.id = c.ajuste_id     
    WHERE a.id = ?
");
$stmtAjuste->execute([$ajusteId]);
$dadosAjuste = $stmtAjuste->fetch(PDO::FETCH_ASSOC);
$processoSb = $processoModel->formatarProcesso($processoModel->findById($processoId));

// Busca os Pagamentos
$placeholders = str_repeat('?,', count($pagamentosIds) - 1) . '?';
$stmtPag = $pdo->prepare("SELECT e.numero_empenho, p.data_pagamento, p.valor, p.descricao FROM ajustes_pagamentos p JOIN ajustes_empenhos e ON p.empenho_id = e.id WHERE p.id IN ($placeholders)");
$stmtPag->execute($pagamentosIds);
$listaPagamentos = $stmtPag->fetchAll(PDO::FETCH_ASSOC);

$valorTotal = 0;
foreach ($listaPagamentos as $pag) {
    $valorTotal += $pag['valor'];
}

// Busca os dados do usuário logado para a assinatura
$uFuncao = "";
$uSigla = "SE-331"; // Padrão
$sqlUser = $pdo->prepare("SELECT u.funcao, l.sigla FROM usuarios u JOIN localexercicio l ON u.id_local = l.id WHERE u.id = :userId");
$sqlUser->bindParam("userId", $_SESSION['user_id']);
$sqlUser->execute();
if ($usuario = $sqlUser->fetch(PDO::FETCH_OBJ)) {
    $uFuncao = ucwords(mb_strtolower($usuario->funcao, "utf-8"));
    $uSigla = $usuario->sigla;
}

// Datas para a assinatura
$mesesNome = ['01'=>'janeiro', '02'=>'fevereiro', '03'=>'março', '04'=>'abril', '05'=>'maio', '06'=>'junho', '07'=>'julho', '08'=>'agosto', '09'=>'setembro', '10'=>'outubro', '11'=>'novembro', '12'=>'dezembro'];
$dia = date('d');
$mes = $mesesNome[date('m')];
$ano = date('Y');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressão da Cota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">        
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />    

    <title><?= "Cota " . $programa . " - " . $instituicao ?></title>
    
    <style>
        .text-indent-3 { text-indent: 3em; }
        .text-justify { text-align: justify; }
        
        @media print {
            .no-print { display: none !important; }
            @page { size: A4; margin: 2cm; }
            body { color: #000 !important; background: #fff !important; }
            table { border-collapse: collapse !important; width: 100% !important; }
            table th, table td { border: 1px solid #000 !important; padding: 8px !important; }
            .table-light { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>

</head>
<body style="font-family: Arial, sans-serif; font-size: 15px; line-height: 1.6; background: #f8f9fa;">
    <div class="container bg-white shadow-sm my-4 p-5" style="max-width: 21cm; min-height: 29.7cm;">
        <div class="text-center mb-5">
            <img src="img/folhadeinformacao.png" width="100%" alt="folha de informação" />
            <p class="mb-0">(Anexo ao <?= htmlspecialchars($processoSb) ?>)</p>
        </div>
        
        <p><b>À<br>SE-341<br>Senhor Diretor,</b></p>

        <p>Procedemos à juntada dos documentos abaixo relacionados:</p>
        <ul style="list-style-type: disc; margin-left: 40px;">
            <?php 
            // Lista todos os documentos que foram "ticados" no formulário
            if (!empty($documentos)) {
                foreach ($documentos as $doc) {
                    echo "<li>" . htmlspecialchars($doc) . ";</li>";
                }
            }
            ?>
            <li><?= htmlspecialchars($textoTermo) ?>.</li>
        </ul>

        <p class="text-justify text-indent-3 mt-4">
            Considerando a formalização do <?= htmlspecialchars($textoTermo) ?>, celebrado entre o Município de São Bernardo do Campo e a <b>"<?= htmlspecialchars($dadosAjuste['instituicao']) ?>"</b>;
        </p>

        <p class="text-justify text-indent-3">
            Encaminhamos para providências quanto à autorização de pagamento do montante de <b>R$ <?= number_format($valorTotal, 2, ',', '.') ?></b>, a favor da <?= htmlspecialchars($dadosAjuste['instituicao']) ?>, relativo ao <?= htmlspecialchars($textoTermo) ?>, conforme segue:
        </p>

        <table class="table text-center align-middle mt-4 mb-4" style="border-color: #FFF;">
            <thead class="table-light text-dark fw-bold" style="background-color: #FFF !important; -webkit-print-color-adjust: exact;">
                <tr>
                    <th>Valor</th>
                    <th>NE</th>
                    <th>Vencimento</th>
                    <th>Referente</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listaPagamentos as $pag): ?>
                <tr>
                    <td>R$ <?= number_format($pag['valor'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($pag['numero_empenho']) ?></td>
                    <td><?= date('d/m/Y', strtotime($pag['data_pagamento'])) ?></td>
                    <td><?= htmlspecialchars($pag['descricao']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="text-justify text-indent-3">Informo que o pagamento deverá ser efetuado mediante depósito no (a) <b><?= htmlspecialchars($dadosAjuste['banco'] ?? '_____') ?> - Agência: <?= htmlspecialchars($dadosAjuste['agencia'] ?? '_____') ?> - Conta Corrente: <?= htmlspecialchars($dadosAjuste['conta_corrente'] ?? '_____') ?></b>.</p>
        
        <p class="text-justify text-indent-3">Terminados os procedimentos, obséquio remeter ao SE-331.1 para demais providências pertinentes.</p>

        <br><br>
        <div class="text-center mt-5">
            <p><?= $uSigla ?>, <?= $dia ?> de <?= $mes ?> de <?= $ano ?>.</p>
            <br><br>
            <p class="mb-0"><b><?= htmlspecialchars($_SESSION['nome'] ?? '') ?></b></p>
            <p class="mb-0"><?= htmlspecialchars($uFuncao) ?></p>
            <p>Mat <?= htmlspecialchars($_SESSION['matricula'] ?? '') ?></p>
        </div>        
    </div>        
    
    <button onclick="history.back()" class="btn btn-danger rounded-circle shadow no-print d-flex align-items-center justify-content-center" style="position: fixed; bottom: 100px; right: 30px; width: 60px; height: 60px;" title="Voltar">
        <i class="lni lni-arrow-left" style="font-size: 1.5rem;"></i>
    </button>
    <button onclick="window.print()" class="btn btn-primary rounded-circle shadow no-print" style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px;">
        <i class="lni lni-printer" style="font-size: 1.5rem;"></i>
    </button>   

    <script>
        // Abre a tela de impressão automaticamente assim que a página carrega
        window.onload = function() {
            window.print();
        };
    </script>       
</body>
</html>
<?php
ob_flush();
?>