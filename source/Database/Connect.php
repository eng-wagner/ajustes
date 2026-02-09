<?php

namespace Source\Database;

use \PDO;
use \PDOException;

class Connect
{   
    //private const HOST = "localhost";
    //private const USER = "id12365546_root";
    //private const DBNAME = "id12365546_formacoes";
    //private const PASSWD = "senha";
    
    private const HOST = "localhost";
    private const USER = "root";
    private const DBNAME = "ajustes";
    private const PASSWD = "";

    // private const HOST = "ajustes.mysql.dbaas.com.br";
    // private const USER = "ajustes";
    // private const DBNAME = "ajustes";
    // private const PASSWD = "se331@Ajustes";

    private const OPTIONS = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ];

    private static $instance;

    public static function getInstance(): PDO
    {
        if (empty(self::$instance)) {
            try{
                self::$instance = new PDO(
                    "mysql:host=" . self::HOST . ";dbname=" . self::DBNAME,
                    self::USER,
                    self::PASSWD,
                    self::OPTIONS
                );
            } catch (PDOException $exception) {
                die("<h1>Erro ao se conectar no banco de dados</h1>");
            }
        }

        return self::$instance;
    }

    final private function __construct()
    {

    }

/*    final private function __clone()
    {

    }*/
}