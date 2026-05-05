<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";

$pdo = Source\Database\Connect::getInstance();

use Source\Models\User;

$userModel = new User();

if (empty($_SESSION['user_id'])) {
    header("Location: index.php?status=sessao_invalida");
    exit();
}

$loggedUser = $userModel->findById($_SESSION['user_id']);
if (!$loggedUser || $loggedUser->perfil !== 'adm') {
    // Se não for admin, chuta de volta para o hub
    header("Location: hub.php?erro=acesso_negado");
    exit();
}

$userName = $loggedUser->nome;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo_csv'])) {
    
    $arquivo = $_FILES['arquivo_csv'];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        die("Erro ao fazer upload do arquivo.");
    }
    
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if ($extensao !== 'csv') {
        die("Por favor, envie apenas arquivos no formato .csv");
    }

    $tipoImportacao = $_GET['tipo'] ?? ''; // Pega o valor da URL

    switch ($tipoImportacao) {
        case 'processos':
            // Aqui você coloca a lógica de ler o CSV 
            // e salvar na tabela 'processos'
            processarProcessos($_FILES['arquivo_csv'], $pdo);
            break;

        case 'ajustes':
            // Aqui a lógica para a tabela 'ajustes'
            processarAjustes($_FILES['arquivo_csv']);
            break;

        case 'empenhos':
            // Lógica para 'empenhos'
            break;

        default:
            die("Tipo de importação inválido!");
    }
}

// Função inteligente para corrigir a codificação do Excel
function corrigirAcentos($texto) {
    $texto = trim($texto);
    
    // O mb_check_encoding verifica se o texto já é UTF-8. 
    // Se NÃO for, a gente converte de ISO-8859-1 (Padrão Excel) para UTF-8.
    if (!mb_check_encoding($texto, 'UTF-8')) {
        return mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
    }
    
    return $texto;
}

function processarProcessos($arquivo, $pdo) {
    
    $handle = fopen($arquivo['tmp_name'], "r");

    if ($handle !== FALSE) {
        try {
            $pdo->beginTransaction();

            // Prepara a query de INSERÇÃO
            $stmtInsert = $pdo->prepare("INSERT INTO processos (orgao, numero, ano, digito, assunto, tipo, detalhamento, instituicao_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Prepara a query de VERIFICAÇÃO (checa se já existe no banco)
            $stmtCheck = $pdo->prepare("SELECT id FROM processos WHERE numero = ?");

            $linha = 0;
            $inseridos = 0;
            
            // Array para guardar os processos lidos neste arquivo (evita duplicidade no próprio CSV)
            $processosNoCSV = []; 

            while (($dados = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $linha++;

                // Pula a primeira linha (cabeçalho)
                if ($linha === 1) {
                    continue; 
                }

                $orgao           = "SB";
                $numero_processo = trim($dados[0] ?? '');
                $ano             = trim($dados[1] ?? '');
                $digito          = trim($dados[2] ?? '');
                $assunto         = corrigirAcentos($dados[3] ?? '');
                $tipo            = corrigirAcentos($dados[4] ?? '');
                $detalhamento    = corrigirAcentos($dados[5] ?? '');
                $instituicao_id  = trim($dados[6] ?? '');

                if (!empty($numero_processo)) {
                    
                    // 1. Verifica se o processo está repetido dentro do próprio CSV
                    if (in_array($numero_processo, $processosNoCSV)) {
                        // O throw new Exception cancela tudo e joga o erro direto pro bloco catch
                        throw new Exception("O processo '{$numero_processo}' está duplicado dentro do arquivo CSV.");
                    }
                    $processosNoCSV[] = $numero_processo; // Adiciona na lista de lidos

                    // 2. Verifica se o processo já existe no Banco de Dados
                    $stmtCheck->execute([$numero_processo]);
                    if ($stmtCheck->rowCount() > 0) {
                        throw new Exception("O processo '{$numero_processo}' já está cadastrado no sistema.");
                    }

                    // Se passou por todas as barreiras, insere!
                    $stmtInsert->execute([$orgao, $numero_processo, $ano, $digito, $assunto, $tipo, $detalhamento, $instituicao_id]);
                    $inseridos++;
                }
            }

            fclose($handle);
            
            // Se chegou até aqui sem disparar nenhum erro, confirma tudo!
            $pdo->commit();
            
            header("Location: importar_dados.php?sucesso=1");
            exit();

        } catch (Exception $e) {
            // Se deu QUALQUER erro (duplicado, banco caiu, etc), desfaz TUDO.
            $pdo->rollBack();
            if (isset($handle)) fclose($handle);
            
            // Mostra a mensagem exata do erro na tela
            echo "<div class='alert alert-danger'>
                    <strong>Importação Cancelada (Erro na linha {$linha}):</strong><br> 
                    " . $e->getMessage() . "
                </div>";
        }
    } else {
        die("Não foi possível abrir o arquivo.");
    }
}
?>