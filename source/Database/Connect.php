<?php

namespace Source\Database;

use \PDO;
use \PDOException;

class Connect
{
    private static $instance;

    public static function getInstance(): PDO
    {
        if (empty(self::$instance)) {
            try {
                // Buscamos a configuração definida no arquivo config.php
                $db = DATA_LAYER_CONFIG;

                self::$instance = new PDO(
                    "mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'] . ";port=" . $db['port'],
                    $db['username'],
                    $db['passwd'],
                    $db['options']
                );
            } catch (PDOException $exception) {
                // Em produção, evite mostrar o erro exato do banco para o usuário.
                // Idealmente, registre em um log e mostre uma mensagem genérica.
                die("<h1>Erro no Banco:</h1><p>" . $exception->getMessage() . "</p>");
            }
        }
        return self::$instance;
    }

    /**
     * Construtor privado previne que uma nova instância da
     * classe seja criada através do operador `new` de fora da classe.
     */
    private function __construct()
    {
    }

    /**
     * Método clone privado previne a clonagem da instância
     */
    private function __clone()
    {
    }
}