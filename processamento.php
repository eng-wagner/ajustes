<?php
// Inicia a sessão e carrega as suas configurações/classes (Ajuste o caminho conforme o seu projeto)
require __DIR__ . "/vendor/autoload.php"; // Se usar composer
require_once __DIR__ . "/source/Helpers/Helpers.php"; // Onde está a sua função limparMoedaSQL()

// Verifica se os dados vieram por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    // 1. RECEBE OS DADOS PRINCIPAIS DO AJUSTE
    $dadosAjuste = [
        'instituicao_id'        => $postData['instituicao_id'],
        'processo_parceria_id'  => !empty($postData['processo_parceria_id']) ? $postData['processo_parceria_id'] : null,
        'processo_pagamento_id' => !empty($postData['processo_pagamento_id']) ? $postData['processo_pagamento_id'] : null,
        'tipo_ajuste'           => $postData['tipo_ajuste'],
        'numero_ajuste'         => $postData['numero_ajuste'],
        'objeto'                => $postData['objeto'],
        'data_inicio'           => $postData['data_inicio'],
        'data_fim'              => $postData['data_fim'],
        // Aqui usamos a sua função para transformar "R$ 1.500,00" em "1500.00" para o MariaDB
        'valor_global_inicial'  => limparMoedaSQL($postData['valor_global_inicial']) 
    ];

    // 2. RECEBE OS DADOS DAS CONTAS BANCÁRIAS (Que vêm como Arrays)
    $bancos         = $postData['banco'];
    $agencias       = $postData['agencia'];
    $contasCorrente = $postData['conta_corrente'];
    $fontesRecursos = $postData['fonte_recursos'];

    $listaContas = [];
    
    // Fazemos um loop para agrupar cada conta com sua agência e fonte
    if (!empty($bancos)) {
        foreach ($bancos as $indice => $nomeBanco) {
            // Só adiciona se o nome do banco não estiver vazio
            if (!empty($nomeBanco)) {
                $listaContas[] = [
                    'banco'          => $nomeBanco,
                    'agencia'        => $agencias[$indice],
                    'conta_corrente' => $contasCorrente[$indice],
                    'fonte_recursos' => $fontesRecursos[$indice]
                ];
            }
        }
    }

    /* ======================================================================
    3. SALVANDO NO BANCO DE DADOS (USANDO SEU MODEL)
    ======================================================================
    Como eu vi que você usa a estrutura Source\Models, 
    aqui você instanciaria a sua classe. Exemplo:
    
    $ajusteModel = new \Source\Models\Ajuste();
    $sucesso = $ajusteModel->salvarNovoAjuste($dadosAjuste, $listaContas);

    if ($sucesso) {
        // Redireciona de volta com o seu toast de sucesso!
        header("Location: dashboard_ajustes.php?status=sucesso");
        exit;
    } else {
        header("Location: dashboard_ajustes.php?status=erro");
        exit;
    }
    */
   
    // Apenas para testes agora: Vamos imprimir na tela para você ver a mágica do Array das contas!
    echo "<h3>Dados do Ajuste Recebidos:</h3>";
    echo "<pre>"; print_r($dadosAjuste); echo "</pre>";

    echo "<h3>Contas Bancárias Recebidas:</h3>";
    echo "<pre>"; print_r($listaContas); echo "</pre>";
    exit;

} else {
    // Se tentarem acessar a página direto pela URL, manda de volta pro dashboard
    header("Location: dashboard_ajustes.php");
    exit;
}