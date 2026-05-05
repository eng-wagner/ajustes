<?php
    use Source\Database\Connect;
    $pdo = Connect::getInstance();

    // 1. Inicializa variáveis para evitar erros no JS caso o banco volte vazio
    $total = $entregue = $aEx = $pend = $aFin = $aFinConc = $conclude = $aguardando = 0;
    $inicial = $rProprios = $rentabilide = $devolucoes = $final = $custeio = $capital = $repTotal = $despesas = 0;

    $sql = $pdo->prepare("SELECT count(numero) AS processos FROM processos WHERE tipo LIKE 'PDDE%'");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $total = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_25 WHERE status_id = 2");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $entregue = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_25 WHERE status_id = 3");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $aEx = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_25 WHERE status_id = 4");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $pend = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_25 WHERE status_id = 5");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $aFin = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_25 WHERE status_id = 6");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $aFinConc = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_25 WHERE status_id = 7");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $conclude = $processos->processos;
    }

    $aguardando = $total - $aEx - $pend - $aFin - $aFinConc - $conclude;

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_25 WHERE status_id = 7");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $conclude = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT sum(saldo24) as s24, sum(rp25) as rp, sum(rent25) as rent, sum(devl25) as dev, sum(saldo25) as s25 FROM saldo_pdde;");
    $sql->execute();
    if($saldos = $sql->fetch())
    {
        $inicial = $saldos->s24;
        $rProprios = $saldos->rp;
        $rentabilide = $saldos->rent;
        $devolucoes = $saldos->dev;
        $final = $saldos->s25;
    }

    $sql = $pdo->prepare("SELECT sum(custeio) as custeio, sum(capital) as capital FROM repasse_25");
    $sql->execute();
    if($repasses = $sql->fetch())
    {
        $custeio = $repasses->custeio;
        $capital = $repasses->capital;
    }
    $repTotal = $custeio + $capital;

    $sql = $pdo->prepare("SELECT sum(valor) as despesas FROM pdde_despesas_25");
    $sql->execute();
    if($valor = $sql->fetch())
    {
        $despesas = $valor->despesas;
    }

?>    
<script>
    google.charts.load('current', {'packages':['corechart']});
    
    // Função unificada para desenhar todos os gráficos
    function drawAllCharts() {
        drawChartStatus();
        drawChartFinanceiro();
    }

    google.charts.setOnLoadCallback(function() {
        setTimeout(drawAllCharts, 150); // Espera 150 milissegundos
    });

    // Redesenha os gráficos caso a janela do navegador mude de tamanho (Responsividade Real)
    window.addEventListener('resize', drawAllCharts);

    // ==========================================
    // GRÁFICO 1: PIE (DONUT) - STATUS
    // ==========================================
    function drawChartStatus() {
        const data = google.visualization.arrayToDataTable([
            ['Status', 'Quantidade'],
            ['Aguardando Entrega', <?= $aguardando ?>],
            ['Entregue', <?= $entregue ?>],
            ['Análise da Execução', <?= $aEx ?>],
            ['Análise Financeira', <?= $aFin ?>],
            ['Pendências na Análise', <?= $pend ?>],
            ['Financ. Concluída', <?= $aFinConc ?>],
            ['Concluído', <?= $conclude ?>]
        ]);

        const options = {
            title: 'Análise da Prestação de Contas',
            titleTextStyle: { color: '#495057', fontSize: 16, bold: true },
            backgroundColor: 'transparent', // Fundo transparente para mesclar com o card
            pieHole: 0.45, // Transforma em um Donut moderno
            chartArea: { width: '90%', height: '75%' },
            legend: { position: 'right', alignment: 'center', textStyle: { color: '#6c757d', fontSize: 12 } },
            // Paleta de cores baseada no Bootstrap para manter a harmonia visual
            colors: ['#adb5bd', '#0dcaf0', '#0d6efd', '#6f42c1', '#ffc107', '#20c997', '#198754']
        };

        const chart = new google.visualization.PieChart(document.getElementById('statusPrestacao'));
        chart.draw(data, options);
    }
    
    // ==========================================
    // GRÁFICO 2: COLUNAS - FINANCEIRO
    // ==========================================
    function drawChartFinanceiro() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Categoria');
        data.addColumn('number', 'Valor');
        data.addColumn({type: 'string', role: 'style'}); // Coluna para cor individual
        data.addColumn({type: 'string', role: 'annotation'}); // Anotação em cima da barra

        // Adiciona os dados com cores específicas para cada coluna
        data.addRows([
            ["Saldo Anterior", <?= $inicial ?>, 'color: #6c757d', 'R$ <?= number_format($inicial, 2, ",", ".") ?>'],
            ["Repasse", <?= $repTotal ?>, 'color: #0d6efd', 'R$ <?= number_format($repTotal, 2, ",", ".") ?>'],
            ["Rec. Próprios", <?= $rProprios ?>, 'color: #17a2b8', 'R$ <?= number_format($rProprios, 2, ",", ".") ?>'],
            ["Rentabilidade", <?= $rentabilide ?>, 'color: #20c997', 'R$ <?= number_format($rentabilide, 2, ",", ".") ?>'],
            ["Despesas", <?= $despesas ?>, 'color: #dc3545', 'R$ <?= number_format($despesas, 2, ",", ".") ?>'],
            ["Devoluções", <?= $devolucoes ?>, 'color: #ffc107', 'R$ <?= number_format($devolucoes, 2, ",", ".") ?>'],
            ["Saldo Atual", <?= $final ?>, 'color: #198754', 'R$ <?= number_format($final, 2, ",", ".") ?>']
        ]);

        // Formatador para deixar o eixo Y em formato de moeda (R$)
        var formatter = new google.visualization.NumberFormat({ prefix: 'R$ ', decimalSymbol: ',', groupingSymbol: '.' });
        formatter.format(data, 1);

        var options = {
            title: "Movimentação Financeira (PDDE)",
            titleTextStyle: { color: '#495057', fontSize: 16, bold: true },
            backgroundColor: 'transparent',
            chartArea: { width: '85%', height: '70%' },
            bar: { groupWidth: "75%" },
            legend: { position: "none" },
            animation: { startup: true, duration: 1000, easing: 'out' }, // Animação de subida ao carregar
            vAxis: { 
                format: 'currency', // Força o eixo esquerdo a mostrar moeda
                textStyle: { color: '#6c757d' },
                gridlines: { color: '#e9ecef' }
            },
            hAxis: {
                textStyle: { color: '#495057', fontSize: 11 }
            },
            annotations: {
                alwaysOutside: true,
                textStyle: { fontSize: 10, color: '#495057', auraColor: 'none' }
            }
        };

        var chart = new google.visualization.ColumnChart(document.getElementById("columnchart_values"));
        chart.draw(data, options);
    }
</script>