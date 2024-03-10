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
            'SELECT ?# FROM users WHERE some_col = ?f AND block = ?d',
            [['name', 'email', 'some_col'], '2.41test', true]
        );
        $correct = 'SELECT `name`, `email`, `some_col` FROM users WHERE some_col = 2.41 AND block = 1';
        if ($result !== $correct) {
            throw new Exception('Failure1 in additionalTestBuildQuery.');
        }

        // проверяем как работает массив значений:
        $result = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a)',
            ['name', ['test_name1', 'test_name2', 'test_name3']]
        );
        $correct = 'SELECT name FROM users WHERE `name` IN (\'test_name1\', \'test_name2\', \'test_name3\')';
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
