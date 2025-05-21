<?php

namespace App\Core;

use PDO;

abstract class Model {
    protected Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }
} 