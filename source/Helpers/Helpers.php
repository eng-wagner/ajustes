<?php   
// source/Helpers/Helpers.php
//namespace Source\Helpers;


function redirecionar(string $url, string $tipo, string $mensagem = ""): void
{
    $_SESSION[$tipo == 'sucesso' ? 'toast_sucesso' : 'toast_erro'] = $mensagem;
    header("Location: $url");
    exit();    
}

function limparMoedaSQL($valor): float 
{
    if (empty($valor)) return 0.00;
    $limpo = preg_replace('/[^0-9,-]/', '', $valor); // Remove R$ e pontos
    return (float) str_replace(',', '.', $limpo); // Troca vírgula por ponto
}

function limparCNPJ($cnpj): string
{
    if (empty($cnpj)) return '';
    return preg_replace('/[^0-9]/', '', $cnpj);    
}

function parseCheckbox(?string $valor): int
{
    return ($valor === "1" || $valor === "on") ? 1 : 0;
}

/**
 * Retorna a data e hora atual no fuso de São Paulo.
 */
function getCurrentDateTime(): string
{
    $timezone = new DateTimeZone("America/Sao_Paulo");
    return (new DateTime('now', $timezone))->format('Y-m-d H:i:s');
}