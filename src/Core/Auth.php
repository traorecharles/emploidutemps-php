<?php

namespace App\Core;

class Auth {
    private static $instance = null;
    private $user = null;

    private function __construct() {
        if (isset($_SESSION['user_id'])) {
            $db = Database::getInstance();
            $this->user = $db->fetch(
                "SELECT * FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function login($usernameOrEmail, $password) {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT * FROM users WHERE username = ? OR email = ?",
            [$usernameOrEmail, $usernameOrEmail]
        );

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                return false;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            $this->user = $user;
            return true;
        }

        return false;
    }

    public function logout() {
        unset($_SESSION['user_id']);
        $this->user = null;
        session_destroy();
    }

    public function getUser() {
        return $this->user;
    }

    public function isLoggedIn() {
        return $this->user !== null;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    public function hasRole($role) {
        return $this->user && $this->user['role'] === $role;
    }

    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('Location: dashboard.php');
            exit;
        }
    }

    public function register($data) {
        $db = Database::getInstance();
        // Vérifier si l'email ou le username existe déjà
        $existing = $db->fetch("SELECT * FROM users WHERE email = ? OR username = ?", [$data['email'], $data['username']]);
        if ($existing) {
            return false;
        }
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $insertData = [
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => $hashedPassword,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => $data['role'],
            'status' => 'inactive',
            'created_at' => date('Y-m-d H:i:s')
        ];
        if (isset($data['class_id'])) {
            $insertData['class_id'] = $data['class_id'];
        }
        return $db->insert('users', $insertData);
    }
} 