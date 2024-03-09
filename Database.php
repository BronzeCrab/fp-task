<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    private function __fill_db()
    {
        echo "Try to fill the db" . PHP_EOL;
        $this->mysqli->query("CREATE TABLE IF NOT EXISTS users (
            user_id int primary key NOT NULL AUTO_INCREMENT,
            name text NOT NULL,
            block int(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    }
    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        $this->__fill_db();
    }


    public function buildQuery(string $query, array $args = []): string
    {
        if (!$args) {
            return $query;
        }
        throw new Exception();
    }

    public function skip()
    {
        throw new Exception();
    }
}
