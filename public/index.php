<?php
session_start();

// Inclure les classes nécessaires
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Core\Auth;

$auth = Auth::getInstance();

// Rediriger vers le tableau de bord si l'utilisateur est connecté
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Sinon, rediriger vers la page de connexion
header('Location: login.php');
exit; 