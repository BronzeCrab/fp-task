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

        var_dump($results);

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
        try {
            $this->db->buildQuery(
                'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
                [['name', 'email'], 2, true, 1]
            );
        } catch (Exception $e) {
            echo 'Правильно выброшено исключение в доп. тесте: ', $e->getMessage(), "\n";
        }

        $result = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE some_col = ?f AND block = ?d',
            [['name', 'email', 'some_col'], '2.41test', true]
        );
        $correct = 'SELECT `name`, `email`, `some_col` FROM users WHERE some_col = 2.41 AND block = 1';
        if ($result !== $correct) {
            throw new Exception('Failure in additionalTestBuildQuery.');
        }

    }

}
