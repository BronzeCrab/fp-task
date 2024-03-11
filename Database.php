<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    public mysqli $mysqli;
    private string $unique_skip_ident = '0c97e6257d8de32a3983cdc10f523799';

    // решил немного заполнить бд:
    private function __fillDb()
    {
        echo "Try to fill the db" . PHP_EOL;
        $this->mysqli->query("CREATE TABLE IF NOT EXISTS users (
            user_id int primary key NOT NULL AUTO_INCREMENT,
            name text NOT NULL,
            block int(11) NOT NULL,
            email text NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $this->mysqli->query("INSERT INTO users(name, block, email) VALUES ('Jack', 1, 'test@test.ru')");
        $this->mysqli->query("INSERT INTO users(name, block, email) VALUES ('Jack', 1, 'test@test2.ru')");
        $this->mysqli->query("INSERT INTO users(name, block, email) VALUES ('Jack', 2, 'test@test2.ru')");
        $this->mysqli->query("INSERT INTO users(name, block, email) VALUES ('Jack', 0, 'test@test2.ru')");
        echo "Created users table and some user." . PHP_EOL;
    }

    private function __checkTypeOfArg($an_arg): string
    {
        if (is_array($an_arg)) {
            if (array_is_list($an_arg)) {
                return 'sequential';
            }
            return 'associative';
        }
        return 'not_array';
    }

    private function __parseSequentialArray(array $an_array, string $specifier): string
    {
        $parsed_str = '';
        for ($i = 0; $i < count($an_array); $i++) {
            if ($i > 0) {
                $parsed_str .= ', ';
            }
            if (is_string($an_array[$i])) {
                // значения
                if ($specifier === '?a') {
                    $parsed_str .= "'" . $an_array[$i] . "'";
                    // идентификаторы
                } else {
                    $parsed_str .= '`' . $an_array[$i] . '`';
                }
            } else {
                $parsed_str .= $this->__convertToMIXED($an_array[$i]);
            }
        }
        return $parsed_str;
    }

    private function __parseAssociativeArray(array $an_array): string
    {
        $parsed_str = '';
        $counter = 0;
        foreach ($an_array as $column_name => $column_value) {
            if ($counter > 0) {
                $parsed_str .= ', ';
            }
            $formated_column_name = '`' . $column_name . '`';
            if (is_string($column_value)) {
                $formated_column_value = "'" . $column_value . "'";
            } else {
                $formated_column_value = $this->__convertToMIXED($column_value);
            }
            $parsed_str .= $formated_column_name . ' = ' . $formated_column_value;
            $counter++;
        }
        return $parsed_str;
    }

    private function __parseArg($an_arg, string $specifier): string
    {
        $type_of_arg = $this->__checkTypeOfArg($an_arg);
        if ($type_of_arg === 'sequential') {
            $an_arg = $this->__parseSequentialArray($an_arg, $specifier);
        } else if ($type_of_arg === 'associative') {
            $an_arg = $this->__parseAssociativeArray($an_arg);
        } else {
            // тут это просто идентификатор, мб только строка.
            if (!is_string($an_arg)) {
                throw new Exception("ERROR: идентификатор должен быть строкой!");
            }
            $an_arg = "`" . $an_arg . "`";
        }
        return $an_arg;
    }

    // проверка баланса фигурных скобок, юзаем массив как стек:
    private function __checkBalanceOfCurlyBraces(string $query): bool
    {
        $some_stack = array();
        for ($i = 0; $i < strlen($query); $i++) {
            if ($query[$i] === "{") {
                array_push($some_stack, $query[$i]);
            } else if ($query[$i] === "}") {
                array_pop($some_stack);
            }
        }
        return count($some_stack) === 0;
    }

    // после главного цикла формируем окончательный разультат:
    private function __convertToStrAndRemoveTrash(array $query_array, array $indexes_to_delete): string
    {
        $result_str = '';
        for ($i = 0; $i < count($query_array); $i++) {
            if (!in_array($i, $indexes_to_delete)) {
                $result_str .= $query_array[$i];
            }
        }
        return $result_str;
    }

    private function __convertToMIXED(mixed $a_arg, string $specifier = null): mixed
    {
        if ($a_arg === null) {
            return 'NULL';
        }
        if ($a_arg === true) {
            return 1;
        }
        if ($a_arg === false) {
            return 0;
        }
        if ($specifier === '?d') {
            return intval($a_arg);
        }
        if ($specifier === '?f') {
            return floatval($a_arg);
        }
        return $a_arg;
    }

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        $this->__fillDb();
    }

    public function buildQuery(string $query, array $args = []): string
    {
        if (count($args) !== substr_count($query, '?')) {
            throw new Exception("ERROR: args count != question marks count in query");
        }

        if (!$this->__checkBalanceOfCurlyBraces($query)) {
            throw new Exception("ERROR: imbalance of curly braces");
        }

        if (!$args) {
            return $query;
        }

        $args_counter = 0;
        $array_copy_of_query = str_split($query);
        $indexes_to_delete = array();
        // Занки вопроса - меняем в массиве array_copy_of_query сразу,
        // а идентификаторы и фигурные скобки - просто помечаем на удаление после цилка.
        // Чтобы не сделать так, чтобы длины массива и строки стали неравны. 
        for ($i = 0; $i < strlen($query); $i++) {
            if ($query[$i] === '?' and ($i === strlen($query) - 1 or !(in_array($query[$i + 1], ['#', 'd', 'f', 'a'])))) {
                if (is_string($args[$args_counter])) {
                    $an_arg = "'" . $args[$args_counter] . "'";
                } else {
                    $an_arg = $args[$args_counter];
                }
                $an_arg = $this->__convertToMIXED($an_arg, '?');
                $array_copy_of_query[$i] = $an_arg;
                $args_counter++;
            } else if (substr($query, $i, 2) === '?#' or substr($query, $i, 2) === '?a') {
                $an_arg = $args[$args_counter];
                $specifier = substr($query, $i, 2);
                $an_arg = $this->__parseArg($an_arg, $specifier);
                $array_copy_of_query[$i] = $an_arg;
                array_push($indexes_to_delete, $i + 1);
                $args_counter++;
            } else if (substr($query, $i, 2) === '?d' or substr($query, $i, 2) === '?f') {
                $an_arg = $args[$args_counter];
                $specifier = substr($query, $i, 2);
                $an_arg = $this->__convertToMIXED($an_arg, $specifier);
                $array_copy_of_query[$i] = $an_arg;
                array_push($indexes_to_delete, $i + 1);
                $args_counter++;
            } else if ($query[$i] === '{') {
                array_push($indexes_to_delete, $i);
                $j = 0;
                while ($i + $j < strlen($query)) {
                    $j++;
                    if ($query[$i + $j] === '}') {
                        $k = $i + $j;
                        array_push($indexes_to_delete, $k);
                        break;
                    }
                }
                if ($args[$args_counter] === $this->unique_skip_ident) {
                    // mark to del all indexes from i+1 to k-1
                    for ($z = $i + 1; $z < $k; $z++) {
                        array_push($indexes_to_delete, $z);
                    }
                }
            }
        }

        $result = $this->__convertToStrAndRemoveTrash($array_copy_of_query, $indexes_to_delete);

        return $result;
    }

    public function skip()
    {
        return $this->unique_skip_ident;
    }
}
