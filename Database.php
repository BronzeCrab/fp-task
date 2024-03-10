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
        $this->mysqli->query("INSERT INTO users(name, block) VALUES ('bob', 1)");
        echo "Created users table and some user." . PHP_EOL;
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

        if (count($args) == 1 and !(is_array($args[0]))) {
            $query = str_replace("?", "'" . $args[0] . "'", $query);
        } else if (count($args) == 1 and is_array($args[0])) {
            $columns_names_and_values = '';
            $counter = 0;
            foreach ($args[0] as $column_name => $column_value) {
                if ($counter > 0) {
                    $columns_names_and_values .= ', ';
                }
                $formated_column_name = '`' . $column_name . '`';
                if ($column_value === null) {
                    $formated_column_value = "NULL";
                } else {
                    $formated_column_value = "'" . $column_value . "'";
                }
                $columns_names_and_values .= $formated_column_name . ' = ' . $formated_column_value;
                $counter++;
            }
            $query = str_replace("?a", $columns_names_and_values, $query);
        } else {
            $columns_names = '';
            for ($i = 0; $i < count($args[0]); $i++) {
                if ($i > 0) {
                    $columns_names .= ', ';
                }
                $columns_names .= '`' . $args[0][$i] . '`';
            }

            $query = str_replace("#", '', $query);

            $counter = 0;
            for ($i = 0; $i < strlen($query); $i++) {
                if ($query[$i] == '?' and $query[$i + 1] !== 'd') {
                    $query = substr($query, 0, $i) . $columns_names . substr($query, $i + 1, strlen($query));
                } else if (substr($query, $i, 2) == '?d') {
                    $query = substr($query, 0, $i) . $args[$counter + 1] . substr($query, $i + 2, strlen($query));
                    $counter++;
                }
            }
        }
        return $query;
    }

    public function skip()
    {
        throw new Exception();
    }
}
