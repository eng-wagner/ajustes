<?php
// relatorio_despesas_excel.php

require __DIR__ . "/source/autoload.php";
require_once __DIR__ . "/source/Helpers/Helpers.php";

use Source\Models\Despesa;
use Source\Models\Processo;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (ob_get_contents()) ob_end_clean();

$model = new Despesa();
$processoModel = new Processo();

// Captura o filtro da URL
$filtroFornecedor = $_GET['Forn'] ?? null;
$filtroPrograma = $_GET['Prg'] ?? null;
$filtroCategoria = $_GET['Cat'] ?? null;

// Formata os filtros (se for '0', converte para null para o Model trazer tudo)
if ($filtroFornecedor == '0') $filtroFornecedor = null;
if ($filtroPrograma == '0') $filtroPrograma = null;
if ($filtroCategoria == '0') $filtroCategoria = null;

// Busca os dados filtrados
$dados = $model->buscarDespesasRelatorio($filtroFornecedor, $filtroPrograma, $filtroCategoria);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Despesas por Fornecedor');

// Cabeçalhos
$headers = [
    'A' => 'Nº Processo',
    'B' => 'Programa',
    'C' => 'Categoria',
    'D' => 'Fornecedor',
    'E' => 'CNPJ',
    'F' => 'Aquisição',
    'G' => 'Nº Doc',
    'H' => 'Data Documento',
    'I' => 'Valor (R$)'
];

foreach ($headers as $col => $title) {
    $sheet->setCellValue($col . '1', $title);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

$row = 2;
$somaTotal = 0;

if (!empty($dados)) {
    foreach ($dados as $desp) {
        
        $data = $desp->data_desp ? date('d/m/Y', strtotime($desp->data_desp)) : '';
        $valor = $desp->valor ?? 0;
        $somaTotal += $valor;

        // Máscara de CNPJ para ficar bonito no Excel
        $cnpjLimpo = preg_replace("/[^0-9]/", "", $desp->cnpj);
        $cnpj = strlen($cnpjLimpo) == 14 ? substr($cnpjLimpo,0,2).".".substr($cnpjLimpo,2,3).".".substr($cnpjLimpo,5,3)."/".substr($cnpjLimpo,8,4)."-".substr($cnpjLimpo,12,2) : $desp->cnpj;

        $sheet->setCellValue('A' . $row, $processoModel->formatarProcesso($processoModel->findById($desp->proc_id) ?? ''));
        $sheet->setCellValue('B' . $row, $desp->programa_nome ?? '');
        $sheet->setCellValue('C' . $row, ($desp->categoria == 'C') ? 'Custeio' : (($desp->categoria == 'K') ? 'Capital' : ''));
        $sheet->setCellValue('D' . $row, $desp->razao_social ?? '');
        $sheet->setCellValue('E' . $row, $cnpj);
        $sheet->setCellValue('F' . $row, $desp->descricao ?? '');
        $sheet->setCellValue('G' . $row, $desp->documento ?? '');
        $sheet->setCellValue('H' . $row, $data);
        
        // Escreve o valor como número para o Excel conseguir somar
        $sheet->setCellValueExplicit('I' . $row, $valor, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getStyle("A$row:C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G$row:H$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
    }
    
    // Linha de Total no final
    $sheet->setCellValue('H' . $row, 'TOTAL GERAL:');
    $sheet->getStyle('H' . $row)->getFont()->setBold(true);
    $sheet->setCellValueExplicit('I' . $row, $somaTotal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    $sheet->getStyle('I' . $row)->getFont()->setBold(true);
    $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
}

// Auto-ajuste das colunas
foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$fileName = "Relatorio_Despesas_" . date('d_m_Y') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;