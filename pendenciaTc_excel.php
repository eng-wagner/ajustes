<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";

use Source\Database\Connect;

$timezone = new DateTimeZone("America/Sao_Paulo");

$agora = new DateTime('now', $timezone);
$agora = $agora->format('d_m_Y');

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Pendências_TC_" . $agora . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";
 
?>
<table border="1">
    <tr>
        <th>Data</th>
        <th>Responsável</th>
        <th>Instituição</th>
        <th>Item do DRD</th>
        <th>Fornecedor</th>
        <th>Documento</th>
        <th>Pendência</th>
        <th>Providências</th>
        <th>Regularizado</th>
        <th>Data Regularização</th>
    </tr>
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
            ?>
            <tr>
                <td><?= $dataPendencia ?? ''; ?></td>
                <td><?= $firstName ?? ''; ?></td>
                <td><?= $instituicao ?? ''; ?></td>
                <td><?= $itemDPend ?? ''; ?></td>
                <td><?= $fornecedor ?? ''; ?></td>
                <td><?= $docPend ?? ''; ?></td>
                <td><?= $pendencia ?? ''; ?></td>
                <td><?= $providencias ?? ''; ?></td>
                <td><?= ($resolvido == 1) ? 'SIM' : 'NÃO' ?></td>
                <td><?= $dataResolvido ?? ''; ?></td>
            </tr>
            <?php
                $nItem = $nItem + 1;
            
        }
    }
    ?>    
</table>
<?php
ob_flush();
?>