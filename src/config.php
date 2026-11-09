<?php
session_start();

// Configuration de la base de données
$db_host = 'localhost';
$db_name = 'calendar_db';
$db_user = 'root';
$db_pass = '';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Configuration de l'application
define('BASE_URL', '/php_calendrier/public');

// Fonctions utilitaires
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isTeacher() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher';
}

function isStudent() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('/dashboard.php');
    }
}

function requireTeacher() {
    requireLogin();
    if (!isTeacher() && !isAdmin()) {
        redirect('/dashboard.php');
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent() && !isAdmin()) {
        redirect('/dashboard.php');
    }
}

// Fonction pour nettoyer les entrées
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('Erreur de sécurité : Token CSRF invalide');
    }
    return true;
} 