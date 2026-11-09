<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Core\Auth;
use App\Core\Database;

$auth = Auth::getInstance();
$auth->requireRole('admin');

$db = Database::getInstance();
$error = '';
$success = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // Vérifier si le cours existe
    $schedule = $db->fetch(
        "SELECT * FROM schedules WHERE id = ?",
        [$id]
    );

    if ($schedule) {
        if ($db->delete('schedules', 'id = ?', [$id])) {
            $success = 'Cours supprimé avec succès';
        } else {
            $error = 'Erreur lors de la suppression du cours';
        }
    } else {
        $error = 'Cours non trouvé';
    }
} else {
    $error = 'ID du cours non spécifié';
}

// Rediriger vers la page de gestion des emplois du temps
header('Location: /schedule-management.php' . ($error ? '?error=' . urlencode($error) : '') . ($success ? '?success=' . urlencode($success) : ''));
exit; 