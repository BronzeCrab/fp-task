<?php

namespace FpDbTest;

use Exception;

class DatabaseTest
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function testBuildQuery(): void
    {
        $results = [];

        $results[] = $this->db->buildQuery('SELECT name FROM users WHERE user_id = 1');

        $results[] = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        );

        $results[] = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
            [['name', 'email'], 2, true]
        );

        $results[] = $this->db->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null]]
        );

        foreach ([null, true] as $block) {
            $results[] = $this->db->buildQuery(
                'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                ['user_id', [1, 2, 3], $block ?? $this->db->skip()]
            );
        }

        $correct = [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
            'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1',
            'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
        ];

        if ($results !== $correct) {
            throw new Exception('Failure.');
        }
    }

    public function additionalTestBuildQuery(): void
    {
        // проверяем, что выбрасывается исключение:
        $caught = false;
        try {
            $this->db->buildQuery(
                'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
                [['name', 'email'], 2, true, 1]
            );
        } catch (Exception $e) {
            echo 'Правильно выброшено исключение в доп. тесте: ', $e->getMessage(), "\n";
            $caught = true;
        }
        if (!$caught) {
            throw new Exception('Failure in add tests.');
        }

        // проверяем, что нормально отрабатывает ?f:
        $result = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE some_col = ?f AND block = ?d AND email = ?',
            [['name', 'email', 'some_col'], '2.41test', true, null]
        );
        $correct = 'SELECT `name`, `email`, `some_col` FROM users WHERE some_col = 2.41 AND block = 1 AND email = NULL';
        if ($result !== $correct) {
            throw new Exception('Failure1 in additionalTestBuildQuery.');
        }

        // проверяем как работает массив значений c разными аргументами:
        $result = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a)',
            ['name', ['test_name1', 'test_name2', 'test_name3', null, false, true]]
        );
        $correct = 'SELECT name FROM users WHERE `name` IN (\'test_name1\', \'test_name2\', \'test_name3\', NULL, 0, 1)';
        if ($result !== $correct) {
            throw new Exception('Failure2 in additionalTestBuildQuery.');
        }

        // проверяем, что выбрасывается исключение при дисбалансе фигурных скобок:
        $caught = false;
        try {
            $block = true;
            $result = $this->db->buildQuery(
                'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d} } {',
                ['user_id', [1, 2, 3], $block ?? $this->db->skip()]
            );
        } catch (Exception $e) {
            echo 'Правильно выброшено исключение в доп. тесте (дисбаланс фигурных): ', $e->getMessage(), "\n";
            $caught = true;
        }
        if (!$caught) {
            throw new Exception('Failure in add tests (no disbalance).');
        }

        // проверяем как отрабатывает запрос с двумя условиями (оба без skip):
        $result = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}{ AND user_id = ?d}',
            ['name', ['test_name1', 'test_name2', 'test_name3'], 1, 2]
        );
        $correct = 'SELECT name FROM users WHERE `name` IN (\'test_name1\', \'test_name2\', \'test_name3\') AND block = 1 AND user_id = 2';
        if ($result !== $correct) {
            throw new Exception('Failure3 in additionalTestBuildQuery.');
        }

        // проверяем как отрабатывает запрос с двумя условиями (певрое - skip):
        $result = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}{ AND user_id = ?d}',
            ['name', ['test_name1', 'test_name2', 'test_name3'], $this->db->skip(), 42]
        );
        $correct = 'SELECT name FROM users WHERE `name` IN (\'test_name1\', \'test_name2\', \'test_name3\') AND user_id = 42';
        if ($result !== $correct) {
            throw new Exception('Failure4 in additionalTestBuildQuery.');
        }

        // проверяем как отрабатывает запрос с двумя условиями (второе - skip):
        $result = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}{ AND user_id = ?d}',
            ['name', ['test_name1', 'test_name2', 'test_name3'], 1, $this->db->skip()]
        );
        $correct = 'SELECT name FROM users WHERE `name` IN (\'test_name1\', \'test_name2\', \'test_name3\') AND block = 1';
        if ($result !== $correct) {
            throw new Exception('Failure5 in additionalTestBuildQuery.');
        }

        // проверяем как отрабатывают несколько знаков ? с разными значениями: 
        $result = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = ? AND some_col = ? AND another = ?',
            ['Jack', 1, false, null]
        );
        $correct = 'SELECT * FROM users WHERE name = \'Jack\' AND block = 1 AND some_col = 0 AND another = NULL';
        if ($result !== $correct) {
            throw new Exception('Failure6 in additionalTestBuildQuery.');
        }

        // проверяем как отрабатывают три знака ? (есть один null и один skip):
        $result = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}{ AND user_id = ?d} AND name = ? AND email = ?',
            ['name', ['test_name1', 'test_name2', 'test_name3'], null, $this->db->skip(), 42, true]
        );
        $correct = 'SELECT name FROM users WHERE `name` IN (\'test_name1\', \'test_name2\', \'test_name3\') AND block = NULL AND name = 42 AND email = 1';
        if ($result !== $correct) {
            throw new Exception('Failure7 in additionalTestBuildQuery.');
        }

        // проверяем как отрабатывает парсинг словаря с float, NULL, true, false :
        $result = $this->db->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null, 'block' => 1.42, 's1' => null, 's2' => true, 's3' => false]]
        );
        $correct = 'UPDATE users SET `name` = \'Jack\', `email` = NULL, `block` = 1.42, `s1` = NULL, `s2` = 1, `s3` = 0 WHERE user_id = -1';
        if ($result !== $correct) {
            throw new Exception('Failure8 in additionalTestBuildQuery.');
        }
    }

    public function testDbQueries(): void
    {
        $queries = [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
            'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1',
        ];
        foreach ($queries as $query) {
            $query_result = $this->db->mysqli->query($query);
            $num_rows = $query_result->num_rows;
            if ($num_rows === 0) {
                throw new Exception('Failure in testDbQueries.');
            }
        }
    }
}
