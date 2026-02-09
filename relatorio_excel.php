<?php
require __DIR__ . "/source/autoload.php";
require __DIR__ . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Source\Database\Connect;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$timezone = new DateTimeZone("America/Sao_Paulo");

$agora = new DateTime('now', $timezone);
$agora = $agora->format('d_m_Y');

// 1. Definindo o Cabeçalho
$headers = [
    'Nº Processo', 'Programa', 'Instituição', 'Status', 'Entrega', 
    'Movimentação', 'Análise Execução', 'Responsável', 
    'Enc. An. Financeira', 'Análise Financeira', 'Responsável', 'SIGPC'
];
// Preenche o cabeçalho na linha 1
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// 2. Buscando os Dados (Sua lógica de banco adaptada)
$stmt = "SELECT p.id, p.orgao, p.numero, p.ano, p.digito, p.tipo, i.instituicao 
         FROM processos p 
         JOIN instituicoes i ON p.instituicao_id = i.id 
         WHERE p.tipo LIKE '%PDDE%' ORDER BY i.instituicao ASC";

$sql = Connect::getInstance()->prepare($stmt);
$linha = 2; // Começamos a preencher na linha 2

if ($sql->execute()) {
    while ($proc = $sql->fetch()) {
        
        // ... (Mantenha toda a sua lógica de processamento de datas e nomes aqui)
        // Exemplo simplificado de como inserir na planilha:
        
        $numProcesso = $proc->orgao . '.' . $proc->numero . '/' . $proc->ano . '-' . $proc->digito;
        
        $sheet->setCellValue('A' . $linha, $numProcesso);
        $sheet->setCellValue('B' . $linha, $proc->tipo ?? '');
        $sheet->setCellValue('C' . $linha, $proc->instituicao ?? '');
        // ... continue preenchendo as colunas D, E, F até L
        
        $linha++;
    }
}

// 3. Formatação (Opcional, mas deixa o relatório lindo)
$sheet->getStyle('A1:L1')->getFont()->setBold(true); // Negrito no cabeçalho
foreach (range('A', 'L') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true); // Auto-ajuste de largura
}

// 4. Configuração de Download
$filename = "Relatorio_PDDE_" . date('d_m_Y') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;