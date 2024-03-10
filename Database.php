<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;
    private string $unique_skip_ident = '0c97e6257d8de32a3983cdc10f523799';

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

    private function __check_type_of_arg($an_arg): string
    {
        if (is_array($an_arg)) {
            if (array_is_list($an_arg)) {
                return 'sequential';
            }
            return 'associative';
        }
        return 'not_array';
    }

    private function __parse_sequential_array(array $an_array): string
    {
        $parsed_str = '';
        for ($i = 0; $i < count($an_array); $i++) {
            if ($i > 0) {
                $parsed_str .= ', ';
            }
            if (!is_int($an_array[$i])) {
                $parsed_str .= '`' . $an_array[$i] . '`';
            } else {
                $parsed_str .= $an_array[$i];
            }
        }
        return $parsed_str;
    }

    private function __parse_associative_array(array $an_array): string
    {
        $parsed_str = '';
        $counter = 0;
        foreach ($an_array as $column_name => $column_value) {
            if ($counter > 0) {
                $parsed_str .= ', ';
            }
            $formated_column_name = '`' . $column_name . '`';
            if ($column_value === null) {
                $formated_column_value = "NULL";
            } else {
                $formated_column_value = "'" . $column_value . "'";
            }
            $parsed_str .= $formated_column_name . ' = ' . $formated_column_value;
            $counter++;
        }
        return $parsed_str;
    }

    private function __parse_arg($an_arg): string
    {
        $type_of_arg = $this->__check_type_of_arg($an_arg);
        if ($type_of_arg === 'sequential') {
            $an_arg = $this->__parse_sequential_array($an_arg);
        } else if ($type_of_arg === 'associative') {
            $an_arg = $this->__parse_associative_array($an_arg);
        } else {
            $an_arg = "`" . $an_arg . "`";
        }
        return $an_arg;
    }

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        $this->__fill_db();
    }

    public function buildQuery(string $query, array $args = []): string
    {
        if (count($args) !== substr_count($query, '?')) {
            throw new Exception("ERROR: args count != question marks count in query");
        }

        if (!$args) {
            return $query;
        }

        $args_counter = 0;
        for ($i = 0; $i < strlen($query); $i++) {
            if ($query[$i] === '?' and !(in_array($query[$i + 1], ['#', 'd', 'f', 'a']))) {
                $an_arg = "'" . $args[$args_counter] . "'";
                $query = substr($query, 0, $i) . $an_arg . substr($query, $i + 1, strlen($query));
            } else if (substr($query, $i, 2) === '?#') {
                $an_arg = $args[$args_counter];
                $an_arg = $this->__parse_arg($an_arg);
                $query = substr($query, 0, $i) . $an_arg . substr($query, $i + 2, strlen($query));
                $args_counter++;
            } else if (substr($query, $i, 2) === '?d') {
                $an_arg = intval($args[$args_counter]);
                $query = substr($query, 0, $i) . $an_arg . substr($query, $i + 2, strlen($query));
                $args_counter++;
            } else if (substr($query, $i, 2) === '?f') {
                $an_arg = floatval($args[$args_counter]);
                $query = substr($query, 0, $i) . $an_arg . substr($query, $i + 2, strlen($query));
                $args_counter++;
            } else if (substr($query, $i, 2) === '?a') {
                $an_arg = $args[$args_counter];
                $an_arg = $this->__parse_arg($an_arg);
                $query = substr($query, 0, $i) . $an_arg . substr($query, $i + 2, strlen($query));
                $args_counter++;
            } else if (substr($query, $i, 1) === '{') {
                if ($args[$args_counter] === $this->unique_skip_ident) {
                    $j = 0;
                    while ($i + $j < strlen($query)) {
                        $j++;
                        if ($query[$i + $j] === '}') {
                            break;
                        }
                    }
                    $query = substr($query, 0, $i) . substr($query, $i + $j + 1, strlen($query));
                } else {
                    $query = str_replace("{", '', $query);
                    $query = str_replace("}", '', $query);
                }
            }
        }
        return $query;
    }

    public function skip()
    {
        return $this->unique_skip_ident;
    }
}
