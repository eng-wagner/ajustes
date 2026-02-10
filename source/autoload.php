<?php

// Caminho para a raiz do projeto (um nível acima da pasta source)
$rootDir = dirname(__DIR__);

// 1. Carrega as configurações (Banco, Timezone, Senhas)
$configFile = $rootDir . "/config.php";
if (file_exists($configFile)) {
    require $configFile;
} else {
    // Se não achar o config, para tudo. É segurança.
    die("Erro: O arquivo 'config.php' não foi encontrado na raiz: " . $rootDir);
}

// 2. Carrega o Autoload do Composer
// O Composer agora sabe carregar o PhpSpreadsheet E as suas classes Source\
$vendorAutoload = $rootDir . "/vendor/autoload.php";
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
} else {
    die("Erro: A pasta 'vendor' não existe. Rode 'composer install'.");
}


?>