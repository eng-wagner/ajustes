<?php

// 1. Carrega o Autoload e Bibliotecas
require __DIR__ . "/source/autoload.php";

use Source\Models\RelatorioPDDE;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Limpa buffer de saída
if (ob_get_contents()) ob_end_clean();

// 2. Instancia o Model
$model = new RelatorioPDDE();

// --- MUDANÇA AQUI: ---
// Não pegamos mais filtros da URL. Passamos NULL para trazer TUDO.
$dados = $model->buscarDadosGerais(null, null);

// 3. Cria a Planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Relatório Geral PDDE');

// ==========================================
// Cabeçalhos
// ==========================================
$headers = [
    'A' => 'Nº Processo',
    'B' => 'Programa',
    'C' => 'Instituição',
    'D' => 'Status',
    'E' => 'Entrega',
    'F' => 'Movimentação',
    'G' => 'Análise Execução',
    'H' => 'Responsável',
    'I' => 'Enc. An. Financeira',
    'J' => 'Análise Financeira',
    'K' => 'Responsável',
    'L' => 'SIGPC'
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

if (!empty($dados)) {
    foreach ($dados as $proc) {
        
        $numProcesso = $model->formatarProcesso($proc);
        $status = $proc->status_nome ?? 'Aguardando Entrega';
        
        // Formatações
        $entrega = $proc->data_ent ? date('d/m/Y', strtotime($proc->data_ent)) : '';
        $sMovimento = ($proc->s_movimento == 1) ? 'Sem movimento' : '';
        $analiseEx = $proc->data_analise_ex ? date('d/m/Y', strtotime($proc->data_analise_ex)) : '';
        $encFinanceira = $proc->data_enc_af ? date('d/m/Y', strtotime($proc->data_enc_af)) : '';
        $analiseFin = $proc->data_analise_fin ? date('d/m/Y', strtotime($proc->data_analise_fin)) : '';
        $sigpc = $proc->data_sigpc ? date('d/m/Y', strtotime($proc->data_sigpc)) : '';

        $usuarioEx = $proc->usuario_ex_nome ? explode(' ', $proc->usuario_ex_nome)[0] : '';
        $usuarioFin = $proc->usuario_fin_nome ? explode(' ', $proc->usuario_fin_nome)[0] : '';

        // Escreve na célula
        $sheet->setCellValue('A' . $row, $numProcesso);
        $sheet->setCellValue('B' . $row, $proc->tipo);
        $sheet->setCellValue('C' . $row, $proc->instituicao);
        $sheet->setCellValue('D' . $row, $status);
        $sheet->setCellValue('E' . $row, $entrega);
        $sheet->setCellValue('F' . $row, $sMovimento);
        $sheet->setCellValue('G' . $row, $analiseEx);
        $sheet->setCellValue('H' . $row, $usuarioEx);
        $sheet->setCellValue('I' . $row, $encFinanceira);
        $sheet->setCellValue('J' . $row, $analiseFin);
        $sheet->setCellValue('K' . $row, $usuarioFin);
        $sheet->setCellValue('L' . $row, $sigpc);

        // Centraliza datas e nomes
        $sheet->getStyle("E$row:L$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
    }
}

// Auto-ajuste das colunas
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Download
$fileName = "Relatorio_Geral_" . date('d_m_Y') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;