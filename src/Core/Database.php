<?php

namespace App\Core;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = 'localhost';
        $dbname = 'school_schedule';
        $username = 'root';
        $password = '';

        try {
            $this->pdo = new \PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (\PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function fetch($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function fetchAll($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_fill(0, count($fields), '?');
        
        $query = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                 VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute(array_values($data));
    }

    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));
        
        $query = "UPDATE $table SET " . implode(', ', $fields) . " WHERE $where";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute(array_merge(array_values($data), $whereParams));
    }

    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM $table WHERE $where";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
} 