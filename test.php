<?php

use FpDbTest\Database;
use FpDbTest\DatabaseTest;

spl_autoload_register(function ($class) {
    $a = array_slice(explode('\\', $class), 1);
    if (!$a) {
        throw new Exception();
    }
    $filename = implode('/', [__DIR__, ...$a]) . '.php';
    require_once $filename;
});

$mysqli = @new mysqli('127.0.0.1', 'root', 'password', 'database', 3306);

if ($mysqli->connect_errno) {
    throw new Exception($mysqli->connect_error);
} else {
    echo 'Connect to db is successfull' . PHP_EOL;
}

$db = new Database($mysqli);
$test = new DatabaseTest($db);
$test->testBuildQuery();
$test->additionalTestBuildQuery();
echo 'Все тесты прошли успешно.' . PHP_EOL;

$test->testDbQueries();
exit('OK' . PHP_EOL);

