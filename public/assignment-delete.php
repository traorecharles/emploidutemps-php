<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Core\Auth;
use App\Core\Database;

$auth = Auth::getInstance();
$auth->requireRole('teacher');

$db = Database::getInstance();
$user = $auth->getUser();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // Vérifier que le devoir appartient au professeur
    $assignment = $db->fetch(
        "SELECT * FROM assignments WHERE id = ? AND teacher_id = ?",
        [$id, $user['id']]
    );

    if ($assignment) {
        // Récupérer les pièces jointes
        $attachments = $db->fetchAll(
            "SELECT * FROM assignment_attachments WHERE assignment_id = ?",
            [$id]
        );

        // Supprimer les fichiers physiques
        foreach ($attachments as $attachment) {
            $filePath = __DIR__ . '/../' . $attachment['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Supprimer les pièces jointes de la base de données
        $db->delete('assignment_attachments', 'assignment_id = ?', [$id]);

        // Supprimer le devoir
        if ($db->delete('assignments', 'id = ?', [$id])) {
            header('Location: /assignments.php?success=1');
            exit;
        }
    }
}

// En cas d'erreur ou si l'ID n'est pas valide
header('Location: /assignments.php');
exit; 