<?php   
// source/Helpers/Helpers.php
namespace Source\Helpers;

function redirecionar(string $url, string $tipo, string $mensagem = ""): void
{
    $_SESSION[$tipo == 'sucesso' ? 'toast_sucesso' : 'toast_erro'] = $mensagem;
    header("Location: $url");
    exit();    
}   