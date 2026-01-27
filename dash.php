<?php
    use Source\Database\Connect;
    $pdo = Connect::getInstance();

    $sql = $pdo->prepare("SELECT count(numero) AS processos FROM processos WHERE tipo LIKE 'PDDE%'");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $total = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_24 WHERE status_id = 2");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $entregue = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_24 WHERE status_id = 3");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $aEx = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_24 WHERE status_id = 4");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $pend = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_24 WHERE status_id = 5");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $aFin = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_24 WHERE status_id = 6");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $aFinConc = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_24 WHERE status_id = 7");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $conclude = $processos->processos;
    }

    $aguardando = $total - $aEx - $pend - $aFin - $aFinConc - $conclude;

    $sql = $pdo->prepare("SELECT count(status_id) AS processos FROM analise_pdde_24 WHERE status_id = 7");
    $sql->execute();
    if($processos = $sql->fetch())
    {
        $conclude = $processos->processos;
    }

    $sql = $pdo->prepare("SELECT sum(saldo22) as s22, sum(rp24) as rp, sum(rent24) as rent, sum(devl24) as dev, sum(saldo24) as s24 FROM saldo_pdde;");
    $sql->execute();
    if($saldos = $sql->fetch())
    {
        $inicial = $saldos->s22;
        $rProprios = $saldos->rp;
        $rentabilide = $saldos->rent;
        $devolucoes = $saldos->dev;
        $final = $saldos->s24;
    }

    $sql = $pdo->prepare("SELECT sum(custeio) as custeio, sum(capital) as capital FROM repasse_24");
    $sql->execute();
    if($repasses = $sql->fetch())
    {
        $custeio = $repasses->custeio;
        $capital = $repasses->capital;
    }
    $repTotal = $custeio + $capital;

    $sql = $pdo->prepare("SELECT sum(valor) as despesas FROM pdde_despesas_24");
    $sql->execute();
    if($valor = $sql->fetch())
    {
        $despesas = $valor->despesas;
    }

?>    
<script>
    //Gráfico Pizza
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {

        // Set Data
        const data = google.visualization.arrayToDataTable([
        ['Status', 'Quantidade'],
        ['Aguardando Entrega',<?= $aguardando ?>],
        ['Entregue',<?= $entregue ?>],
        ['Análise da Execução',<?= $aEx ?>],
        ['Análise Financeira',<?= $aFin ?>],
        ['Pendências na Análise', <?= $pend ?>],
        ['Análise Financeira Concluída',<?= $aFinConc ?>],
        ['Concluído',<?= $conclude ?>],
        ]);

        // Set Options
        const options = {
        title:'Status Análise da Prestação de Contas',
        backgroundColor: '#fafbfe',
        is3D: 'true'
        };

        // Draw
        const chart = new google.visualization.PieChart(document.getElementById('statusPrestacao'));
        
        chart.draw(data, options);
    }
    
    google.charts.setOnLoadCallback(drawChart1);
    //Gráfico Coluna
    function drawChart1() {
        var data = google.visualization.arrayToDataTable([
            ["Saldo", "Valor"],
            ["Saldo em 01/01", <?= $inicial ?>],
            ["Repasse", <?= $repTotal ?>],
            ["Recursos Próprios", <?= $rProprios ?>],
            ["Rentabilidade", <?= $rentabilide ?>],
            ["Devoluções", <?= $devolucoes ?>],
            ["Despesas", <?= $despesas ?>],
            ["Saldo em 31/12", <?= $final ?>]
        ]);

        var view = new google.visualization.DataView(data);
        view.setColumns([0, 1,
                        { calc: "stringify",
                            sourceColumn: 1,
                            type: "string",
                            role: "annotation" }]);

        var options = {
            title: "Saldo PDDE",
            width: 900,
            height: 400,
            backgroundColor: '#fafbfe',
            bar: {groupWidth: "95%"},
            legend: { position: "none" },
        };
        var chart = new google.visualization.ColumnChart(document.getElementById("columnchart_values"));
        chart.draw(view, options);
    }
</script>