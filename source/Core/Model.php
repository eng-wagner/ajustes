<?php

namespace Source\Core;

use Source\Database\Connect;
use PDO;

abstract class Model
{
    /** @var PDO */
    protected $pdo;

    public function __construct()
    {        
        $this->pdo = Connect::getInstance();
    }
}