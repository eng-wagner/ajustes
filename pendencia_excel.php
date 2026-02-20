<?php
require __DIR__ . "/source/autoload.php";

use Source\Database\Connect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$timezone = new DateTimeZone("America/Sao_Paulo");

$agora = new DateTime('now', $timezone);
$agora = $agora->format('d_m_Y');

// Limpa buffer de saída
if (ob_get_contents()) ob_end_clean();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Pendências_PDDE');

// ==========================================
// Cabeçalhos
// ==========================================
$headers = [
    'A' => 'Data',
    'B' => 'Responsável',
    'C' => 'Instituição',
    'D' => 'Programa',
    'E' => 'Item do DRD',
    'F' => 'Favorecido',
    'G' => 'Documento',
    'H' => 'Pendência',
    'I' => 'Providências',
    'J' => 'Regularizado',
    'K' => 'Data Regularização'
];

foreach ($headers as $col => $title) {
    $sheet->setCellValue($col . '1', $title);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// ==========================================
// Preenchendo as Linhas
// ==========================================
$row = 2;


$stmt = "SELECT s.id, u.id AS iduser, u.nome, s.dataPend, s.itemDRD, t.documento, s.favorecido, d.pendencia, s.providencias, s.resolvido, s.dataResolvido, p.tipo, i.instituicao FROM pendencias_25 s JOIN processos p ON s.proc_id = p.id JOIN instituicoes i ON p.instituicao_id = i.id JOIN usuarios u ON s.usuario_id = u.id JOIN tipo_documento t ON s.docPend_id = t.id JOIN tipo_pendencia d ON s.pend_id = d.id WHERE s.ativado = 1";
$sql = Connect::getInstance()->prepare($stmt);
if ($sql->execute()) {
    while ($pend = $sql->fetch()) {
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

        $sheet->setCellValue('A' . $row, $dataPendencia ?? '');
        $sheet->setCellValue('B' . $row, $firstName ?? '');
        $sheet->setCellValue('C' . $row, $instituicao ?? '');
        $sheet->setCellValue('D' . $row, $tpProg ?? '');
        $sheet->setCellValue('E' . $row, $itemDPend ?? '');
        $sheet->setCellValue('F' . $row, $favorecido ?? '');
        $sheet->setCellValue('G' . $row, $docPend ?? '');
        $sheet->setCellValue('H' . $row, $pendencia ?? '');
        $sheet->setCellValue('I' . $row, $providencias ?? '');
        $sheet->setCellValue('J' . $row, ($resolvido == 1) ? 'SIM' : 'NÃO');
        $sheet->setCellValue('K' . $row, $dataResolvido);

        $sheet->getStyle("J$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
    }
} 

// Ajuste das colunas

$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setWidth(40);
$sheet->getColumnDimension('D')->setAutoSize(true);
$sheet->getColumnDimension('E')->setWidth(12);
$sheet->getColumnDimension('F')->setWidth(50);
$sheet->getColumnDimension('G')->setAutoSize(true);
$sheet->getColumnDimension('H')->setWidth(40);
$sheet->getColumnDimension('I')->setWidth(60);
$sheet->getColumnDimension('J')->setWidth(15);
$sheet->getColumnDimension('K')->setWidth(20);


$fileName = "Pendências_PDDE_" . $agora . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;