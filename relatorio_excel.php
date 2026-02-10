<?php

// 1. Carrega tudo (Config + Banco + Planilha)
require __DIR__ . "/source/autoload.php";

use Source\Database\Connect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Limpa qualquer lixo de memória anterior para não corromper o Excel
ob_end_clean(); 

// Cria a planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Relatório PDDE');

// ==========================================
// 2. Definindo o Cabeçalho (Linha 1)
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
    // Deixa negrito e centralizado
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// ==========================================
// 3. Buscando e Preenchendo os Dados
// ==========================================
$stmt = "SELECT p.id, p.orgao, p.numero, p.ano, p.digito, p.tipo, i.instituicao 
         FROM processos p 
         JOIN instituicoes i ON p.instituicao_id = i.id 
         WHERE p.tipo LIKE '%PDDE%' 
         ORDER BY i.instituicao ASC";

$query = Connect::getInstance()->prepare($stmt);
$query->execute();

$row = 2; // Começamos a preencher na linha 2

while ($proc = $query->fetch()) {
    
    // -- Lógica original recuperada e limpa --
    
    // Busca dados da análise (Sub-query)
    // DICA PARA O FUTURO: Isso pode ser otimizado com JOIN, mas vamos manter sua lógica por enquanto.
    $stmtAnalise = Connect::getInstance()->prepare("SELECT * FROM analise_pdde_25 WHERE proc_id = :id");
    $stmtAnalise->bindValue(":id", $proc->id);
    $stmtAnalise->execute();
    $analise = $stmtAnalise->fetch();

    // Inicializa variáveis para não dar erro de "undefined"
    $status = "Aguardando Entrega";
    $entrega = "";
    $sMovimento = "";
    $analiseEx = "";
    $usuarioEx = "";
    $encFinanceira = "";
    $analiseFin = "";
    $usuarioFin = "";
    $sigpc = "";

    if ($analise) {
        // Formatação de Datas (Função nativa do PHP direto na linha)
        $entrega = $analise->data_ent ? date('d/m/Y', strtotime($analise->data_ent)) : '';
        $analiseEx = $analise->data_analise_ex ? date('d/m/Y', strtotime($analise->data_analise_ex)) : '';
        $encFinanceira = $analise->data_enc_af ? date('d/m/Y', strtotime($analise->data_enc_af)) : '';
        $analiseFin = $analise->data_analise_fin ? date('d/m/Y', strtotime($analise->data_analise_fin)) : '';
        $sigpc = $analise->data_sigpc ? date('d/m/Y', strtotime($analise->data_sigpc)) : '';
        $sMovimento = ($analise->s_movimento == 1) ? 'Sem movimento' : '';

        // Busca Status
        if ($analise->status_id) {
            $st = Connect::getInstance()->prepare("SELECT status_pc FROM status_processo WHERE id = :id");
            $st->bindValue(":id", $analise->status_id);
            $st->execute();
            $statusData = $st->fetch();
            if ($statusData) $status = $statusData->status_pc;
        }

        // Busca Responsável Execução
        if ($analise->usuario_ex_id) {
            $ue = Connect::getInstance()->prepare("SELECT nome FROM usuarios WHERE id = :id");
            $ue->bindValue(":id", $analise->usuario_ex_id);
            $ue->execute();
            $userE = $ue->fetch();
            if ($userE) $usuarioEx = explode(' ', $userE->nome)[0]; // Pega só o primeiro nome
        }

        // Busca Responsável Financeiro
        if ($analise->usuario_fin_id) {
            $uf = Connect::getInstance()->prepare("SELECT nome FROM usuarios WHERE id = :id");
            $uf->bindValue(":id", $analise->usuario_fin_id);
            $uf->execute();
            $userF = $uf->fetch();
            if ($userF) $usuarioFin = explode(' ', $userF->nome)[0];
        }
    }

    // -- Preenchendo a Planilha --
    
    $numProcesso = "{$proc->orgao}.{$proc->numero}/{$proc->ano}-{$proc->digito}";

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

    $row++;
}

// ==========================================
// 4. Finalização e Download
// ==========================================

// Auto-ajuste da largura das colunas
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Define o nome do arquivo
$fileName = "Relatorio_PDDE_" . date('d_m_Y') . ".xlsx";

// Cabeçalhos HTTP para forçar o download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Salva e envia para o navegador
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;