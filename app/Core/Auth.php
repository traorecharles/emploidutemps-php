<?php

namespace App\Core;

class Auth {
    private static $instance = null;
    private $db;
    private $user = null;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->checkSession();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function checkSession() {
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->db->fetch(
                "SELECT * FROM users WHERE id = ? AND status = 'active'",
                [$_SESSION['user_id']]
            );
        }
    }

    public function login($email, $password) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? AND status = 'active'",
            [$email]
        );

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $this->user = $user;
            return true;
        }

        return false;
    }

    public function register($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['status'] = 'pending';
        
        return $this->db->insert('users', $data);
    }

    public function logout() {
        unset($_SESSION['user_id']);
        $this->user = null;
        session_destroy();
    }

    public function isLoggedIn() {
        return $this->user !== null;
    }

    public function getUser() {
        return $this->user;
    }

    public function hasRole($role) {
        return $this->isLoggedIn() && $this->user['role'] === $role;
    }

    public function requireRole($role) {
        if (!$this->hasRole($role)) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }
} 